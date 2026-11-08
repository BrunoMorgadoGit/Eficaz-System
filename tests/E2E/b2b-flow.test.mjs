import assert from 'node:assert/strict';
import { execFileSync, spawn } from 'node:child_process';
import { once } from 'node:events';
import { mkdtemp, rm } from 'node:fs/promises';
import { createServer } from 'node:net';
import path from 'node:path';
import { tmpdir } from 'node:os';
import { setTimeout as delay } from 'node:timers/promises';
import { after, before, test } from 'node:test';
import { chromium } from 'playwright';

const projectRoot = process.cwd();
const contexts = [];
let browser;
let appServer;
let baseUrl;
let tempDirectory;
let serverError;
let serverOutput = '';

before(async () => {
    tempDirectory = await mkdtemp(path.join(tmpdir(), 'eficaz-e2e-'));
    const port = await findAvailablePort();
    baseUrl = `http://127.0.0.1:${port}`;
    const environment = createTestEnvironment(tempDirectory, baseUrl);

    execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--force'], {
        cwd: projectRoot,
        env: environment,
        stdio: 'pipe',
        timeout: 120_000,
    });

    appServer = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${port}`], {
        cwd: projectRoot,
        detached: process.platform !== 'win32',
        env: environment,
        stdio: ['ignore', 'pipe', 'pipe'],
    });
    appServer.stdout.on('data', (chunk) => appendServerOutput(chunk));
    appServer.stderr.on('data', (chunk) => appendServerOutput(chunk));
    appServer.on('error', (error) => {
        serverError = error;
    });

    await waitForApplication();
    browser = await chromium.launch({
        headless: true,
        channel: 'chrome',
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
});

after(async () => {
    for (const context of contexts) {
        await context.close().catch(() => {});
    }
    await browser?.close().catch(() => {});

    await stopAppServer();

    if (tempDirectory) {
        await rm(tempDirectory, { recursive: true, force: true });
    }
});

test('reseller completes a purchase and an administrator updates its status', async () => {
    const resellerContext = await createContext();
    const resellerPage = await resellerContext.newPage();

    await resellerPage.goto(`${baseUrl}/`);
    assert.equal(new URL(resellerPage.url()).pathname, '/login');
    await login(resellerPage, 'revendedor@eficaz.test', 'password');
    assert.equal(new URL(resellerPage.url()).pathname, '/dashboard');
    assert.match(await resellerPage.locator('body').innerText(), /Bem-vindo de volta, Marina Costa/);

    await resellerPage.goto(`${baseUrl}/produtos`);
    await resellerPage.locator('#catalog-search').fill('EFI-1001');
    await Promise.all([
        resellerPage.waitForURL((url) => url.searchParams.get('sku') === 'EFI-1001'),
        resellerPage.locator('#catalog-search').press('Enter'),
    ]);
    assert.match(await resellerPage.locator('body').innerText(), /Controlador Industrial X1/);

    const addForm = resellerPage.locator('form.catalog-add-form').first();
    await addForm.locator('input[name="quantity"]').fill('2');
    const addButton = resellerPage.locator(`button[form="${await addForm.getAttribute('id')}"]`);
    await Promise.all([
        resellerPage.waitForURL((url) => url.pathname === '/carrinho'),
        addButton.click(),
    ]);
    assert.match(await resellerPage.locator('body').innerText(), /R\$ 3\.591,00/);

    await Promise.all([
        resellerPage.waitForURL((url) => /^\/orcamentos\/\d+$/.test(url.pathname)),
        resellerPage.getByRole('button', { name: 'Gerar Orçamento' }).click(),
    ]);
    assert.match(await resellerPage.locator('body').innerText(), /EFI-1001/);
    assert.match(await resellerPage.locator('body').innerText(), /R\$ 3\.591,00/);

    await Promise.all([
        resellerPage.waitForURL((url) => url.pathname === '/orcamentos'),
        resellerPage.locator('#app-sidebar').getByRole('link', { name: 'Orçamentos', exact: true }).click(),
    ]);
    assert.match(await resellerPage.locator('body').innerText(), /Histórico de Orçamentos/);

    await Promise.all([
        resellerPage.waitForURL((url) => /^\/orcamentos\/\d+$/.test(url.pathname)),
        resellerPage.getByRole('link', { name: /ORC-/ }).click(),
    ]);

    await resellerPage.setViewportSize({ width: 390, height: 844 });
    await resellerPage.locator('#mobile-menu-btn').click();
    await Promise.all([
        resellerPage.waitForURL((url) => url.pathname === '/orcamentos'),
        resellerPage.locator('#app-sidebar').getByRole('link', { name: 'Orçamentos', exact: true }).click(),
    ]);
    assert.match(await resellerPage.locator('body').innerText(), /Histórico de Orçamentos/);
    assert.match(await resellerPage.locator('body').innerText(), /Em aberto/);
    assert.equal(
        await resellerPage.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth),
        false,
        'The quote history should not overflow horizontally on mobile.',
    );

    await Promise.all([
        resellerPage.waitForURL((url) => /^\/orcamentos\/\d+$/.test(url.pathname)),
        resellerPage.getByRole('link', { name: /ORC-/ }).click(),
    ]);
    assert.match(await resellerPage.locator('body').innerText(), /EFI-1001/);

    await Promise.all([
        resellerPage.waitForURL((url) => /^\/pedidos\/\d+$/.test(url.pathname)),
        resellerPage.getByRole('button', { name: 'Converter em pedido' }).click(),
    ]);
    const orderPath = new URL(resellerPage.url()).pathname;
    const orderId = orderPath.match(/^\/pedidos\/(\d+)$/)?.[1];
    assert.ok(orderId, `Expected an order detail URL, got ${orderPath}`);
    assert.match(await resellerPage.locator('body').innerText(), /Pendente/);

    const adminContext = await createContext();
    const adminPage = await adminContext.newPage();
    await adminPage.goto(`${baseUrl}/login`);
    await login(adminPage, 'admin@eficaz.test', 'password');
    assert.equal(new URL(adminPage.url()).pathname, '/admin/dashboard');
    await adminPage.goto(`${baseUrl}/admin/pedidos`);

    const statusSelect = adminPage.locator(`#status-${orderId}`);
    await statusSelect.selectOption('APROVADO');
    await Promise.all([
        adminPage.waitForResponse((response) => response.url().endsWith(`/admin/pedidos/${orderId}/status`)),
        adminPage.locator(`#status-${orderId}`).locator('xpath=ancestor::form').getByRole('button', { name: 'Salvar' }).click(),
    ]);
    assert.equal(await adminPage.locator(`#status-${orderId}`).inputValue(), 'APROVADO');

    await resellerPage.goto(`${baseUrl}${orderPath}`);
    assert.match(await resellerPage.locator('body').innerText(), /Aprovado/);
    assert.match(await resellerPage.locator('body').innerText(), /Condição de demonstração/);

    await adminPage.goto(`${baseUrl}/admin/condicoes-comerciais`);
    const goldPolicyForm = adminPage.locator('form').filter({ hasText: 'Grupo Gold' });
    await goldPolicyForm.locator('input[name="payment_terms"]').fill('Condição MVP: 30 dias');
    await goldPolicyForm.getByRole('button', { name: 'Salvar grupo' }).click();
    assert.equal(await goldPolicyForm.locator('input[name="payment_terms"]').inputValue(), 'Condição MVP: 30 dias');

    const localToday = new Date();
    const startDateValue = new Date(localToday.setDate(localToday.getDate() - 1));
    const startDate = new Date(startDateValue.getTime() - startDateValue.getTimezoneOffset() * 60_000).toISOString().slice(0, 10);
    const endDateValue = new Date(localToday.setDate(localToday.getDate() + 8));
    const endDate = new Date(endDateValue.getTime() - endDateValue.getTimezoneOffset() * 60_000).toISOString().slice(0, 10);

    await adminPage.goto(`${baseUrl}/admin/promocoes`);
    await adminPage.locator('#new_name').fill('Promoção teste E2E');
    await adminPage.locator('#new_profile').selectOption('GOLD');
    await adminPage.locator('#new_discount').fill('20');
    await adminPage.locator('#new_starts_on').fill(startDate);
    await adminPage.locator('#new_ends_on').fill(endDate);
    await adminPage.locator('input[type="checkbox"][name="is_active"]').check();
    await adminPage.getByRole('button', { name: 'Cadastrar promoção' }).click();
    assert.equal(
        await adminPage.locator('input[name="name"]').last().inputValue(),
        'Promoção teste E2E',
    );

    await adminPage.setViewportSize({ width: 390, height: 844 });
    for (const adminPath of ['/admin/condicoes-comerciais', '/admin/promocoes']) {
        await adminPage.goto(`${baseUrl}${adminPath}`);
        assert.equal(
            await adminPage.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth),
            false,
            `${adminPath} should not overflow horizontally on mobile.`,
        );
    }

    await resellerPage.setViewportSize({ width: 1365, height: 900 });
    await resellerPage.goto(`${baseUrl}/produtos`);
    await resellerPage.locator('#catalog-search').fill('EFI-1002');
    await Promise.all([
        resellerPage.waitForURL((url) => url.searchParams.get('sku') === 'EFI-1002'),
        resellerPage.locator('#catalog-search').press('Enter'),
    ]);
    const promoAddForm = resellerPage.locator('form.catalog-add-form').first();
    await promoAddForm.locator('input[name="quantity"]').fill('1');
    await Promise.all([
        resellerPage.waitForURL((url) => url.pathname === '/carrinho'),
        resellerPage.locator(`button[form="${await promoAddForm.getAttribute('id')}"]`).click(),
    ]);
    assert.match(await resellerPage.locator('body').innerText(), /R\$ 388,40/);

    await Promise.all([
        resellerPage.waitForURL((url) => /^\/orcamentos\/\d+$/.test(url.pathname)),
        resellerPage.getByRole('button', { name: 'Gerar Orçamento' }).click(),
    ]);
    assert.match(await resellerPage.locator('body').innerText(), /Promoção teste E2E/);
    assert.match(await resellerPage.locator('body').innerText(), /Condição MVP: 30 dias/);
    const promoQuotePath = new URL(resellerPage.url()).pathname;
    await Promise.all([
        resellerPage.waitForURL((url) => /^\/pedidos\/\d+$/.test(url.pathname)),
        resellerPage.getByRole('button', { name: 'Converter em pedido' }).click(),
    ]);
    assert.match(await resellerPage.locator('body').innerText(), /estoque foi baixado/i);
    await resellerPage.goto(`${baseUrl}/dashboard`);
    assert.match(await resellerPage.locator('body').innerText(), /R\$ 21\.020,60/);

    await adminPage.goto(`${baseUrl}/admin/dashboard`);
    assert.match(await adminPage.locator('body').innerText(), /Giro por SKU/);
    assert.match(await adminPage.locator('body').innerText(), /Giro estimado/);
    assert.match(await adminPage.locator('body').innerText(), /0,09x/);
    assert.match(await adminPage.locator('body').innerText(), /0,02x/);

    await assertResponsivePages(resellerPage, [
        '/dashboard',
        '/produtos?sku=EFI-1002',
        '/carrinho',
        '/orcamentos',
        promoQuotePath,
        '/pedidos',
        orderPath,
    ], 'revendedor');
    await assertResponsivePages(adminPage, [
        '/admin/dashboard',
        '/admin/produtos',
        '/admin/revendedores',
        '/admin/pedidos',
        '/admin/usuarios',
        '/admin/condicoes-comerciais',
        '/admin/promocoes',
    ], 'admin');
});

test('invalid login stays on the login page and reseller access to admin is denied', async () => {
    const context = await createContext();
    const page = await context.newPage();

    await page.goto(`${baseUrl}/`);
    await login(page, 'revendedor@eficaz.test', 'senha-incorreta', { expectSuccess: false });
    assert.equal(new URL(page.url()).pathname, '/login');
    assert.match(await page.getByRole('alert').innerText(), /Não foi possível entrar/);

    await login(page, 'revendedor@eficaz.test', 'password');
    const response = await page.goto(`${baseUrl}/admin/dashboard`);

    assert.equal(response?.status(), 403);
});

async function login(page, email, password, { expectSuccess = true } = {}) {
    await page.getByLabel('E-mail').fill(email);
    await page.getByLabel('Senha').fill(password);
    await page.getByRole('button', { name: 'Entrar no portal' }).click();

    if (!expectSuccess) {
        await page.getByRole('alert').waitFor();
        return;
    }

    await page.waitForURL((url) => url.pathname === '/dashboard' || url.pathname === '/admin/dashboard');
}

async function createContext() {
    const context = await browser.newContext({ viewport: { width: 1365, height: 900 } });
    contexts.push(context);

    return context;
}

async function assertResponsivePages(page, paths, role) {
    for (const width of [390, 768, 1365]) {
        await page.setViewportSize({ width, height: 900 });

        for (const pagePath of paths) {
            const response = await page.goto(`${baseUrl}${pagePath}`);
            const overflow = await page.evaluate(() => {
                const viewportWidth = document.documentElement.clientWidth;
                const layout = ['html', 'body', '.page-shell', '.app-main', '.content-wrap', '.content-wrap > div']
                    .map((selector) => {
                        const element = document.querySelector(selector);

                        if (!element) return { selector, missing: true };

                        const bounds = element.getBoundingClientRect();
                        const style = getComputedStyle(element);

                        return {
                            selector,
                            left: Math.round(bounds.left),
                            right: Math.round(bounds.right),
                            width: Math.round(bounds.width),
                            scrollWidth: element.scrollWidth,
                            clientWidth: element.clientWidth,
                            minWidth: style.minWidth,
                            display: style.display,
                            gridTemplateColumns: style.gridTemplateColumns,
                        };
                    });

                const elements = Array.from(document.querySelectorAll('body *'))
                    .map((element) => {
                        const bounds = element.getBoundingClientRect();

                        return {
                            tag: element.tagName.toLowerCase(),
                            id: element.id,
                            className: typeof element.className === 'string' ? element.className : '',
                            left: Math.round(bounds.left),
                            right: Math.round(bounds.right),
                            width: Math.round(bounds.width),
                            scrollWidth: element.scrollWidth,
                            clientWidth: element.clientWidth,
                        };
                    })
                    .filter((element) => element.right > viewportWidth + 1)
                    .sort((left, right) => left.right - right.right)
                    .slice(0, 8);

                return { layout, elements };
            });

            assert.equal(response?.status(), 200, `${role} page ${pagePath} should load at ${width}px.`);
            assert.equal(
                await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth),
                false,
                `${role} page ${pagePath} should not overflow horizontally at ${width}px: ${JSON.stringify(overflow)}`,
            );
        }
    }
}

function createTestEnvironment(directory, appUrl) {
    const cachePath = (name) => path.join(directory, `${name}.php`);

    return {
        ...process.env,
        APP_ENV: 'testing',
        APP_DEBUG: 'false',
        APP_KEY: 'base64:MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDA=',
        APP_URL: appUrl,
        APP_CONFIG_CACHE: cachePath('config'),
        APP_EVENTS_CACHE: cachePath('events'),
        APP_PACKAGES_CACHE: cachePath('packages'),
        APP_ROUTES_CACHE: cachePath('routes'),
        APP_SERVICES_CACHE: cachePath('services'),
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: path.join(directory, 'database.sqlite'),
        DB_URL: '',
        DATABASE_URL: '',
        CACHE_STORE: 'array',
        SESSION_DRIVER: 'database',
        SESSION_COOKIE: 'eficaz-e2e-session',
        QUEUE_CONNECTION: 'sync',
        MAIL_MAILER: 'array',
    };
}

async function findAvailablePort() {
    const server = createServer();
    server.listen(0, '127.0.0.1');
    await once(server, 'listening');
    const address = server.address();
    await new Promise((resolve, reject) => server.close((error) => error ? reject(error) : resolve()));

    if (!address || typeof address === 'string') {
        throw new Error('Could not reserve a local port for the E2E server.');
    }

    return address.port;
}

async function waitForApplication() {
    let lastError;

    for (let attempt = 0; attempt < 100; attempt += 1) {
        if (serverError) {
            throw serverError;
        }
        if (appServer.exitCode !== null) {
            throw new Error(`Laravel server exited before becoming ready. ${serverOutput}`);
        }

        try {
            const response = await fetch(`${baseUrl}/login`);
            if (response.ok) {
                return;
            }
            lastError = new Error(`Laravel returned HTTP ${response.status}.`);
        } catch (error) {
            lastError = error;
        }

        await delay(150);
    }

    throw new Error(`Laravel did not become ready: ${lastError?.message ?? 'unknown error'}. ${serverOutput}`);
}

function appendServerOutput(chunk) {
    serverOutput = `${serverOutput}${chunk.toString()}`.slice(-5_000);
}

async function stopAppServer() {
    if (!appServer || appServer.exitCode !== null) {
        return;
    }

    const exited = once(appServer, 'exit').catch(() => {});
    signalAppServer('SIGTERM');
    await Promise.race([exited, delay(1_000)]);

    if (appServer.exitCode === null) {
        signalAppServer('SIGKILL');
    }
    appServer.stdout.destroy();
    appServer.stderr.destroy();
}

function signalAppServer(signal) {
    try {
        if (process.platform !== 'win32' && appServer.pid) {
            process.kill(-appServer.pid, signal);
        } else {
            appServer.kill(signal);
        }
    } catch (error) {
        if (error.code !== 'ESRCH') {
            throw error;
        }
    }
}

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

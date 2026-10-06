@props(['title' => 'Eficaz B2B', 'activeNav' => ''])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Eficaz B2B' }}</title>
    @vite('resources/css/app.css')
</head>
<body>
    @php
        $currentUser = auth()->user();
        $isAdmin = $currentUser?->isAdmin() ?? false;
        $displayName = $currentUser?->name ?? 'Conta B2B';
        $displayEmail = $currentUser?->email ?? '';
        $currentReseller = $currentUser?->isReseller() ? $currentUser->reseller : null;
        $activeQuote = request()->route('quote');
        $quoteNavHref = $activeQuote
            ? route('reseller.quotes.show', $activeQuote)
            : route('reseller.cart.index');
        $cartProductCount = 0;
        if ($currentReseller && request()->routeIs('reseller.cart.index')) {
            $cartProductCount = $currentReseller->cart?->items()->count() ?? 0;
        }
        $moduleHeaderTitle = null;
        $moduleHeaderDescription = null;
        $moduleHeaderActionLabel = null;
        $moduleHeaderActionRoute = null;
        $moduleHeaderActionPrimary = false;
        $moduleHeaderStatus = null;

        if ($isAdmin && request()->routeIs('admin.dashboard')) {
            $moduleHeaderTitle = 'Panorama da operação';
            $moduleHeaderDescription = 'Acompanhe os indicadores centrais da plataforma e priorize os próximos movimentos comerciais.';
            $moduleHeaderActionLabel = 'Cadastrar produto';
            $moduleHeaderActionRoute = route('admin.products.create');
            $moduleHeaderActionPrimary = true;
        } elseif ($isAdmin && request()->routeIs('admin.products.index')) {
            $moduleHeaderTitle = 'Produtos';
            $moduleHeaderDescription = 'Cadastre e mantenha os itens disponíveis para as operações dos revendedores.';
            $moduleHeaderActionLabel = 'Novo produto';
            $moduleHeaderActionRoute = route('admin.products.create');
            $moduleHeaderActionPrimary = true;
        } elseif ($isAdmin && request()->routeIs('admin.products.create')) {
            $moduleHeaderTitle = 'Cadastrar produto';
            $moduleHeaderDescription = 'Informe os dados que ficarão visíveis aos revendedores no catálogo B2B.';
            $moduleHeaderActionLabel = 'Voltar ao catálogo';
            $moduleHeaderActionRoute = route('admin.products.index');
        } elseif ($isAdmin && request()->routeIs('admin.products.edit')) {
            $moduleHeaderTitle = 'Editar produto';
            $moduleHeaderDescription = 'Atualize as informações de ' . data_get(request()->route('product'), 'name', 'produto') . ' sem alterar os registros comerciais já emitidos.';
            $moduleHeaderActionLabel = 'Voltar ao catálogo';
            $moduleHeaderActionRoute = route('admin.products.index');
        } elseif ($isAdmin && request()->routeIs('admin.resellers.index')) {
            $moduleHeaderTitle = 'Revendedores';
            $moduleHeaderDescription = 'Consulte os perfis comerciais e o limite de crédito das empresas cadastradas.';
        } elseif ($isAdmin && request()->routeIs('admin.orders.index')) {
            $moduleHeaderTitle = 'Pedidos';
            $moduleHeaderDescription = 'Acompanhe os pedidos e atualize seu status operacional.';
        } elseif (request()->routeIs('reseller.orders.show')) {
            $currentOrder = request()->route('order');
            $orderCreatedAt = data_get($currentOrder, 'created_at');
            $moduleHeaderTitle = 'Pedido #' . data_get($currentOrder, 'id', '—');
            $moduleHeaderDescription = $orderCreatedAt
                ? 'Registrado em ' . \Illuminate\Support\Carbon::parse($orderCreatedAt)->format('d/m/Y \à\s H:i')
                : 'Detalhes do pedido confirmado.';
            $moduleHeaderStatus = data_get($currentOrder, 'status', 'PENDENTE');
            $moduleHeaderActionLabel = 'Voltar aos pedidos';
            $moduleHeaderActionRoute = route('reseller.orders.index');
        } elseif (request()->routeIs('profile')) {
            $moduleHeaderTitle = 'Meu perfil';
            $moduleHeaderDescription = 'Consulte os dados vinculados ao seu acesso no portal B2B.';
            $moduleHeaderActionLabel = 'Voltar ao painel';
            $moduleHeaderActionRoute = $isAdmin ? route('admin.dashboard') : route('reseller.dashboard');
        }
    @endphp

    <div class="page-shell lg:flex">
        <aside class="app-sidebar" aria-label="Navegação principal">
            <a href="{{ $isAdmin ? route('admin.dashboard') : route('reseller.dashboard') }}" class="brand-lockup" aria-label="Eficaz B2B, página inicial">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="2.2"><path d="M4 19V9l8-5 8 5v10M8 19v-6h8v6M4 9h16" stroke-linejoin="round"/></svg>
                </span>
                <span>
                    <span class="brand-name block">Eficaz B2B</span>
                    <span class="brand-caption block">Gestão industrial</span>
                </span>
            </a>

            @if ($isAdmin)
                <p class="sidebar-section-label">Operação</p>
                <nav class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-3H4v3Z" stroke-linejoin="round"/></svg> Visão geral
                    </a>
                    <a href="{{ route('admin.products.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'products'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4m8-14v10l-8 4m0-10v10" stroke-linejoin="round"/></svg> Catálogo
                    </a>
                    <a href="{{ route('admin.resellers.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'resellers'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M16 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1m11-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm3 4a4 4 0 0 1 4 4v1" stroke-linecap="round"/></svg> Revendedores
                    </a>
                    <a href="{{ route('admin.orders.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'orders'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M7 3h10l3 4v14H4V7l3-4Zm-3 4h16M8 11h8" stroke-linejoin="round"/></svg> Pedidos
                    </a>
                </nav>
            @else
                <p class="sidebar-section-label">Revendedor</p>
                <nav class="space-y-1">
                    <a href="{{ route('reseller.dashboard') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-3H4v3Z" stroke-linejoin="round"/></svg> Dashboard
                    </a>
                    <a href="{{ route('reseller.products.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'products'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4m8-14v10l-8 4m8-14v10l-8 4m0-10v10" stroke-linejoin="round"/></svg> Produtos
                    </a>
                    <a href="{{ route('reseller.cart.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'cart'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7M10 20a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm9 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg> Carrinho
                        @if ($cartProductCount > 0)<span class="sidebar-cart-count">{{ $cartProductCount }}</span>@endif
                    </a>
                    <a href="{{ $quoteNavHref }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'quotes'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M6 3h9l4 4v14H6z" stroke-linejoin="round"/><path d="M15 3v5h5M9 12h7m-7 4h7" stroke-linecap="round"/></svg> Orçamento
                    </a>
                    <a href="{{ route('reseller.orders.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'orders'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M7 3h10l3 4v14H4V7l3-4Zm-3 4h16M8 11h8" stroke-linejoin="round"/></svg> Meus pedidos
                    </a>
                </nav>
            @endif

            <div class="sidebar-user">
                <p class="truncate text-sm font-semibold text-white">{{ $displayName }}</p>
                @if ($currentReseller)
                    <p class="mt-0.5 truncate text-xs text-slate-400">{{ $currentReseller->company_name }}</p>
                    <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-cyan-400">{{ $currentReseller->commercial_profile }}</p>
                @else
                    <p class="mt-0.5 truncate text-xs text-slate-400">{{ $displayEmail }}</p>
                @endif
                <div class="mt-3 flex items-center justify-between gap-2">
                    <a href="{{ route('profile') }}" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300">Meu perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-slate-300 hover:text-white">Sair</button>
                    </form>
                </div>
            </div>
        </aside>

        <main @class([
            'app-main',
            'app-main--reseller-dashboard' => $activeNav === 'dashboard' && ! $isAdmin,
            'app-main--reseller-catalog' => $activeNav === 'products' && ! $isAdmin,
            'app-main--reseller-quote' => $activeNav === 'quotes' && ! $isAdmin,
            'app-main--reseller-orders' => $activeNav === 'orders' && ! $isAdmin && request()->routeIs('reseller.orders.index'),
            'app-main--reseller-cart' => $activeNav === 'cart' && ! $isAdmin,
        ])>
            <header @class([
                'topbar',
                'topbar--reseller-dashboard' => $activeNav === 'dashboard' && ! $isAdmin,
                'topbar--reseller-catalog' => $activeNav === 'products' && ! $isAdmin,
                'topbar--reseller-quote' => $activeNav === 'quotes' && ! $isAdmin,
                'topbar--reseller-orders' => $activeNav === 'orders' && ! $isAdmin && request()->routeIs('reseller.orders.index'),
                'topbar--reseller-cart' => $activeNav === 'cart' && ! $isAdmin,
                'topbar--module-header' => $moduleHeaderTitle !== null,
            ])>
                @if ($moduleHeaderTitle)
                    <div class="desktop-module-header">
                        <div class="desktop-module-heading-copy">
                            <h1>{{ $moduleHeaderTitle }}</h1>
                            <p>{{ $moduleHeaderDescription }}</p>
                        </div>
                        <div class="desktop-module-header-actions">
                            @if ($moduleHeaderStatus)
                                <x-status-badge :status="$moduleHeaderStatus" />
                            @endif
                            @if ($moduleHeaderActionLabel && $moduleHeaderActionRoute)
                                <a href="{{ $moduleHeaderActionRoute }}" @class(['module-header-action', 'module-header-action--primary' => $moduleHeaderActionPrimary])>{{ $moduleHeaderActionLabel }}</a>
                            @endif
                        </div>
                    </div>
                @elseif ($activeNav === 'dashboard' && ! $isAdmin)
                    <div class="desktop-dashboard-header">
                        <div>
                            <h1>Dashboard</h1>
                            <p>Bem-vindo de volta, {{ $displayName }}. Aqui está o resumo da sua conta.</p>
                        </div>
                        <a href="{{ route('reseller.products.index') }}" class="dashboard-search-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                            Buscar por SKU
                        </a>
                    </div>
                @elseif ($activeNav === 'products' && ! $isAdmin)
                    <div class="desktop-catalog-header">
                        <h1>Catálogo de Produtos</h1>
                        <p>Busque pelo SKU do produto</p>
                    </div>
                @elseif ($activeNav === 'quotes' && ! $isAdmin)
                    <div class="desktop-quote-header">
                        <h1>Orçamento</h1>
                        <p>Revise os itens e converta em pedido</p>
                    </div>
                @elseif ($activeNav === 'orders' && ! $isAdmin && request()->routeIs('reseller.orders.index'))
                    <div class="desktop-orders-header">
                        <h1>Meus Pedidos</h1>
                        <p>Histórico e acompanhamento de pedidos</p>
                    </div>
                @elseif ($activeNav === 'cart' && ! $isAdmin)
                    <div class="desktop-cart-header">
                        <div>
                            <h1>Carrinho</h1>
                            <p>{{ $cartProductCount }} {{ $cartProductCount === 1 ? 'produto selecionado' : 'produtos selecionados' }}</p>
                        </div>
                        <a href="{{ route('reseller.products.index') }}">+ Adicionar produtos</a>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4 lg:hidden">
                    <a href="{{ $isAdmin ? route('admin.dashboard') : route('reseller.dashboard') }}" class="brand-lockup">
                        <span class="brand-mark h-8 w-8 rounded-lg" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2.2"><path d="M4 19V9l8-5 8 5v10M8 19v-6h8v6M4 9h16" stroke-linejoin="round"/></svg></span>
                        <span class="brand-name text-navy-950">Eficaz B2B</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary min-h-8 px-3 py-1.5 text-xs">Sair</button>
                    </form>
                </div>
                <nav class="mobile-nav mt-3" aria-label="Navegação móvel">
                    @if ($isAdmin)
                        <a href="{{ route('admin.dashboard') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])>Visão geral</a>
                        <a href="{{ route('admin.products.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'products'])>Catálogo</a>
                        <a href="{{ route('admin.resellers.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'resellers'])>Revendedores</a>
                        <a href="{{ route('admin.orders.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'orders'])>Pedidos</a>
                    @else
                        <a href="{{ route('reseller.dashboard') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])>Dashboard</a>
                        <a href="{{ route('reseller.products.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'products'])>Produtos</a>
                        <a href="{{ route('reseller.cart.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'cart'])>Carrinho @if ($cartProductCount > 0)<span class="sidebar-cart-count">{{ $cartProductCount }}</span>@endif</a>
                        <a href="{{ $quoteNavHref }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'quotes'])>Orçamento</a>
                        <a href="{{ route('reseller.orders.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'orders'])>Meus pedidos</a>
                    @endif
                </nav>
            </header>

            <div @class([
                'content-wrap',
                'content-wrap--reseller-dashboard' => $activeNav === 'dashboard' && ! $isAdmin,
                'content-wrap--reseller-catalog' => $activeNav === 'products' && ! $isAdmin,
                'content-wrap--reseller-quote' => $activeNav === 'quotes' && ! $isAdmin,
                'content-wrap--reseller-orders' => $activeNav === 'orders' && ! $isAdmin && request()->routeIs('reseller.orders.index'),
                'content-wrap--reseller-cart' => $activeNav === 'cart' && ! $isAdmin,
            ])>
                <x-flash />
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>

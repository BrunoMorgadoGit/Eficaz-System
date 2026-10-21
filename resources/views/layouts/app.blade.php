@props(['title' => 'Eficaz B2B', 'activeNav' => ''])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Eficaz B2B' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/sidebar.js'])
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

    {{-- Mobile overlay --}}
    <div id="sidebar-overlay" class="sidebar-overlay" aria-hidden="true"></div>

    <div class="page-shell lg:flex">
        <aside id="app-sidebar" class="app-sidebar" aria-label="Navegação principal">

            {{-- Brand + toggle button --}}
            <div class="sidebar-header">
                <a href="{{ $isAdmin ? route('admin.dashboard') : route('reseller.dashboard') }}"
                   class="brand-lockup brand-lockup--sidebar"
                   aria-label="Eficaz B2B, página inicial">
                    <span class="sidebar-brand-logo sidebar-brand-logo--expanded" aria-hidden="true">
                        <img src="{{ asset('image/logo.png') }}" alt="" width="2172" height="724">
                    </span>
                    <span class="sidebar-brand-logo sidebar-brand-logo--collapsed" aria-hidden="true">
                        <img src="{{ asset('image/icon.png') }}" alt="" width="1254" height="1254">
                    </span>
                </a>
                <button id="sidebar-toggle"
                        class="sidebar-toggle-btn"
                        type="button"
                        aria-label="Recolher menu"
                        aria-pressed="false">
                    {{-- Chevron left icon --}}
                    <svg class="sidebar-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </button>
            </div>

            {{-- Nav items --}}
            @if ($isAdmin)
                <p class="sidebar-section-label"><span class="sidebar-section-label-text">Operação</span></p>
                <nav class="sidebar-nav" aria-label="Menu administrativo">
                    <a href="{{ route('admin.dashboard') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])
                       title="Visão geral">
                        <svg viewBox="0 0 24 24" class="nav-icon fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-3H4v3Z" stroke-linejoin="round"/></svg>
                        <span class="nav-label">Visão geral</span>
                    </a>
                    <a href="{{ route('admin.products.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'products'])
                       title="Catálogo">
                        <svg viewBox="0 0 24 24" class="nav-icon fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4m8-14v10l-8 4m0-10v10" stroke-linejoin="round"/></svg>
                        <span class="nav-label">Catálogo</span>
                    </a>
                    <a href="{{ route('admin.resellers.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'resellers'])
                       title="Revendedores">
                        <svg viewBox="0 0 24 24" class="nav-icon fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M16 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1m11-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm3 4a4 4 0 0 1 4 4v1" stroke-linecap="round"/></svg>
                        <span class="nav-label">Revendedores</span>
                    </a>
                    <a href="{{ route('admin.orders.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'orders'])
                       title="Pedidos">
                        <svg viewBox="0 0 24 24" class="nav-icon fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M7 3h10l3 4v14H4V7l3-4Zm-3 4h16M8 11h8" stroke-linejoin="round"/></svg>
                        <span class="nav-label">Pedidos</span>
                    </a>
                </nav>
            @else
                <p class="sidebar-section-label"><span class="sidebar-section-label-text">Revendedor</span></p>
                <nav class="sidebar-nav" aria-label="Menu revendedor">
                    <a href="{{ route('reseller.dashboard') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])
                       title="Visão geral">
                        <svg viewBox="0 0 24 24" class="nav-icon fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-3H4v3Z" stroke-linejoin="round"/></svg>
                        <span class="nav-label">Visão geral</span>
                    </a>
                    <a href="{{ route('reseller.products.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'products'])
                       title="Produtos">
                        <svg viewBox="0 0 24 24" class="nav-icon fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4m8-14v10l-8 4m8-14v10l-8 4m0-10v10" stroke-linejoin="round"/></svg>
                        <span class="nav-label">Produtos</span>
                    </a>
                    <a href="{{ route('reseller.cart.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'cart'])
                       title="Carrinho">
                        <svg viewBox="0 0 24 24" class="nav-icon fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7M10 20a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm9 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="nav-label">Carrinho</span>
                        @if ($cartProductCount > 0)<span class="sidebar-cart-count">{{ $cartProductCount }}</span>@endif
                    </a>
                    <a href="{{ $quoteNavHref }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'quotes'])
                       title="Orçamento">
                        <svg viewBox="0 0 24 24" class="nav-icon fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M6 3h9l4 4v14H6z" stroke-linejoin="round"/><path d="M15 3v5h5M9 12h7m-7 4h7" stroke-linecap="round"/></svg>
                        <span class="nav-label">Orçamento</span>
                    </a>
                    <a href="{{ route('reseller.orders.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'orders'])
                       title="Meus pedidos">
                        <svg viewBox="0 0 24 24" class="nav-icon fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M7 3h10l3 4v14H4V7l3-4Zm-3 4h16M8 11h8" stroke-linejoin="round"/></svg>
                        <span class="nav-label">Meus pedidos</span>
                    </a>
                </nav>
            @endif

            {{-- User area --}}
            <div class="sidebar-user">
                <div class="sidebar-user-header">
                    <div class="sidebar-user-avatar" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}
                    </div>
                    <div class="sidebar-user-info">
                        <p class="sidebar-user-name">{{ $displayName }}</p>
                        @if ($currentReseller)
                            <p class="sidebar-user-sub">{{ $currentReseller->company_name }}</p>
                            <p class="sidebar-user-role">{{ $currentReseller->commercial_profile }}</p>
                        @else
                            <p class="sidebar-user-sub">{{ $displayEmail }}</p>
                            <p class="sidebar-user-role">Administrador</p>
                        @endif
                    </div>
                </div>
                <div class="sidebar-user-actions">
                    <a href="{{ route('profile') }}" class="sidebar-user-link" title="Meu perfil">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span class="nav-label">Perfil</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form">
                        @csrf
                        <button type="submit" class="sidebar-user-link sidebar-logout-btn" title="Sair">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span class="nav-label">Sair</span>
                        </button>
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
                            <h1>Visão geral</h1>
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

                {{-- Mobile topbar --}}
                <div class="mobile-topbar lg:hidden">
                    <button id="mobile-menu-btn"
                            class="mobile-menu-btn"
                            type="button"
                            aria-label="Abrir menu"
                            aria-expanded="false"
                            aria-controls="app-sidebar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M3 12h18M3 6h18M3 18h18"/>
                        </svg>
                    </button>
                    <a href="{{ $isAdmin ? route('admin.dashboard') : route('reseller.dashboard') }}"
                       class="brand-lockup brand-lockup--mobile"
                       aria-label="Eficaz B2B, página inicial">
                        <img src="{{ asset('image/logo.png') }}" alt="" class="mobile-brand-image" width="2172" height="724">
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
                        <a href="{{ route('reseller.dashboard') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])>Visão geral</a>
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

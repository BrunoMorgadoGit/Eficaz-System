@props(['title' => 'Eficaz B2B', 'activeNav' => ''])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Eficaz B2B' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icon.png') }}">
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
                    <x-icon name="chevron-left" class="sidebar-toggle-icon" stroke-width="2" />
                </button>
            </div>

            {{-- Nav items --}}
            @if ($isAdmin)
                <p class="sidebar-section-label"><span class="sidebar-section-label-text">Operação</span></p>
                <nav class="sidebar-nav" aria-label="Menu administrativo">
                    <a href="{{ route('admin.dashboard') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])
                       title="Visão geral">
                        <x-icon name="layout-dashboard" class="nav-icon" />
                        <span class="nav-label">Visão geral</span>
                    </a>
                    <a href="{{ route('admin.products.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'products'])
                       title="Catálogo">
                        <x-icon name="package" class="nav-icon" />
                        <span class="nav-label">Catálogo</span>
                    </a>
                    <a href="{{ route('admin.resellers.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'resellers'])
                       title="Revendedores">
                        <x-icon name="users" class="nav-icon" />
                        <span class="nav-label">Revendedores</span>
                    </a>
                    <a href="{{ route('admin.orders.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'orders'])
                       title="Pedidos">
                        <x-icon name="clipboard-list" class="nav-icon" />
                        <span class="nav-label">Pedidos</span>
                    </a>
                </nav>
            @else
                <p class="sidebar-section-label"><span class="sidebar-section-label-text">Revendedor</span></p>
                <nav class="sidebar-nav" aria-label="Menu revendedor">
                    <a href="{{ route('reseller.dashboard') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])
                       title="Visão geral">
                        <x-icon name="layout-dashboard" class="nav-icon" />
                        <span class="nav-label">Visão geral</span>
                    </a>
                    <a href="{{ route('reseller.products.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'products'])
                       title="Produtos">
                        <x-icon name="package" class="nav-icon" />
                        <span class="nav-label">Produtos</span>
                    </a>
                    <a href="{{ route('reseller.cart.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'cart'])
                       title="Carrinho">
                        <x-icon name="shopping-cart" class="nav-icon" />
                        <span class="nav-label">Carrinho</span>
                        @if ($cartProductCount > 0)<span class="sidebar-cart-count">{{ $cartProductCount }}</span>@endif
                    </a>
                    <a href="{{ $quoteNavHref }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'quotes'])
                       title="Orçamento">
                        <x-icon name="file-description" class="nav-icon" />
                        <span class="nav-label">Orçamento</span>
                    </a>
                    <a href="{{ route('reseller.orders.index') }}"
                       @class(['nav-link', 'nav-link--active' => $activeNav === 'orders'])
                       title="Meus pedidos">
                        <x-icon name="clipboard-list" class="nav-icon" />
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
                        <p class="sidebar-user-name" title="{{ $displayName }}">{{ $displayName }}</p>
                        @if ($currentReseller)
                            <p class="sidebar-user-sub" title="{{ $currentReseller->company_name }}">{{ $currentReseller->company_name }}</p>
                            <p class="sidebar-user-role font-mono">{{ $currentReseller->commercial_profile }}</p>
                        @else
                            <p class="sidebar-user-sub" title="{{ $displayEmail }}">{{ $displayEmail }}</p>
                            <p class="sidebar-user-role">Administrador</p>
                        @endif
                    </div>
                </div>
                <div class="sidebar-user-actions">
                    <a href="{{ route('profile') }}" class="sidebar-user-link" title="Meu perfil" aria-label="Perfil">
                        <x-icon name="user" class="nav-icon" />
                        <span class="nav-label">Perfil</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form">
                        @csrf
                        <button type="submit" class="sidebar-user-link sidebar-logout-btn" title="Sair" aria-label="Sair">
                            <x-icon name="logout" class="nav-icon" />
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
                            <x-icon name="search" stroke-width="2" />
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
                        <a href="{{ route('reseller.products.index') }}"><x-icon name="plus" size="1em" /> Adicionar produtos</a>
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
                        <x-icon name="menu-2" stroke-width="2" />
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

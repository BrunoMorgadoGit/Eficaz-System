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
                <p class="sidebar-section-label">Minha operação</p>
                <nav class="space-y-1">
                    <a href="{{ route('reseller.dashboard') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-3H4v3Z" stroke-linejoin="round"/></svg> Visão geral
                    </a>
                    <a href="{{ route('reseller.products.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'products'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4m8-14v10l-8 4m8-14v10l-8 4m0-10v10" stroke-linejoin="round"/></svg> Catálogo
                    </a>
                    <a href="{{ route('reseller.cart.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'cart'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7M10 20a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm9 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg> Meu carrinho
                    </a>
                    <a href="{{ route('reseller.orders.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'orders'])>
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M7 3h10l3 4v14H4V7l3-4Zm-3 4h16M8 11h8" stroke-linejoin="round"/></svg> Pedidos
                    </a>
                </nav>
            @endif

            <div class="sidebar-user">
                <p class="truncate text-sm font-semibold text-white">{{ $displayName }}</p>
                <p class="mt-0.5 truncate text-xs text-slate-400">{{ $displayEmail }}</p>
                <div class="mt-3 flex items-center justify-between gap-2">
                    <a href="{{ route('profile') }}" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300">Meu perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-slate-300 hover:text-white">Sair</button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="app-main">
            <header class="topbar">
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
                        <a href="{{ route('reseller.dashboard') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'dashboard'])>Visão geral</a>
                        <a href="{{ route('reseller.products.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'products'])>Catálogo</a>
                        <a href="{{ route('reseller.cart.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'cart'])>Meu carrinho</a>
                        <a href="{{ route('reseller.orders.index') }}" @class(['nav-link', 'nav-link--active' => $activeNav === 'orders'])>Pedidos</a>
                    @endif
                </nav>
            </header>

            <div class="content-wrap">
                <x-flash />
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>

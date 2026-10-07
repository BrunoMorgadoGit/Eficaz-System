<x-layouts.app title="Dashboard | Eficaz B2B" active-nav="dashboard">
    @php
        $summary = $stats ?? [];
        $recentOrders = $recentOrders ?? collect();
        $recentQuotes = $recentQuotes ?? collect();
        $monthDelta = (int) data_get($summary, 'orders_this_month', 0)
            - (int) data_get($summary, 'orders_previous_month', 0);
        $creditLimit = data_get($reseller, 'credit_limit');
        $totalInOrders = (float) data_get($summary, 'total_spent', 0);
    @endphp

    <div class="reseller-dashboard">
        <header class="dashboard-mobile-heading lg:hidden">
            <h1>Dashboard</h1>
            <p>Bem-vindo de volta, {{ data_get($reseller, 'user.name', 'revendedor') }}. Aqui está o resumo da sua conta.</p>
        </header>

        <section class="dashboard-metrics" aria-label="Resumo da operação">
            <article class="dashboard-stat-card">
                <div>
                    <p class="dashboard-stat-label">Pedidos no mês</p>
                    <p class="dashboard-stat-value">{{ data_get($summary, 'orders_this_month', 0) }}</p>
                    <p class="dashboard-stat-hint">
                        @if ($monthDelta > 0)
                            +{{ $monthDelta }} em relação ao mês anterior
                        @elseif ($monthDelta < 0)
                            {{ abs($monthDelta) }} a menos que no mês anterior
                        @else
                            Mesmo total do mês anterior
                        @endif
                    </p>
                </div>
                <span class="dashboard-stat-icon dashboard-stat-icon--amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 7 9-4 9 4v10l-9 4-9-4V7Z" stroke-linejoin="round"/><path d="m3 7 9 4 9-4M12 11v10M7.5 5 16 9" stroke-linejoin="round"/></svg>
                </span>
            </article>

            <article class="dashboard-stat-card">
                <div>
                    <p class="dashboard-stat-label">Orçamentos em aberto</p>
                    <p class="dashboard-stat-value">{{ data_get($summary, 'open_quotes_count', 0) }}</p>
                    <p class="dashboard-stat-hint">Aguardando conversão em pedido</p>
                </div>
                <span class="dashboard-stat-icon dashboard-stat-icon--blue" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h9l4 4v14H6z" stroke-linejoin="round"/><path d="M15 3v5h5M9 12h7m-7 4h7" stroke-linecap="round"/></svg>
                </span>
            </article>

            <article class="dashboard-stat-card">
                <div>
                    <p class="dashboard-stat-label">Carrinho</p>
                    <p class="dashboard-stat-value">{{ data_get($summary, 'cart_items', 0) }}</p>
                    <p class="dashboard-stat-hint">unidades no carrinho</p>
                </div>
                <span class="dashboard-stat-icon dashboard-stat-icon--cyan" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                </span>
            </article>

            <article class="dashboard-stat-card">
                <div>
                    <p class="dashboard-stat-label">Limite cadastrado</p>
                    <p class="dashboard-stat-value dashboard-stat-value--money">
                        {{ $creditLimit === null ? 'Não informado' : 'R$ ' . number_format((float) $creditLimit, 2, ',', '.') }}
                    </p>
                    <p class="dashboard-stat-hint">Valor do seu perfil comercial</p>
                </div>
                <span class="dashboard-stat-icon dashboard-stat-icon--gold" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 9h18m-13 6h4" stroke-linecap="round"/></svg>
                </span>
            </article>
        </section>

        <section class="dashboard-credit-panel" aria-labelledby="dashboard-credit-title">
            <div>
                <p id="dashboard-credit-title" class="dashboard-credit-label">Limite de crédito cadastrado</p>
                <p class="dashboard-credit-value">
                    {{ $creditLimit === null ? 'Não informado' : 'R$ ' . number_format((float) $creditLimit, 2, ',', '.') }}
                </p>
                <p class="dashboard-credit-note">O limite exibido é o valor registrado para {{ data_get($reseller, 'company_name', 'sua empresa') }}.</p>
            </div>
            <div class="dashboard-credit-total">
                <span>Total em pedidos registrados</span>
                <strong>R$ {{ number_format($totalInOrders, 2, ',', '.') }}</strong>
            </div>
        </section>

        <section class="dashboard-recent-grid" aria-label="Movimentações recentes">
            <article class="dashboard-list-panel">
                <header class="dashboard-list-heading">
                    <h2>Pedidos recentes</h2>
                    <a href="{{ route('reseller.orders.index') }}">Ver todos <span aria-hidden="true">→</span></a>
                </header>

                @forelse ($recentOrders as $order)
                    <a href="{{ route('reseller.orders.show', $order) }}" class="dashboard-list-row">
                        <span class="dashboard-row-description">
                            <strong>Pedido #{{ $order->getKey() }}</strong>
                            <span>{{ $order->created_at?->format('d/m/Y') ?? '—' }}</span>
                        </span>
                        <strong class="dashboard-row-total">R$ {{ number_format((float) $order->total, 2, ',', '.') }}</strong>
                        <x-status-badge :status="$order->status" />
                    </a>
                @empty
                    <div class="dashboard-list-empty">
                        <p>Nenhum pedido registrado ainda.</p>
                        <a href="{{ route('reseller.products.index') }}">Consultar produtos</a>
                    </div>
                @endforelse
            </article>

            <article class="dashboard-list-panel">
                <header class="dashboard-list-heading">
                    <h2>Orçamentos</h2>
                    <a href="{{ route('reseller.cart.index') }}">Novo orçamento <span aria-hidden="true">→</span></a>
                </header>

                @forelse ($recentQuotes as $quote)
                    <a href="{{ route('reseller.quotes.show', $quote) }}" class="dashboard-list-row">
                        <span class="dashboard-row-description">
                            <strong>Orçamento #{{ $quote->getKey() }}</strong>
                            <span>{{ $quote->created_at?->format('d/m/Y') ?? '—' }} · {{ $quote->items_count }} {{ $quote->items_count === 1 ? 'item' : 'itens' }}</span>
                        </span>
                        <strong class="dashboard-row-total">R$ {{ number_format((float) $quote->total, 2, ',', '.') }}</strong>
                        <span @class(['dashboard-quote-status', 'dashboard-quote-status--converted' => $quote->order, 'dashboard-quote-status--open' => ! $quote->order])>
                            <span aria-hidden="true"></span>{{ $quote->order ? 'Convertido' : 'Em aberto' }}
                        </span>
                    </a>
                @empty
                    <div class="dashboard-list-empty">
                        <p>Nenhum orçamento registrado ainda.</p>
                        <a href="{{ route('reseller.products.index') }}">Buscar produtos por SKU</a>
                    </div>
                @endforelse
            </article>
        </section>
    </div>
</x-layouts.app>

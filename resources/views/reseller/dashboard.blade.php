<x-layouts.app title="Visão geral | Eficaz B2B" active-nav="dashboard">
    @php
        $summary = $stats ?? [];
        $recentOrders = $recentOrders ?? collect();
        $recentQuotes = $recentQuotes ?? collect();
        $monthDelta = (int) data_get($summary, 'orders_this_month', 0)
            - (int) data_get($summary, 'orders_previous_month', 0);
        $creditLimit = data_get($reseller, 'credit_limit');
        $totalInOrders = (float) data_get($summary, 'total_orders', 0);
        $committedCredit = data_get($summary, 'committed_credit');
        $availableCredit = data_get($summary, 'available_credit');
    @endphp

    <div class="reseller-dashboard">
        <header class="dashboard-mobile-heading lg:hidden">
            <h1>Visão geral</h1>
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
                    <x-icon name="package" />
                </span>
            </article>

            <article class="dashboard-stat-card">
                <div>
                    <p class="dashboard-stat-label">Orçamentos em aberto</p>
                    <p class="dashboard-stat-value">{{ data_get($summary, 'open_quotes_count', 0) }}</p>
                    <p class="dashboard-stat-hint">Aguardando conversão em pedido</p>
                </div>
                <span class="dashboard-stat-icon dashboard-stat-icon--blue" aria-hidden="true">
                    <x-icon name="file-description" />
                </span>
            </article>

            <article class="dashboard-stat-card">
                <div>
                    <p class="dashboard-stat-label">Carrinho</p>
                    <p class="dashboard-stat-value">{{ data_get($summary, 'cart_items', 0) }}</p>
                    <p class="dashboard-stat-hint">unidades no carrinho</p>
                </div>
                <span class="dashboard-stat-icon dashboard-stat-icon--cyan" aria-hidden="true">
                    <x-icon name="shopping-cart" />
                </span>
            </article>

            <article class="dashboard-stat-card">
                <div>
                    <p class="dashboard-stat-label">Crédito disponível</p>
                    <p class="dashboard-stat-value dashboard-stat-value--money">
                        {{ $availableCredit === null ? 'Não configurado' : 'R$ ' . number_format((float) $availableCredit, 2, ',', '.') }}
                    </p>
                    <p class="dashboard-stat-hint">Limite menos pedidos pendentes e aprovados</p>
                </div>
                <span class="dashboard-stat-icon dashboard-stat-icon--gold" aria-hidden="true">
                    <x-icon name="credit-card" />
                </span>
            </article>
        </section>

        <section class="dashboard-credit-panel" aria-labelledby="dashboard-credit-title">
            <div>
                <p id="dashboard-credit-title" class="dashboard-credit-label">Condições do grupo {{ ucfirst(strtolower($commercialPolicy->commercial_profile)) }}</p>
                <p class="dashboard-credit-value">
                    {{ $commercialPolicy->payment_terms ?: 'Condição de pagamento não configurada' }}
                </p>
                <p class="dashboard-credit-note">Desconto do grupo: {{ $commercialPolicy->discount_percentage }}%.
                    @if ($activePromotion)
                        Promoção atual: {{ $activePromotion->name }} ({{ $activePromotion->discount_percentage }}%).
                    @endif
                </p>
            </div>
            <div class="dashboard-credit-total">
                <span>Crédito comprometido · total em pedidos</span>
                <strong>{{ $creditLimit === null ? 'Limite não definido' : 'R$ ' . number_format((float) $committedCredit, 2, ',', '.') . ' comprometidos' }}</strong>
                <span class="mt-2 block text-xs text-slate-500">Limite: {{ $creditLimit === null ? 'não informado' : 'R$ ' . number_format((float) $creditLimit, 2, ',', '.') }} · Pedidos registrados: R$ {{ number_format($totalInOrders, 2, ',', '.') }}</span>
            </div>
        </section>

        <section class="dashboard-recent-grid" aria-label="Movimentações recentes">
            <article class="dashboard-list-panel">
                <header class="dashboard-list-heading">
                    <h2>Pedidos recentes</h2>
                    <a href="{{ route('reseller.orders.index') }}">Ver todos <span aria-hidden="true"><x-icon name="arrow-right" size="1em" /></span></a>
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
                    <a href="{{ route('reseller.cart.index') }}">Novo orçamento <span aria-hidden="true"><x-icon name="arrow-right" size="1em" /></span></a>
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

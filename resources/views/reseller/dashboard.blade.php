<x-layouts.app title="Visão geral | Eficaz B2B" active-nav="dashboard">
    @php
        $summary = $stats ?? $dashboardStats ?? [];
        $recentOrders = $recentOrders ?? [];
    @endphp

    <section class="page-heading">
        <div>
            <p class="eyebrow">Painel do revendedor</p>
            <h1 class="page-title">Visão geral da sua operação</h1>
            <p class="page-subtitle">Acompanhe os principais movimentos comerciais e avance para o catálogo quando precisar fazer uma nova solicitação.</p>
        </div>
        <a href="{{ route('reseller.products.index') }}" class="btn btn-cyan">Consultar catálogo</a>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumo da operação">
        <x-stat-card label="Itens no carrinho" :value="data_get($summary, 'cart_items', data_get($summary, 'cartItems', 0))" hint="Prontos para solicitar cotação" tone="cyan" />
        <x-stat-card label="Cotações emitidas" :value="data_get($summary, 'quotes_count', data_get($summary, 'quotesCount', 0))" hint="Histórico comercial" tone="navy" />
        <x-stat-card label="Pedidos realizados" :value="data_get($summary, 'orders_count', data_get($summary, 'ordersCount', 0))" hint="Todos os pedidos vinculados" tone="green" />
        <x-stat-card label="Total em pedidos" :value="'R$ ' . number_format((float) data_get($summary, 'total_spent', data_get($summary, 'totalSpent', 0)), 2, ',', '.')" hint="Valor acumulado" tone="amber" />
    </section>

    <section class="panel mt-6">
        <div class="panel-heading">
            <div>
                <h2 class="panel-title">Pedidos recentes</h2>
                <p class="panel-subtitle">Acompanhe rapidamente as últimas solicitações registradas.</p>
            </div>
            <a href="{{ route('reseller.orders.index') }}" class="text-sm font-bold text-cyan-700 hover:text-cyan-800">Ver todos</a>
        </div>
        @forelse ($recentOrders as $order)
            @if ($loop->first)
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Pedido</th><th>Data</th><th>Status</th><th class="text-right">Total</th><th></th></tr>
                        </thead>
                        <tbody>
            @endif
            @php $createdAt = data_get($order, 'created_at'); @endphp
            <tr>
                <td class="font-bold text-navy-950">#{{ data_get($order, 'id', '—') }}</td>
                <td>{{ $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->format('d/m/Y') : '—' }}</td>
                <td><x-status-badge :status="data_get($order, 'status', 'PENDENTE')" /></td>
                <td class="text-right font-semibold text-navy-950">R$ {{ number_format((float) data_get($order, 'total', 0), 2, ',', '.') }}</td>
                <td class="text-right"><a href="{{ route('reseller.orders.show', $order) }}" class="text-sm font-bold text-cyan-700 hover:text-cyan-800">Detalhes</a></td>
            </tr>
            @if ($loop->last)
                        </tbody>
                    </table>
                </div>
            @endif
        @empty
            <x-empty-state title="Nenhum pedido por enquanto" description="Assim que uma cotação for confirmada, o pedido ficará disponível neste painel." action-label="Consultar catálogo" :action-route="route('reseller.products.index')" />
        @endforelse
    </section>
</x-layouts.app>

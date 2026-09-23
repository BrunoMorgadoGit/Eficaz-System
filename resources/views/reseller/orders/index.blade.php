<x-layouts.app title="Pedidos | Eficaz B2B" active-nav="orders">
    @php $orderList = $orders ?? []; @endphp

    <section class="page-heading">
        <div>
            <p class="eyebrow">Acompanhamento comercial</p>
            <h1 class="page-title">Meus pedidos</h1>
            <p class="page-subtitle">Consulte o histórico e o status de cada pedido confirmado pela sua empresa.</p>
        </div>
        <a href="{{ route('reseller.products.index') }}" class="btn btn-secondary">Nova solicitação</a>
    </section>

    <section class="panel mt-6">
        @if (count($orderList))
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th>Pedido</th><th>Data</th><th>Status</th><th class="text-right">Total</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($orderList as $order)
                            @php $createdAt = data_get($order, 'created_at'); @endphp
                            <tr>
                                <td><span class="font-bold text-navy-950">#{{ data_get($order, 'id', '—') }}</span></td>
                                <td>{{ $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->format('d/m/Y') : '—' }}</td>
                                <td><x-status-badge :status="data_get($order, 'status', 'PENDENTE')" /></td>
                                <td class="text-right font-bold text-navy-950">R$ {{ number_format((float) data_get($order, 'total', 0), 2, ',', '.') }}</td>
                                <td class="text-right"><a href="{{ route('reseller.orders.show', $order) }}" class="text-sm font-bold text-cyan-700 hover:text-cyan-800">Ver pedido</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Nenhum pedido registrado" description="Quando você confirmar uma cotação, o pedido será listado aqui para acompanhamento." action-label="Consultar catálogo" :action-route="route('reseller.products.index')" />
        @endif
    </section>

    @if (is_object($orderList) && method_exists($orderList, 'links'))
        <div class="mt-6">{{ $orderList->links() }}</div>
    @endif
</x-layouts.app>

<x-layouts.app title="Meus Pedidos | Eficaz B2B" active-nav="orders">
    @php
        $orderList = $orders ?? collect();
        $activeStatus = $activeStatus ?? 'TODOS';
        $statusCounts = $statusCounts ?? [];
        $selectedOrder = $selectedOrder ?? null;
        $filters = [
            'TODOS' => 'Todos',
            'PENDENTE' => 'Pendente',
            'APROVADO' => 'Aprovado',
            'CONCLUIDO' => 'Concluído',
        ];
    @endphp

    <header class="orders-mobile-heading lg:hidden">
        <h1>Meus Pedidos</h1>
        <p>Histórico e acompanhamento de pedidos</p>
    </header>

    <div class="orders-workspace">
        <section class="orders-list-pane" aria-label="Lista de pedidos">
            <nav class="orders-status-tabs" aria-label="Filtrar pedidos por status">
                @foreach ($filters as $filterKey => $filterLabel)
                    <a
                        href="{{ route('reseller.orders.index', $filterKey === 'TODOS' ? [] : ['status' => $filterKey]) }}"
                        @class(['orders-status-tab', 'orders-status-tab--active' => $activeStatus === $filterKey])
                        aria-current="{{ $activeStatus === $filterKey ? 'page' : 'false' }}"
                    >
                        {{ $filterLabel }}
                        <span>{{ data_get($statusCounts, $filterKey, 0) }}</span>
                    </a>
                @endforeach
            </nav>

            @forelse ($orderList as $order)
                @php
                    $createdAt = data_get($order, 'created_at');
                    $selectionParams = $activeStatus === 'TODOS' ? [] : ['status' => $activeStatus];
                    $selectionParams['selected'] = data_get($order, 'id');
                @endphp
                <a
                    href="{{ route('reseller.orders.index', $selectionParams) }}"
                    @class(['orders-list-card', 'orders-list-card--selected' => (int) data_get($selectedOrder, 'id') === (int) data_get($order, 'id')])
                    aria-current="{{ (int) data_get($selectedOrder, 'id') === (int) data_get($order, 'id') ? 'true' : 'false' }}"
                >
                    <span class="orders-card-topline">
                        <strong>PED-{{ $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->format('Y') : '—' }}-{{ str_pad((string) data_get($order, 'id'), 4, '0', STR_PAD_LEFT) }}</strong>
                        <x-status-badge :status="data_get($order, 'status', 'PENDENTE')" />
                    </span>
                    <span class="orders-card-date">{{ $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->format('d/m/Y') : '—' }}</span>
                    <span class="orders-card-bottomline">
                        <span>{{ data_get($order, 'items_count', 0) }} {{ (int) data_get($order, 'items_count', 0) === 1 ? 'item' : 'itens' }}</span>
                        <strong>R$ {{ number_format((float) data_get($order, 'total', 0), 2, ',', '.') }}</strong>
                    </span>
                </a>
            @empty
                <div class="orders-list-empty">
                    <p>Nenhum pedido {{ $activeStatus === 'TODOS' ? 'registrado' : 'com este status' }}.</p>
                    <a href="{{ route('reseller.products.index') }}">Consultar produtos</a>
                </div>
            @endforelse

            @if (is_object($orderList) && method_exists($orderList, 'links'))
                <div class="orders-pagination">{{ $orderList->links() }}</div>
            @endif
        </section>

        <section class="orders-detail-pane" aria-live="polite" aria-label="Detalhes do pedido selecionado">
            @if ($selectedOrder)
                @php
                    $selectedCreatedAt = data_get($selectedOrder, 'created_at');
                    $selectedId = (int) data_get($selectedOrder, 'id');
                    $selectedNumber = 'PED-' . ($selectedCreatedAt ? \Illuminate\Support\Carbon::parse($selectedCreatedAt)->format('Y') : '—')
                        . '-' . str_pad((string) $selectedId, 4, '0', STR_PAD_LEFT);
                    $selectedItems = data_get($selectedOrder, 'items', []);
                @endphp
                <article class="orders-selected-card">
                    <header class="orders-selected-header">
                        <div>
                            <span class="orders-selected-label">Pedido</span>
                            <h2>{{ $selectedNumber }}</h2>
                        </div>
                        <x-status-badge :status="data_get($selectedOrder, 'status', 'PENDENTE')" />
                    </header>

                    <div class="orders-selected-meta">
                        <div><span>Data</span><strong>{{ $selectedCreatedAt ? \Illuminate\Support\Carbon::parse($selectedCreatedAt)->format('d/m/Y') : '—' }}</strong></div>
                        <div><span>Itens</span><strong>{{ count($selectedItems) }}</strong></div>
                    </div>

                    <h3>Itens do pedido</h3>
                    <div class="orders-selected-items">
                        @forelse ($selectedItems as $item)
                            <div class="orders-selected-item">
                                <span>
                                    <strong>{{ data_get($item, 'name', 'Produto') }}</strong>
                                    <small>SKU {{ data_get($item, 'sku', '—') }} · {{ data_get($item, 'quantity', 0) }} un.</small>
                                </span>
                                <strong>R$ {{ number_format((float) data_get($item, 'line_total', 0), 2, ',', '.') }}</strong>
                            </div>
                        @empty
                            <p>Este pedido não possui itens registrados.</p>
                        @endforelse
                    </div>

                    <div class="orders-selected-total">
                        <span>Total do pedido</span>
                        <strong>R$ {{ number_format((float) data_get($selectedOrder, 'total', 0), 2, ',', '.') }}</strong>
                    </div>

                    <a href="{{ route('reseller.orders.show', $selectedOrder) }}" class="orders-full-details">Ver detalhes do pedido</a>
                </article>
            @else
                <div class="orders-detail-placeholder">
                    <svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M9 4h10l6 6v18H9z" fill="#fff" stroke="#75869a" stroke-width="1.4"/><path d="M19 4v7h6M12 15h10M12 19h10M12 23h7" stroke="#16a7b7" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 7v21h17" stroke="#b77942" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <p>{{ count($orderList) ? 'Selecione um pedido para ver os detalhes' : 'Seus pedidos aparecerão aqui' }}</p>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>

<x-layouts.app title="Pedido | Eficaz B2B" active-nav="orders">
    @php
        $orderItems = data_get($order ?? null, 'items', []);
        $orderCreatedAt = data_get($order ?? null, 'created_at');
        $orderSubtotal = (float) data_get($order ?? null, 'subtotal', 0);
        $orderDiscount = (float) data_get($order ?? null, 'discount', 0);
        $orderTotal = (float) data_get($order ?? null, 'total', 0);
    @endphp

    <section class="page-heading module-page-heading">
        <div>
            <p class="eyebrow">Detalhe do pedido</p>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <h1 class="page-title mt-0">Pedido #{{ data_get($order ?? null, 'id', '—') }}</h1>
                <x-status-badge :status="data_get($order ?? null, 'status', 'PENDENTE')" />
            </div>
            <p class="page-subtitle">Registrado em {{ $orderCreatedAt ? \Illuminate\Support\Carbon::parse($orderCreatedAt)->format('d/m/Y \à\s H:i') : 'data indisponível' }}.</p>
        </div>
        <a href="{{ route('reseller.orders.index') }}" class="btn btn-secondary">Voltar aos pedidos</a>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2 class="panel-title">Itens do pedido</h2>
                    <p class="panel-subtitle">Este pedido preserva os produtos e preços definidos na confirmação.</p>
                </div>
            </div>
            @if (count($orderItems))
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Produto</th><th>Valor unitário</th><th>Quantidade</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            @foreach ($orderItems as $item)
                                <tr>
                                    <td>
                                        <p class="font-bold text-navy-950">{{ data_get($item, 'name', 'Produto') }}</p>
                                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">SKU {{ data_get($item, 'sku', '—') }}</p>
                                        @if (data_get($item, 'description'))
                                            <p class="mt-1 max-w-md text-xs leading-5 text-slate-500">{{ data_get($item, 'description') }}</p>
                                        @endif
                                    </td>
                                    <td>R$ {{ number_format((float) data_get($item, 'unit_price', 0), 2, ',', '.') }}</td>
                                    <td>{{ data_get($item, 'quantity', 0) }}</td>
                                    <td class="text-right font-bold text-navy-950">R$ {{ number_format((float) data_get($item, 'line_total', 0), 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state title="Pedido sem itens" description="Não foi possível localizar os itens deste pedido." />
            @endif
        </section>

        <aside class="panel h-fit p-5">
            <h2 class="panel-title">Resumo financeiro</h2>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4 text-slate-600"><dt>Subtotal</dt><dd>R$ {{ number_format($orderSubtotal, 2, ',', '.') }}</dd></div>
                <div class="flex justify-between gap-4 text-emerald-700"><dt>Desconto</dt><dd>- R$ {{ number_format($orderDiscount, 2, ',', '.') }}</dd></div>
                <div class="border-t border-slate-100 pt-3"><div class="flex justify-between gap-4 text-base font-bold text-navy-950"><dt>Total do pedido</dt><dd>R$ {{ number_format($orderTotal, 2, ',', '.') }}</dd></div></div>
            </dl>
            <div class="mt-5 rounded-xl bg-slate-50 p-3">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Status atual</p>
                <div class="mt-2"><x-status-badge :status="data_get($order ?? null, 'status', 'PENDENTE')" /></div>
            </div>
        </aside>
    </div>
</x-layouts.app>

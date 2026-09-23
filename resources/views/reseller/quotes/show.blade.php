<x-layouts.app title="Cotação | Eficaz B2B" active-nav="orders">
    @php
        $quoteItems = data_get($quote ?? null, 'items', []);
        $quoteCreatedAt = data_get($quote ?? null, 'created_at');
        $quoteSubtotal = (float) data_get($quote ?? null, 'subtotal', 0);
        $quoteDiscount = (float) data_get($quote ?? null, 'discount', 0);
        $quoteTotal = (float) data_get($quote ?? null, 'total', 0);
        $convertedOrder = data_get($quote ?? null, 'order');
    @endphp

    <section class="page-heading">
        <div>
            <p class="eyebrow">Cotação comercial</p>
            <h1 class="page-title">Cotação #{{ data_get($quote ?? null, 'id', '—') }}</h1>
            <p class="page-subtitle">Gerada em {{ $quoteCreatedAt ? \Illuminate\Support\Carbon::parse($quoteCreatedAt)->format('d/m/Y \à\s H:i') : 'data indisponível' }}. Revise os itens e confirme para criar seu pedido.</p>
        </div>
        <a href="{{ route('reseller.products.index') }}" class="btn btn-secondary">Voltar ao catálogo</a>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2 class="panel-title">Itens cotados</h2>
                    <p class="panel-subtitle">Os valores abaixo foram registrados no momento da geração desta cotação.</p>
                </div>
            </div>
            @if (count($quoteItems))
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Produto</th><th>Valor unitário</th><th>Quantidade</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            @foreach ($quoteItems as $item)
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
                <x-empty-state title="Esta cotação não tem itens" description="Volte ao catálogo e crie uma nova solicitação de cotação." action-label="Consultar catálogo" :action-route="route('reseller.products.index')" />
            @endif
        </section>

        <aside class="panel h-fit p-5">
            <h2 class="panel-title">Resumo comercial</h2>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4 text-slate-600"><dt>Subtotal</dt><dd>R$ {{ number_format($quoteSubtotal, 2, ',', '.') }}</dd></div>
                <div class="flex justify-between gap-4 text-emerald-700"><dt>Desconto comercial</dt><dd>- R$ {{ number_format($quoteDiscount, 2, ',', '.') }}</dd></div>
                <div class="border-t border-slate-100 pt-3"><div class="flex justify-between gap-4 text-base font-bold text-navy-950"><dt>Total</dt><dd>R$ {{ number_format($quoteTotal, 2, ',', '.') }}</dd></div></div>
            </dl>
            <p class="mt-5 rounded-xl bg-cyan-50 p-3 text-xs leading-5 text-navy-800">Ao confirmar, esta cotação será convertida em um pedido para acompanhamento da operação.</p>
            @if ($convertedOrder)
                <div class="mt-5 rounded-xl bg-emerald-50 p-3 text-sm leading-5 text-emerald-800">
                    Esta cotação já foi convertida em pedido.
                </div>
                <a href="{{ route('reseller.orders.show', $convertedOrder) }}" class="btn btn-secondary mt-4 w-full">Ver pedido #{{ data_get($convertedOrder, 'id') }}</a>
            @elseif (count($quoteItems))
                <form method="POST" action="{{ route('reseller.orders.store', $quote) }}" class="mt-5">
                    @csrf
                    <button type="submit" class="btn btn-cyan w-full">Confirmar pedido</button>
                </form>
            @endif
        </aside>
    </div>
</x-layouts.app>

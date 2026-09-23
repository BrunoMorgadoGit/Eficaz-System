<x-layouts.app title="Meu carrinho | Eficaz B2B" active-nav="cart">
    @php
        $cartSummary = $summary ?? [];
        $cartItems = data_get($cartSummary, 'items', data_get($cart ?? null, 'items', []));
        $calculatedSubtotal = 0;
        foreach ($cartItems as $cartItem) {
            $calculatedSubtotal += (float) data_get($cartItem, 'unit_price', data_get($cartItem, 'product.price', 0)) * (int) data_get($cartItem, 'quantity', 0);
        }
        $cartSubtotal = (float) data_get($cartSummary, 'subtotal', $calculatedSubtotal);
        $cartDiscount = (float) data_get($cartSummary, 'discount', 0);
        $cartTotal = (float) data_get($cartSummary, 'total', $cartSubtotal - $cartDiscount);
        $discountRate = (int) data_get($cartSummary, 'discount_rate', 0);
        $itemCount = (int) data_get($cartSummary, 'item_count', count($cartItems));
    @endphp

    <section class="page-heading">
        <div>
            <p class="eyebrow">Solicitação de cotação</p>
            <h1 class="page-title">Meu carrinho</h1>
            <p class="page-subtitle">Revise quantidades e valores. Ao solicitar a cotação, os itens serão registrados para acompanhamento comercial.</p>
        </div>
        <a href="{{ route('reseller.products.index') }}" class="btn btn-secondary">Continuar comprando</a>
    </section>

    @if (count($cartItems))
        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2 class="panel-title">Itens selecionados</h2>
                        <p class="panel-subtitle">As alterações de quantidade são aplicadas antes de gerar a cotação.</p>
                    </div>
                </div>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Produto</th><th>Valor unitário</th><th>Quantidade</th><th class="text-right">Subtotal</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($cartItems as $item)
                                @php
                                    $product = data_get($item, 'product');
                                    $unitPrice = (float) data_get($item, 'unit_price', data_get($product, 'price', 0));
                                    $quantity = (int) data_get($item, 'quantity', 1);
                                @endphp
                                <tr>
                                    <td>
                                        <p class="font-bold text-navy-950">{{ data_get($product, 'name', 'Produto') }}</p>
                                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">SKU {{ data_get($product, 'sku', '—') }}</p>
                                    </td>
                                    <td>R$ {{ number_format($unitPrice, 2, ',', '.') }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('reseller.cart.items.update', $item) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <label class="sr-only" for="quantity-{{ data_get($item, 'id') }}">Quantidade</label>
                                            <input id="quantity-{{ data_get($item, 'id') }}" type="number" name="quantity" min="1" value="{{ $quantity }}" class="field-control w-18 py-2">
                                            <button type="submit" class="text-xs font-bold text-cyan-700 hover:text-cyan-800">Atualizar</button>
                                        </form>
                                    </td>
                                    <td class="text-right font-bold text-navy-950">R$ {{ number_format($unitPrice * $quantity, 2, ',', '.') }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('reseller.cart.items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-800">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="panel h-fit p-5">
                <h2 class="panel-title">Resumo da solicitação</h2>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4 text-slate-600"><dt>Itens</dt><dd>{{ $itemCount }}</dd></div>
                    <div class="flex justify-between gap-4 text-slate-600"><dt>Subtotal estimado</dt><dd>R$ {{ number_format($cartSubtotal, 2, ',', '.') }}</dd></div>
                    <div class="flex justify-between gap-4 text-emerald-700"><dt>Desconto comercial{{ $discountRate ? ' (' . $discountRate . '%)' : '' }}</dt><dd>- R$ {{ number_format($cartDiscount, 2, ',', '.') }}</dd></div>
                    <div class="border-t border-slate-100 pt-3"><div class="flex justify-between gap-4 text-base font-bold text-navy-950"><dt>Total estimado</dt><dd>R$ {{ number_format($cartTotal, 2, ',', '.') }}</dd></div></div>
                </dl>
                <p class="mt-5 rounded-xl bg-cyan-50 p-3 text-xs leading-5 text-navy-800">Os valores são estimados com base no seu perfil comercial e serão preservados na cotação.</p>
                <form method="POST" action="{{ route('reseller.quotes.store') }}" class="mt-5">
                    @csrf
                    <button type="submit" class="btn btn-cyan w-full">Solicitar cotação</button>
                </form>
            </aside>
        </div>
    @else
        <section class="panel mt-6">
            <x-empty-state title="Seu carrinho está vazio" description="Adicione itens do catálogo para iniciar uma solicitação de cotação." action-label="Ir para o catálogo" :action-route="route('reseller.products.index')" />
        </section>
    @endif
</x-layouts.app>

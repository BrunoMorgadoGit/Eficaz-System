<x-layouts.app title="Carrinho | Eficaz B2B" active-nav="cart">
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
        $productCount = count($cartItems);
    @endphp

    <section class="cart-content">
        <div class="cart-mobile-heading">
            <div>
                <h1>Carrinho</h1>
                <p>{{ $productCount }} {{ $productCount === 1 ? 'produto selecionado' : 'produtos selecionados' }}</p>
            </div>
            <a href="{{ route('reseller.products.index') }}">+ Adicionar produtos</a>
        </div>

        @if ($productCount > 0)
            <div class="cart-layout">
                <section class="cart-items-panel" aria-label="Produtos selecionados">
                    <div class="cart-table-wrap">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th scope="col">Produto / SKU</th>
                                    <th scope="col">Qtd</th>
                                    <th scope="col">Preço unit.</th>
                                    <th scope="col">Desconto</th>
                                    <th scope="col">Subtotal</th>
                                    <th scope="col"><span class="sr-only">Ações</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cartItems as $item)
                                    @php
                                        $product = data_get($item, 'product');
                                        $unitPrice = (float) data_get($item, 'unit_price', data_get($product, 'price', 0));
                                        $quantity = (int) data_get($item, 'quantity', 1);
                                        $lineSubtotal = $unitPrice * $quantity;
                                        $itemId = data_get($item, 'id');
                                    @endphp
                                    <tr>
                                        <td class="cart-product-cell">
                                            <span class="cart-product-name">{{ data_get($product, 'name', 'Produto') }}</span>
                                            <span class="cart-product-sku">{{ data_get($product, 'sku', '—') }}</span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('reseller.cart.items.update', $item) }}" class="cart-quantity-form">
                                                @csrf
                                                @method('PATCH')
                                                <label class="sr-only" for="quantity-{{ $itemId }}">Quantidade de {{ data_get($product, 'name', 'produto') }}</label>
                                                <input id="quantity-{{ $itemId }}" name="quantity" type="number" min="1" max="{{ data_get($product, 'stock') }}" value="{{ $quantity }}" class="cart-quantity-input">
                                                <button type="submit" class="cart-update-button">Atualizar</button>
                                            </form>
                                        </td>
                                        <td class="cart-money-cell">R$ {{ number_format($unitPrice, 2, ',', '.') }}</td>
                                        <td class="cart-discount-cell">{{ $discountRate }}%</td>
                                        <td class="cart-line-total">R$ {{ number_format($lineSubtotal, 2, ',', '.') }}</td>
                                        <td class="cart-remove-cell">
                                            <form method="POST" action="{{ route('reseller.cart.items.destroy', $item) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cart-remove-button">Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <aside class="cart-summary-panel" aria-labelledby="cart-summary-title">
                    <h2 id="cart-summary-title">Resumo do Pedido</h2>
                    <dl class="cart-summary-lines">
                        <div>
                            <dt>Subtotal</dt>
                            <dd>R$ {{ number_format($cartSubtotal, 2, ',', '.') }}</dd>
                        </div>
                        @if ($cartDiscount > 0)
                            <div class="cart-summary-discount">
                                <dt>Desconto do perfil ({{ $discountRate }}%)</dt>
                                <dd>-R$ {{ number_format($cartDiscount, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        <div class="cart-summary-total">
                            <dt>Total</dt>
                            <dd>R$ {{ number_format($cartTotal, 2, ',', '.') }}</dd>
                        </div>
                    </dl>
                    <form method="POST" action="{{ route('reseller.quotes.store') }}" class="cart-quote-form">
                        @csrf
                        <button type="submit" class="cart-quote-button">Gerar Orçamento</button>
                    </form>
                    <p class="cart-summary-note">O orçamento será enviado para aprovação.</p>
                </aside>
            </div>
        @else
            <section class="cart-empty-panel">
                <div class="cart-empty-icon" aria-hidden="true">🛒</div>
                <h2>Seu carrinho está vazio</h2>
                <p>Adicione produtos do catálogo para montar seu próximo orçamento.</p>
                <a href="{{ route('reseller.products.index') }}" class="cart-quote-button">Adicionar produtos</a>
            </section>
        @endif
    </section>
</x-layouts.app>

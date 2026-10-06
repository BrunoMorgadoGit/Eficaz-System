<x-layouts.app title="Orçamento | Eficaz B2B" active-nav="quotes">
    @php
        $quoteItems = data_get($quote, 'items', []);
        $quoteId = (int) data_get($quote, 'id', 0);
        $quoteCreatedAt = data_get($quote, 'created_at');
        $quoteSubtotal = (float) data_get($quote, 'subtotal', 0);
        $quoteDiscount = (float) data_get($quote, 'discount', 0);
        $quoteTotal = (float) data_get($quote, 'total', 0);
        $convertedOrder = data_get($quote, 'order');
        $quoteNumber = 'ORC-' . ($quoteCreatedAt ? \Illuminate\Support\Carbon::parse($quoteCreatedAt)->format('Y') : '—')
            . '-' . str_pad((string) $quoteId, 4, '0', STR_PAD_LEFT);
    @endphp

    <div class="quote-mobile-heading lg:hidden">
        <h1>Orçamento</h1>
        <p>Revise os itens e converta em pedido</p>
    </div>

    <div class="quote-layout">
        <div class="quote-main-column">
            <section class="quote-meta-panel" aria-label="Dados do orçamento">
                <div class="quote-meta-item">
                    <span>Número</span>
                    <strong class="quote-number">{{ $quoteNumber }}</strong>
                </div>
                <div class="quote-meta-item">
                    <span>Data</span>
                    <strong>{{ $quoteCreatedAt ? \Illuminate\Support\Carbon::parse($quoteCreatedAt)->format('d/m/Y') : '—' }}</strong>
                </div>
                <div class="quote-meta-item">
                    <span>Status</span>
                    <span @class(['quote-state', 'quote-state--converted' => $convertedOrder, 'quote-state--open' => ! $convertedOrder])>
                        <span aria-hidden="true"></span>{{ $convertedOrder ? 'Convertido' : 'Em aberto' }}
                    </span>
                </div>
                <div class="quote-meta-item">
                    <span>Itens</span>
                    <strong>{{ count($quoteItems) }}</strong>
                </div>
            </section>

            <section class="quote-items-panel" aria-labelledby="quote-items-title">
                <header class="quote-items-heading">
                    <h2 id="quote-items-title">Itens do orçamento</h2>
                </header>

                @if (count($quoteItems))
                    <div class="quote-items-table-wrap">
                        <table class="quote-items-table">
                            <thead>
                                <tr>
                                    <th scope="col">SKU</th>
                                    <th scope="col">Produto</th>
                                    <th scope="col">Qtd.</th>
                                    <th scope="col">Preço unit.</th>
                                    <th scope="col">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($quoteItems as $item)
                                    <tr>
                                        <td class="quote-item-sku">{{ data_get($item, 'sku', '—') }}</td>
                                        <td>
                                            <strong class="quote-item-name">{{ data_get($item, 'name', 'Produto') }}</strong>
                                            @if (data_get($item, 'description'))
                                                <span class="quote-item-description">{{ data_get($item, 'description') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ data_get($item, 'quantity', 0) }}</td>
                                        <td class="quote-item-price">R$ {{ number_format((float) data_get($item, 'unit_price', 0), 2, ',', '.') }}</td>
                                        <td class="quote-item-total">R$ {{ number_format((float) data_get($item, 'line_total', 0), 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="quote-empty-state">
                        <p>Este orçamento não possui itens.</p>
                        <a href="{{ route('reseller.products.index') }}">Voltar ao catálogo</a>
                    </div>
                @endif
            </section>
        </div>

        <aside class="quote-summary-panel" aria-labelledby="quote-summary-title">
            <h2 id="quote-summary-title">Resumo do Orçamento</h2>
            <dl class="quote-summary-lines">
                <div>
                    <dt>Subtotal</dt>
                    <dd>R$ {{ number_format($quoteSubtotal, 2, ',', '.') }}</dd>
                </div>
                <div class="quote-savings">
                    <dt>Economia</dt>
                    <dd>- R$ {{ number_format($quoteDiscount, 2, ',', '.') }}</dd>
                </div>
                <div class="quote-grand-total">
                    <dt>Total</dt>
                    <dd>R$ {{ number_format($quoteTotal, 2, ',', '.') }}</dd>
                </div>
            </dl>

            @if ($convertedOrder)
                <p class="quote-converted-note">Este orçamento já foi convertido em pedido.</p>
                <a href="{{ route('reseller.orders.show', $convertedOrder) }}" class="quote-primary-action">Ver pedido #{{ data_get($convertedOrder, 'id') }}</a>
            @elseif (count($quoteItems))
                <form method="POST" action="{{ route('reseller.orders.store', $quote) }}">
                    @csrf
                    <button type="submit" class="quote-primary-action">Converter em pedido</button>
                </form>
            @endif

            <a href="{{ route('reseller.cart.index') }}" class="quote-back-action">Voltar ao Carrinho</a>
        </aside>
    </div>
</x-layouts.app>

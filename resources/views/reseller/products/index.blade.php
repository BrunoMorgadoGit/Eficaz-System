<x-layouts.app title="Catálogo de Produtos | Eficaz B2B" active-nav="products">
    @php
        $catalogProducts = $products ?? [];
        $search = $searchedSku ?? request('sku', '');
        $searchFeedback = $searchFeedback ?? null;
        $resultCount = is_object($catalogProducts) && method_exists($catalogProducts, 'total')
            ? $catalogProducts->total()
            : count($catalogProducts);
    @endphp

    <div class="catalog-mobile-heading lg:hidden">
        <h1>Catálogo de Produtos</h1>
        <p>Busque pelo SKU do produto</p>
    </div>

    <form method="GET" action="{{ route('reseller.products.index') }}" class="catalog-toolbar">
        <div class="catalog-search-wrap">
            <x-icon name="search" class="catalog-search-icon" stroke-width="1.8" />
            <label for="catalog-search" class="sr-only">Buscar produto pelo SKU</label>
            <input id="catalog-search" name="sku" value="{{ $search }}" class="catalog-search-input" placeholder="Buscar por SKU...">
            <button type="submit" class="sr-only">Buscar</button>
        </div>
        <p class="catalog-result-count" aria-live="polite">{{ $resultCount }} {{ $resultCount === 1 ? 'produto encontrado' : 'produtos encontrados' }}</p>
        @if ($search !== '')
            <a href="{{ route('reseller.products.index') }}" class="catalog-clear-search">Limpar busca</a>
        @endif
    </form>

    @if ($searchFeedback)
        <p class="catalog-search-feedback" role="status">{{ $searchFeedback }}</p>
    @endif

    @if (count($catalogProducts))
        <section class="catalog-table-panel" aria-label="Produtos encontrados">
            <div class="catalog-table-wrap">
                <table class="catalog-table">
                    <thead>
                        <tr>
                            <th scope="col">SKU</th>
                            <th scope="col">Produto</th>
                            <th scope="col">Descrição</th>
                            <th scope="col">Preço unit.</th>
                            <th scope="col">Estoque</th>
                            <th scope="col">Status</th>
                            <th scope="col">Qtd.</th>
                            <th scope="col"><span class="sr-only">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($catalogProducts as $product)
                            @php
                                $stock = (int) data_get($product, 'stock', 0);
                                $isActive = (bool) data_get($product, 'is_active', false);
                                $isAvailable = $isActive && $stock > 0;
                            @endphp
                            <tr>
                                <td class="catalog-sku">{{ data_get($product, 'sku', '—') }}</td>
                                <td class="catalog-product-name">{{ data_get($product, 'name', 'Produto') }}</td>
                                <td class="catalog-description">{{ data_get($product, 'description') ?: '—' }}</td>
                                <td class="catalog-price">R$ {{ number_format((float) data_get($product, 'price', 0), 2, ',', '.') }}</td>
                                <td>
                                    @if ($stock > 0)
                                        <span class="catalog-stock catalog-stock--available">{{ number_format($stock, 0, ',', '.') }}</span>
                                    @else
                                        <span class="catalog-stock catalog-stock--empty">Esgotado</span>
                                    @endif
                                </td>
                                <td>
                                    <span @class(['catalog-status', 'catalog-status--active' => $isActive, 'catalog-status--inactive' => ! $isActive])>
                                        <span aria-hidden="true"></span>{{ $isActive ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td>
                                    <form id="add-to-cart-{{ data_get($product, 'id') }}" method="POST" action="{{ route('reseller.cart.items.store') }}" class="catalog-add-form">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ data_get($product, 'id') }}">
                                        <label class="sr-only" for="quantity-{{ data_get($product, 'id') }}">Quantidade de {{ data_get($product, 'name', 'produto') }}</label>
                                        <input
                                            id="quantity-{{ data_get($product, 'id') }}"
                                            type="number"
                                            name="quantity"
                                            min="1"
                                            max="{{ max($stock, 1) }}"
                                            value="1"
                                            class="catalog-quantity"
                                            {{ $isAvailable ? '' : 'disabled' }}
                                        >
                                    </form>
                                </td>
                                <td>
                                    @if ($isAvailable)
                                        <button type="submit" form="add-to-cart-{{ data_get($product, 'id') }}" class="catalog-add-button"><x-icon name="plus" width="1em" height="1em" class="inline-block align-middle" /> Carrinho</button>
                                    @else
                                        <button type="button" class="catalog-add-button" disabled>Indisponível</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="catalog-mobile-list">
                @foreach ($catalogProducts as $product)
                    @php
                        $stock = (int) data_get($product, 'stock', 0);
                        $isActive = (bool) data_get($product, 'is_active', false);
                        $isAvailable = $isActive && $stock > 0;
                    @endphp
                    <article class="catalog-mobile-card" aria-labelledby="mobile-product-{{ data_get($product, 'id') }}">
                        <header class="catalog-mobile-card-header">
                            <div>
                                <p class="catalog-mobile-sku">SKU {{ data_get($product, 'sku', '—') }}</p>
                                <h2 id="mobile-product-{{ data_get($product, 'id') }}">{{ data_get($product, 'name', 'Produto') }}</h2>
                            </div>
                            <span @class(['catalog-status', 'catalog-status--active' => $isActive, 'catalog-status--inactive' => ! $isActive])>
                                <span aria-hidden="true"></span>{{ $isActive ? 'Ativo' : 'Inativo' }}
                            </span>
                        </header>

                        @if (data_get($product, 'description'))
                            <p class="catalog-mobile-description">{{ data_get($product, 'description') }}</p>
                        @endif

                        <dl class="catalog-mobile-facts">
                            <div>
                                <dt>Preço unitário</dt>
                                <dd>R$ {{ number_format((float) data_get($product, 'price', 0), 2, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt>Estoque</dt>
                                <dd @class(['catalog-stock--available' => $stock > 0, 'catalog-stock--empty' => $stock <= 0])>
                                    {{ $stock > 0 ? number_format($stock, 0, ',', '.') . ' unidades' : 'Esgotado' }}
                                </dd>
                            </div>
                        </dl>

                        <form method="POST" action="{{ route('reseller.cart.items.store') }}" class="catalog-mobile-action">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ data_get($product, 'id') }}">
                            <label for="quantity-mobile-{{ data_get($product, 'id') }}">Quantidade</label>
                            <div>
                                <input
                                    id="quantity-mobile-{{ data_get($product, 'id') }}"
                                    type="number"
                                    name="quantity"
                                    min="1"
                                    max="{{ max($stock, 1) }}"
                                    value="1"
                                    class="catalog-quantity"
                                    {{ $isAvailable ? '' : 'disabled' }}
                                >
                                <button type="{{ $isAvailable ? 'submit' : 'button' }}" class="catalog-add-button" {{ $isAvailable ? '' : 'disabled' }}>
                                    {{ $isAvailable ? 'Adicionar ao carrinho' : 'Indisponível' }}
                                </button>
                            </div>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>
    @else
        <section class="catalog-empty-state">
            <h2>{{ $search !== '' ? 'Nenhum produto encontrado' : 'Catálogo sem produtos ativos' }}</h2>
            <p>{{ $search !== '' ? 'Não localizamos um item com esse SKU. Confira o código e tente novamente.' : 'Quando produtos estiverem disponíveis para seu perfil, eles aparecerão aqui.' }}</p>
        </section>
    @endif

    @if (is_object($catalogProducts) && method_exists($catalogProducts, 'links'))
        <div class="catalog-pagination">{{ $catalogProducts->withQueryString()->links() }}</div>
    @endif
</x-layouts.app>

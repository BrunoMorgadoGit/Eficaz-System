<x-layouts.app title="Catálogo | Eficaz B2B" active-nav="products">
    @php
        $catalogProducts = $products ?? [];
        $search = $searchedSku ?? request('sku', '');
        $searchFeedback = $searchFeedback ?? null;
    @endphp

    <section class="page-heading">
        <div>
            <p class="eyebrow">Catálogo industrial</p>
            <h1 class="page-title">Encontre produtos para sua operação</h1>
            <p class="page-subtitle">Busque pelo SKU do item e adicione ao carrinho para organizar a próxima solicitação de cotação.</p>
        </div>
        <a href="{{ route('reseller.cart.index') }}" class="btn btn-secondary">Ver carrinho</a>
    </section>

    <form method="GET" action="{{ route('reseller.products.index') }}" class="panel mt-6 p-4 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <label for="search" class="sr-only">Buscar por SKU</label>
                <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 fill-none stroke-slate-400" stroke-width="2"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4" stroke-linecap="round"/></svg>
                <input id="search" name="sku" value="{{ $search }}" class="field-control pl-10" placeholder="Buscar pelo SKU do produto">
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
            @if ($search !== '')
                <a href="{{ route('reseller.products.index') }}" class="btn btn-secondary">Limpar</a>
            @endif
        </div>
        @if ($searchFeedback)
            <p class="field-help text-amber-700">{{ $searchFeedback }}</p>
        @elseif ($search !== '')
            <p class="field-help">Resultado da busca por <strong class="font-semibold text-navy-900">{{ $search }}</strong>.</p>
        @endif
    </form>

    @if (count($catalogProducts))
        <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3" aria-label="Produtos encontrados">
            @foreach ($catalogProducts as $product)
                @php
                    $stock = (int) data_get($product, 'stock', 0);
                    $isAvailable = (bool) data_get($product, 'is_active', true) && $stock > 0;
                @endphp
                <article class="panel flex flex-col p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-cyan-700">SKU {{ data_get($product, 'sku', '—') }}</p>
                            <h2 class="mt-2 text-lg font-bold tracking-tight text-navy-950">{{ data_get($product, 'name', 'Produto') }}</h2>
                        </div>
                        @if ($isAvailable)
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Em estoque</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Indisponível</span>
                        @endif
                    </div>
                    <p class="mt-3 min-h-12 text-sm leading-6 text-slate-500">{{ data_get($product, 'description', 'Sem descrição disponível para este item.') }}</p>
                    <div class="mt-5 flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
                        <div>
                            <p class="text-xs text-slate-500">Preço unitário</p>
                            <p class="mt-1 text-lg font-bold text-navy-950">R$ {{ number_format((float) data_get($product, 'price', 0), 2, ',', '.') }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $stock }} unidade(s) disponível(is)</p>
                        </div>
                        <form method="POST" action="{{ route('reseller.cart.items.store') }}" class="flex items-end gap-2">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ data_get($product, 'id') }}">
                            <label class="sr-only" for="quantity-{{ data_get($product, 'id') }}">Quantidade</label>
                            <input id="quantity-{{ data_get($product, 'id') }}" type="number" name="quantity" min="1" max="{{ max($stock, 1) }}" value="1" class="field-control w-18 py-2" {{ $isAvailable ? '' : 'disabled' }}>
                            <button type="submit" class="btn btn-cyan px-3" {{ $isAvailable ? '' : 'disabled' }}>Adicionar</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </section>
    @else
        <section class="panel mt-6">
            <x-empty-state :title="$search !== '' ? 'Nenhum produto encontrado' : 'Catálogo sem produtos ativos'" :description="$search !== '' ? 'Não localizamos um item com esse SKU. Confira o código e tente novamente.' : 'Quando produtos estiverem disponíveis para seu perfil, eles aparecerão aqui.'" />
        </section>
    @endif

    @if (is_object($catalogProducts) && method_exists($catalogProducts, 'links'))
        <div class="mt-6">{{ $catalogProducts->withQueryString()->links() }}</div>
    @endif
</x-layouts.app>

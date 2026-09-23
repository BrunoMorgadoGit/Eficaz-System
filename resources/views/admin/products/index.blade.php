<x-layouts.app title="Catálogo | Administração" active-nav="products">
    @php $productList = $products ?? []; @endphp

    <section class="page-heading">
        <div>
            <p class="eyebrow">Administração do catálogo</p>
            <h1 class="page-title">Produtos</h1>
            <p class="page-subtitle">Cadastre e mantenha os itens disponíveis para as operações dos revendedores.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-cyan">Novo produto</a>
    </section>

    <section class="panel mt-6">
        @if (count($productList))
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th>SKU</th><th>Produto</th><th>Preço</th><th>Estoque</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($productList as $product)
                            <tr>
                                <td><span class="font-bold text-cyan-700">{{ data_get($product, 'sku', '—') }}</span></td>
                                <td>
                                    <p class="font-bold text-navy-950">{{ data_get($product, 'name', 'Produto') }}</p>
                                    <p class="mt-1 max-w-sm truncate text-xs text-slate-500">{{ data_get($product, 'description', 'Sem descrição') }}</p>
                                </td>
                                <td>R$ {{ number_format((float) data_get($product, 'price', 0), 2, ',', '.') }}</td>
                                <td>{{ data_get($product, 'stock', 0) }}</td>
                                <td><x-status-badge :status="data_get($product, 'is_active', false) ? 'active' : 'inactive'" /></td>
                                <td class="text-right"><a href="{{ route('admin.products.edit', $product) }}" class="text-sm font-bold text-cyan-700 hover:text-cyan-800">Editar</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Nenhum produto cadastrado" description="Cadastre o primeiro item para disponibilizá-lo no catálogo dos revendedores." action-label="Cadastrar produto" :action-route="route('admin.products.create')" />
        @endif
    </section>

    @if (is_object($productList) && method_exists($productList, 'links'))
        <div class="mt-6">{{ $productList->links() }}</div>
    @endif
</x-layouts.app>

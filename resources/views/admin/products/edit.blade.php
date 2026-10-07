<x-layouts.app title="Editar produto | Administração" active-nav="products">
    <section class="page-heading module-page-heading">
        <div>
            <p class="eyebrow">Administração do catálogo</p>
            <h1 class="page-title">Editar produto</h1>
            <p class="page-subtitle">Atualize as informações de {{ data_get($product ?? null, 'name', 'produto') }} sem alterar os registros comerciais já emitidos.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Voltar ao catálogo</a>
    </section>

    <div class="mt-6">
        @include('admin.products._form', [
            'product' => $product ?? null,
            'action' => route('admin.products.update', $product),
            'method' => 'PATCH',
            'submitLabel' => 'Salvar alterações',
        ])
    </div>
</x-layouts.app>

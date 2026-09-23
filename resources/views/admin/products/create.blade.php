<x-layouts.app title="Novo produto | Administração" active-nav="products">
    <section class="page-heading">
        <div>
            <p class="eyebrow">Administração do catálogo</p>
            <h1 class="page-title">Cadastrar produto</h1>
            <p class="page-subtitle">Informe os dados que ficarão visíveis aos revendedores no catálogo B2B.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Voltar ao catálogo</a>
    </section>

    <div class="mt-6">
        @include('admin.products._form', [
            'product' => null,
            'action' => route('admin.products.store'),
            'method' => 'POST',
            'submitLabel' => 'Cadastrar produto',
        ])
    </div>
</x-layouts.app>

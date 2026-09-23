@php
    $currentProduct = $product ?? null;
    $isProductActive = (bool) old('is_active', data_get($currentProduct, 'is_active', true));
@endphp

<form method="POST" action="{{ $action }}" class="panel p-5 sm:p-6">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label for="sku" class="field-label">SKU</label>
            <input id="sku" name="sku" value="{{ old('sku', data_get($currentProduct, 'sku')) }}" required maxlength="80" class="field-control" placeholder="EX.: IND-0001">
            <x-input-error :messages="$errors->get('sku')" />
        </div>
        <div>
            <label for="name" class="field-label">Nome do produto</label>
            <input id="name" name="name" value="{{ old('name', data_get($currentProduct, 'name')) }}" required maxlength="255" class="field-control" placeholder="Nome comercial do item">
            <x-input-error :messages="$errors->get('name')" />
        </div>
        <div class="md:col-span-2">
            <label for="description" class="field-label">Descrição</label>
            <textarea id="description" name="description" rows="4" maxlength="2000" class="field-control resize-y" placeholder="Características relevantes para o revendedor">{{ old('description', data_get($currentProduct, 'description')) }}</textarea>
            <x-input-error :messages="$errors->get('description')" />
        </div>
        <div>
            <label for="price" class="field-label">Preço unitário (R$)</label>
            <input id="price" name="price" type="number" min="0" step="0.01" value="{{ old('price', data_get($currentProduct, 'price')) }}" required class="field-control" placeholder="0,00">
            <x-input-error :messages="$errors->get('price')" />
        </div>
        <div>
            <label for="stock" class="field-label">Estoque disponível</label>
            <input id="stock" name="stock" type="number" min="0" step="1" value="{{ old('stock', data_get($currentProduct, 'stock', 0)) }}" required class="field-control" placeholder="0">
            <x-input-error :messages="$errors->get('stock')" />
        </div>
    </div>

    <label class="mt-6 flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked($isProductActive) class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
        <span><span class="font-bold text-navy-950">Produto ativo no catálogo</span><span class="mt-0.5 block text-xs text-slate-500">Somente itens ativos poderão ser incluídos pelo revendedor.</span></span>
    </label>
    <x-input-error :messages="$errors->get('is_active')" />

    <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-cyan">{{ $submitLabel }}</button>
    </div>
</form>

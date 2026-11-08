<x-layouts.app title="Editar revendedor | Administração" active-nav="resellers">
    <section class="page-heading module-page-heading">
        <div>
            <p class="eyebrow">Rede comercial</p>
            <h1 class="page-title">Condições do revendedor</h1>
            <p class="page-subtitle">{{ $reseller->company_name }} · {{ $reseller->user?->email }}</p>
        </div>
        <a href="{{ route('admin.resellers.index') }}" class="btn btn-secondary">Voltar para revendedores</a>
    </section>

    <form method="POST" action="{{ route('admin.resellers.update', $reseller) }}" class="panel mt-6 grid gap-5 p-5 sm:p-7">
        @csrf
        @method('PATCH')

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="company_name" class="field-label">Empresa</label>
                <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $reseller->company_name) }}" class="field-control" maxlength="255" required>
                <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
            </div>

            <div>
                <label for="commercial_profile" class="field-label">Grupo comercial</label>
                <select id="commercial_profile" name="commercial_profile" class="field-control" required>
                    @foreach ([\App\Models\Reseller::PROFILE_STANDARD => 'Standard', \App\Models\Reseller::PROFILE_GOLD => 'Gold', \App\Models\Reseller::PROFILE_PREMIUM => 'Premium'] as $profile => $label)
                        <option value="{{ $profile }}" @selected(old('commercial_profile', $reseller->commercial_profile) === $profile)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="field-help">O grupo define o desconto comercial atualmente configurado no portal.</p>
                <x-input-error :messages="$errors->get('commercial_profile')" class="mt-2" />
            </div>

            <div>
                <label for="credit_limit" class="field-label">Limite de crédito (R$)</label>
                <input id="credit_limit" name="credit_limit" type="number" min="0" step="0.01" value="{{ old('credit_limit', $reseller->credit_limit) }}" class="field-control" placeholder="Deixe vazio se não definido">
                <p class="field-help">Esse valor será aplicado à regra de crédito definida para os pedidos.</p>
                <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5">
            <a href="{{ route('admin.resellers.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-brand">Salvar condições</button>
        </div>
    </form>
</x-layouts.app>

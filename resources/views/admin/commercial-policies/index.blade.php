<x-layouts.app title="Condições comerciais | Administração" active-nav="commercial-policies">
    <section class="page-heading module-page-heading">
        <div>
            <p class="eyebrow">Regras por grupo</p>
            <h1 class="page-title">Condições comerciais</h1>
            <p class="page-subtitle">Configure descontos e condições de pagamento que serão congelados nos novos orçamentos.</p>
        </div>
        <a href="{{ route('admin.promotions.index') }}" class="btn btn-secondary">Gerenciar promoções</a>
    </section>

    <section class="mt-6 grid gap-5">
        @foreach ($policies as $policy)
            <form method="POST" action="{{ route('admin.commercial-policies.update', $policy) }}" class="panel grid gap-5 p-5 sm:p-7">
                @csrf
                @method('PATCH')

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="panel-title">Grupo {{ ucfirst(strtolower($policy->commercial_profile)) }}</h2>
                        <p class="panel-subtitle">Atribuído aos revendedores na edição de perfil comercial.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold tracking-wide text-slate-700">{{ $policy->commercial_profile }}</span>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="discount_percentage_{{ $policy->id }}" class="field-label">Desconto do grupo (%)</label>
                        <input id="discount_percentage_{{ $policy->id }}" name="discount_percentage" type="number" min="0" max="100" step="1" value="{{ old('discount_percentage', $policy->discount_percentage) }}" class="field-control" required>
                        <x-input-error :messages="$errors->get('discount_percentage')" class="mt-2" />
                    </div>
                    <div>
                        <label for="payment_terms_{{ $policy->id }}" class="field-label">Condição de pagamento</label>
                        <input id="payment_terms_{{ $policy->id }}" name="payment_terms" type="text" maxlength="255" value="{{ old('payment_terms', $policy->payment_terms) }}" class="field-control" placeholder="Ex.: definir com a equipe comercial">
                        <p class="field-help">Sem uma condição cadastrada, o revendedor poderá gerar orçamento, mas não emitir pedido.</p>
                        <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-200 pt-4">
                    <button type="submit" class="btn btn-brand">Salvar grupo</button>
                </div>
            </form>
        @endforeach
    </section>
</x-layouts.app>

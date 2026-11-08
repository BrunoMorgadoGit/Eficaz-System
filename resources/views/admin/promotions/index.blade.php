<x-layouts.app title="Promoções | Administração" active-nav="promotions">
    @php
        $profileLabels = [
            \App\Models\Reseller::PROFILE_STANDARD => 'Standard',
            \App\Models\Reseller::PROFILE_GOLD => 'Gold',
            \App\Models\Reseller::PROFILE_PREMIUM => 'Premium',
        ];
    @endphp

    <section class="page-heading module-page-heading">
        <div>
            <p class="eyebrow">Campanhas por grupo</p>
            <h1 class="page-title">Promoções</h1>
            <p class="page-subtitle">Cadastre ofertas temporárias para um grupo. Nenhuma promoção fica ativa por padrão.</p>
        </div>
        <a href="{{ route('admin.commercial-policies.index') }}" class="btn btn-secondary">Condições comerciais</a>
    </section>

    <form method="POST" action="{{ route('admin.promotions.store') }}" class="panel mt-6 grid gap-5 p-5 sm:p-7">
        @csrf
        <div>
            <h2 class="panel-title">Nova promoção</h2>
            <p class="panel-subtitle">A oferta só será aplicada dentro das datas e se estiver ativa.</p>
        </div>
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label for="new_name" class="field-label">Nome da promoção</label>
                <input id="new_name" name="name" type="text" maxlength="120" value="{{ old('name') }}" class="field-control" required>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <label for="new_profile" class="field-label">Grupo</label>
                <select id="new_profile" name="commercial_profile" class="field-control" required>
                    @foreach ($profileLabels as $profile => $label)
                        <option value="{{ $profile }}" @selected(old('commercial_profile') === $profile)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('commercial_profile')" class="mt-2" />
            </div>
            <div>
                <label for="new_discount" class="field-label">Desconto (%)</label>
                <input id="new_discount" name="discount_percentage" type="number" min="1" max="100" step="1" value="{{ old('discount_percentage') }}" class="field-control" required>
                <x-input-error :messages="$errors->get('discount_percentage')" class="mt-2" />
            </div>
            <div>
                <label for="new_starts_on" class="field-label">Início</label>
                <input id="new_starts_on" name="starts_on" type="date" value="{{ old('starts_on') }}" class="field-control" required>
                <x-input-error :messages="$errors->get('starts_on')" class="mt-2" />
            </div>
            <div>
                <label for="new_ends_on" class="field-label">Fim</label>
                <input id="new_ends_on" name="ends_on" type="date" value="{{ old('ends_on') }}" class="field-control" required>
                <x-input-error :messages="$errors->get('ends_on')" class="mt-2" />
            </div>
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-3">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active')) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Ativa
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="is_stackable" value="0">
                <input type="checkbox" name="is_stackable" value="1" @checked(old('is_stackable')) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Acumula com o desconto do grupo
            </label>
        </div>
        <p class="field-help">Quando não acumula, vale o maior percentual entre a promoção e o grupo. Se mais de uma oferta estiver ativa no mesmo período, o sistema escolhe o maior desconto efetivo.</p>
        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
        <x-input-error :messages="$errors->get('is_stackable')" class="mt-2" />
        <div class="flex justify-end border-t border-slate-200 pt-4">
            <button type="submit" class="btn btn-brand">Cadastrar promoção</button>
        </div>
    </form>

    <section class="panel mt-6 overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-7">
            <h2 class="panel-title">Promoções cadastradas</h2>
        </div>
        @forelse ($promotions as $promotion)
            <form method="POST" action="{{ route('admin.promotions.update', $promotion) }}" class="grid gap-5 border-b border-slate-100 p-5 last:border-b-0 sm:p-7">
                @csrf
                @method('PATCH')
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label for="name_{{ $promotion->id }}" class="field-label">Nome</label>
                        <input id="name_{{ $promotion->id }}" name="name" type="text" maxlength="120" value="{{ $promotion->name }}" class="field-control" required>
                    </div>
                    <div>
                        <label for="profile_{{ $promotion->id }}" class="field-label">Grupo</label>
                        <select id="profile_{{ $promotion->id }}" name="commercial_profile" class="field-control" required>
                            @foreach ($profileLabels as $profile => $label)
                                <option value="{{ $profile }}" @selected($promotion->commercial_profile === $profile)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="discount_{{ $promotion->id }}" class="field-label">Desconto (%)</label>
                        <input id="discount_{{ $promotion->id }}" name="discount_percentage" type="number" min="1" max="100" step="1" value="{{ $promotion->discount_percentage }}" class="field-control" required>
                    </div>
                    <div>
                        <label for="starts_on_{{ $promotion->id }}" class="field-label">Início</label>
                        <input id="starts_on_{{ $promotion->id }}" name="starts_on" type="date" value="{{ $promotion->starts_on?->format('Y-m-d') }}" class="field-control" required>
                    </div>
                    <div>
                        <label for="ends_on_{{ $promotion->id }}" class="field-label">Fim</label>
                        <input id="ends_on_{{ $promotion->id }}" name="ends_on" type="date" value="{{ $promotion->ends_on?->format('Y-m-d') }}" class="field-control" required>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-x-6 gap-y-3">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($promotion->is_active) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            Ativa
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="hidden" name="is_stackable" value="0">
                            <input type="checkbox" name="is_stackable" value="1" @checked($promotion->is_stackable) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            Acumula com o grupo
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-secondary">Salvar</button>
                        <button type="submit" form="delete-promotion-{{ $promotion->id }}" class="btn btn-secondary text-red-700">Remover</button>
                    </div>
                </div>
                <p class="field-help">{{ $promotion->commercial_profile }} · {{ $promotion->discount_percentage }}% · {{ $promotion->starts_on?->format('d/m/Y') }} a {{ $promotion->ends_on?->format('d/m/Y') }}</p>
            </form>
            <form id="delete-promotion-{{ $promotion->id }}" method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @empty
            <div class="p-5 sm:p-7">
                <x-empty-state title="Nenhuma promoção cadastrada" description="Crie uma oferta com grupo e período para exibi-la em novos orçamentos." />
            </div>
        @endforelse
    </section>

    @if (method_exists($promotions, 'links'))
        <div class="mt-6">{{ $promotions->links() }}</div>
    @endif
</x-layouts.app>

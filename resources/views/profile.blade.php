<x-layouts.app title="Meu perfil | Eficaz B2B">
    @php
        $profileUser = $user ?? auth()->user();
        $resellerProfile = data_get($profileUser, 'reseller');
    @endphp

    <section class="page-heading module-page-heading">
        <div>
            <p class="eyebrow">Conta corporativa</p>
            <h1 class="page-title">Meu perfil</h1>
            <p class="page-subtitle">Consulte os dados vinculados ao seu acesso no portal B2B.</p>
        </div>
        <a href="{{ ($profileUser?->isAdmin() ?? false) ? route('admin.dashboard') : route('reseller.dashboard') }}" class="btn btn-secondary">Voltar ao painel</a>
    </section>

    <section class="panel mt-6 max-w-3xl p-5 sm:p-6">
        <dl class="divide-y divide-slate-100">
            <div class="grid gap-1 py-4 sm:grid-cols-3 sm:gap-6"><dt class="text-sm font-semibold text-slate-500">Nome</dt><dd class="text-sm font-bold text-navy-950 sm:col-span-2">{{ data_get($profileUser, 'name', '—') }}</dd></div>
            <div class="grid gap-1 py-4 sm:grid-cols-3 sm:gap-6"><dt class="text-sm font-semibold text-slate-500">E-mail</dt><dd class="text-sm font-bold text-navy-950 sm:col-span-2">{{ data_get($profileUser, 'email', '—') }}</dd></div>
            <div class="grid gap-1 py-4 sm:grid-cols-3 sm:gap-6"><dt class="text-sm font-semibold text-slate-500">Perfil de acesso</dt><dd class="text-sm font-bold text-navy-950 sm:col-span-2">{{ ($profileUser?->isAdmin() ?? false) ? 'Administrador' : 'Revendedor' }}</dd></div>
            @if ($resellerProfile)
                <div class="grid gap-1 py-4 sm:grid-cols-3 sm:gap-6"><dt class="text-sm font-semibold text-slate-500">Empresa</dt><dd class="text-sm font-bold text-navy-950 sm:col-span-2">{{ data_get($resellerProfile, 'company_name', '—') }}</dd></div>
                <div class="grid gap-1 py-4 sm:grid-cols-3 sm:gap-6"><dt class="text-sm font-semibold text-slate-500">Perfil comercial</dt><dd class="text-sm font-bold text-navy-950 sm:col-span-2">{{ data_get($resellerProfile, 'commercial_profile', '—') }}</dd></div>
            @endif
        </dl>
    </section>
</x-layouts.app>

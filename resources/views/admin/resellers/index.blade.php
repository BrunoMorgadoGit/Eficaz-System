<x-layouts.app title="Revendedores | Administração" active-nav="resellers">
    @php $resellerList = $resellers ?? []; @endphp

    <section class="page-heading module-page-heading">
        <div>
            <p class="eyebrow">Rede comercial</p>
            <h1 class="page-title">Revendedores</h1>
            <p class="page-subtitle">Gerencie os perfis comerciais e os limites de crédito das empresas cadastradas na plataforma.</p>
        </div>
    </section>

    <section class="panel mt-6">
        @if (count($resellerList))
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th>Empresa</th><th>Responsável</th><th>Perfil comercial</th><th>Limite de crédito</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($resellerList as $reseller)
                            @php
                                $user = data_get($reseller, 'user');
                                $creditLimit = data_get($reseller, 'credit_limit');
                            @endphp
                            <tr>
                                <td>
                                    <p class="font-medium text-navy-950">{{ data_get($reseller, 'company_name', 'Empresa não informada') }}</p>
                                    <p class="font-mono mt-1 text-xs text-slate-500">ID #{{ data_get($reseller, 'id', '—') }}</p>
                                </td>
                                <td>
                                    <p class="font-medium text-slate-700">{{ data_get($user, 'name', '—') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ data_get($user, 'email', '—') }}</p>
                                </td>
                                <td><span class="font-mono text-xs font-medium text-slate-600">{{ data_get($reseller, 'commercial_profile', 'STANDARD') }}</span></td>
                                <td class="font-mono font-medium text-navy-950">
                                    {{ $creditLimit === null ? 'Não informado' : 'R$ '.number_format((float) $creditLimit, 2, ',', '.') }}
                                </td>
                                <td><x-status-badge :status="data_get($reseller, 'is_active', false) ? 'active' : 'inactive'" /></td>
                                <td class="text-right"><a href="{{ route('admin.resellers.edit', $reseller) }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">Editar condições</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Nenhum revendedor cadastrado" description="Os revendedores vinculados a usuários aparecerão nesta lista." />
        @endif
    </section>

    @if (is_object($resellerList) && method_exists($resellerList, 'links'))
        <div class="mt-6">{{ $resellerList->links() }}</div>
    @endif
</x-layouts.app>

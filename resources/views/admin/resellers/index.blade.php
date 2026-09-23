<x-layouts.app title="Revendedores | Administração" active-nav="resellers">
    @php $resellerList = $resellers ?? []; @endphp

    <section class="page-heading">
        <div>
            <p class="eyebrow">Rede comercial</p>
            <h1 class="page-title">Revendedores</h1>
            <p class="page-subtitle">Consulte os perfis comerciais e o limite de crédito das empresas cadastradas na plataforma.</p>
        </div>
    </section>

    <section class="panel mt-6">
        @if (count($resellerList))
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th>Empresa</th><th>Responsável</th><th>Perfil comercial</th><th>Limite de crédito</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($resellerList as $reseller)
                            @php $user = data_get($reseller, 'user'); @endphp
                            <tr>
                                <td>
                                    <p class="font-bold text-navy-950">{{ data_get($reseller, 'company_name', 'Empresa não informada') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">ID #{{ data_get($reseller, 'id', '—') }}</p>
                                </td>
                                <td>
                                    <p class="font-semibold text-slate-700">{{ data_get($user, 'name', '—') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ data_get($user, 'email', '—') }}</p>
                                </td>
                                <td><span class="rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-bold text-cyan-800">{{ data_get($reseller, 'commercial_profile', 'STANDARD') }}</span></td>
                                <td class="font-semibold text-navy-950">R$ {{ number_format((float) data_get($reseller, 'credit_limit', 0), 2, ',', '.') }}</td>
                                <td><x-status-badge :status="data_get($reseller, 'is_active', false) ? 'active' : 'inactive'" /></td>
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

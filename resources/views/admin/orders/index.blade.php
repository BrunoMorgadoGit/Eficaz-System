<x-layouts.app title="Pedidos | Administração" active-nav="orders">
    @php $orderList = $orders ?? []; @endphp

    <section class="page-heading module-page-heading">
        <div>
            <p class="eyebrow">Operação comercial</p>
            <h1 class="page-title">Pedidos</h1>
            <p class="page-subtitle">Acompanhe os pedidos de todos os revendedores e atualize seu status operacional.</p>
        </div>
    </section>

    <section class="panel mt-6">
        @if (count($orderList))
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th>Pedido</th><th>Revendedor</th><th>Data</th><th class="text-right">Total</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($orderList as $order)
                            @php $createdAt = data_get($order, 'created_at'); @endphp
                            <tr>
                                <td><span class="font-bold text-navy-950">#{{ data_get($order, 'id', '—') }}</span></td>
                                <td>
                                    <p class="font-semibold text-slate-700">{{ data_get($order, 'reseller.company_name', '—') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ data_get($order, 'reseller.user.email', '') }}</p>
                                </td>
                                <td>{{ $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->format('d/m/Y') : '—' }}</td>
                                <td class="text-right font-bold text-navy-950">R$ {{ number_format((float) data_get($order, 'total', 0), 2, ',', '.') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.orders.status.update', $order) }}" class="flex min-w-50 items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="status-{{ data_get($order, 'id') }}">Status do pedido</label>
                                        <select id="status-{{ data_get($order, 'id') }}" name="status" class="field-control py-2 text-xs">
                                            @foreach (['PENDENTE' => 'Pendente', 'APROVADO' => 'Aprovado', 'CONCLUIDO' => 'Concluído'] as $value => $label)
                                                <option value="{{ $value }}" @selected(data_get($order, 'status') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="text-xs font-bold text-cyan-700 hover:text-cyan-800">Salvar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Nenhum pedido registrado" description="Os pedidos confirmados pelos revendedores serão exibidos aqui." />
        @endif
    </section>

    @if (is_object($orderList) && method_exists($orderList, 'links'))
        <div class="mt-6">{{ $orderList->links() }}</div>
    @endif
</x-layouts.app>

<x-layouts.app title="Administração | Eficaz B2B" active-nav="dashboard">
    @php
        $stats = $stats ?? [];
        $recentOrders = $recentOrders ?? [];
        $trendValues = $trendValues ?? data_get($trend ?? [], 'values', []);
        $trendValues = is_iterable($trendValues) ? collect($trendValues)->map(fn ($value) => (float) $value)->values() : collect();
        $trendMax = max(1, (float) ($trendValues->max() ?? 1));
        $trendPoints = $trendValues->count() > 1
            ? $trendValues->values()->map(fn ($value, $index) => round(16 + ($index * (268 / max(1, $trendValues->count() - 1))), 1) . ',' . round(108 - (($value / $trendMax) * 78), 1))->implode(' ')
            : '16,108 284,108';
    @endphp

    <section class="page-heading">
        <div>
            <p class="eyebrow">Administração</p>
            <h1 class="page-title">Panorama da operação</h1>
            <p class="page-subtitle">Acompanhe os indicadores centrais da plataforma e priorize os próximos movimentos comerciais.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-cyan">Cadastrar produto</a>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores da plataforma">
        <x-stat-card label="Total de pedidos" :value="data_get($stats, 'orders', 0)" hint="Pedidos registrados na plataforma" tone="cyan" />
        <x-stat-card label="Total vendido" :value="'R$ ' . number_format((float) data_get($stats, 'revenue', 0), 2, ',', '.')" hint="Valor de todos os pedidos" tone="green" />
        <x-stat-card label="Produtos ativos" :value="data_get($stats, 'products', 0)" hint="Itens disponíveis no catálogo" tone="navy" />
        <x-stat-card label="Revendedores" :value="data_get($stats, 'resellers', 0)" hint="Empresas cadastradas" tone="amber" />
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(17rem,0.65fr)]">
        <article class="panel p-5 sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="panel-title">Evolução de pedidos</h2>
                    <p class="panel-subtitle">Tendência recente de pedidos registrados.</p>
                </div>
                <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-bold text-cyan-700">Pedidos</span>
            </div>
            <div class="mt-6 overflow-hidden rounded-xl border border-slate-100 bg-slate-25 p-3 sm:p-5">
                <svg viewBox="0 0 300 130" class="h-44 w-full" role="img" aria-label="Gráfico de tendência dos pedidos">
                    <path d="M16 20H284M16 64H284M16 108H284" stroke="#dbe7ef" stroke-width="1" stroke-dasharray="3 5"/>
                    <polyline points="{{ $trendPoints }}" fill="none" stroke="#10c8d4" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    @if ($trendValues->count() > 1)
                        @foreach ($trendValues as $value)
                            @php
                                $pointIndex = $loop->index;
                                $pointX = round(16 + ($pointIndex * (268 / max(1, $trendValues->count() - 1))), 1);
                                $pointY = round(108 - (($value / $trendMax) * 78), 1);
                            @endphp
                            <circle cx="{{ $pointX }}" cy="{{ $pointY }}" r="4" fill="#0a2540" stroke="#fff" stroke-width="2"/>
                        @endforeach
                    @endif
                </svg>
            </div>
            @if (! $trendValues->count())
                <p class="mt-3 text-xs leading-5 text-slate-500">Ainda não há dados suficientes para desenhar uma tendência de pedidos.</p>
            @endif
        </article>

        <article class="panel p-5 sm:p-6">
            <h2 class="panel-title">Atalhos de gestão</h2>
            <p class="panel-subtitle">Acesse rapidamente as rotinas mais frequentes.</p>
            <div class="mt-5 space-y-3">
                <a href="{{ route('admin.products.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-navy-900 transition hover:border-cyan-400 hover:bg-cyan-50"><span>Gerenciar catálogo</span><span aria-hidden="true">→</span></a>
                <a href="{{ route('admin.resellers.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-navy-900 transition hover:border-cyan-400 hover:bg-cyan-50"><span>Consultar revendedores</span><span aria-hidden="true">→</span></a>
                <a href="{{ route('admin.orders.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-navy-900 transition hover:border-cyan-400 hover:bg-cyan-50"><span>Atualizar pedidos</span><span aria-hidden="true">→</span></a>
            </div>
        </article>
    </section>

    <section class="panel mt-6">
        <div class="panel-heading">
            <div>
                <h2 class="panel-title">Pedidos recentes</h2>
                <p class="panel-subtitle">Últimas movimentações comerciais recebidas pela plataforma.</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="text-sm font-bold text-cyan-700 hover:text-cyan-800">Ver pedidos</a>
        </div>
        @if (count($recentOrders))
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead><tr><th>Pedido</th><th>Revendedor</th><th>Status</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr>
                                <td class="font-bold text-navy-950">#{{ data_get($order, 'id', '—') }}</td>
                                <td>{{ data_get($order, 'reseller.company_name', data_get($order, 'reseller_name', '—')) }}</td>
                                <td><x-status-badge :status="data_get($order, 'status', 'PENDENTE')" /></td>
                                <td class="text-right font-bold text-navy-950">R$ {{ number_format((float) data_get($order, 'total', 0), 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Nenhum pedido registrado" description="Os pedidos confirmados pelos revendedores serão exibidos aqui." />
        @endif
    </section>
</x-layouts.app>

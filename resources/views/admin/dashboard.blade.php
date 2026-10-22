<x-layouts.app title="Administração | Eficaz B2B" active-nav="dashboard">
    @php
        $stats = $stats ?? [];
        $recentOrders = $recentOrders ?? [];
        $trendValues = $trendValues ?? data_get($trend ?? [], 'values', []);
        $trendValues = is_iterable($trendValues) ? collect($trendValues)->map(fn ($value) => (float) $value)->values() : collect();
        $trendLabels = collect(data_get($trend ?? [], 'labels', []))->values();
        $trendMax = max(1, (float) ($trendValues->max() ?? 1));
        $trendPoints = $trendValues->count() > 1
            ? $trendValues->values()->map(fn ($value, $index) => round(16 + ($index * (268 / max(1, $trendValues->count() - 1))), 1) . ',' . round(108 - (($value / $trendMax) * 78), 1))->implode(' ')
            : '16,108 284,108';
    @endphp

    <div class="admin-dashboard">
        <section class="page-heading module-page-heading">
            <div>
                <p class="eyebrow">Administração</p>
                <h1 class="page-title">Panorama da operação</h1>
                <p class="page-subtitle">Acompanhe os indicadores centrais da plataforma e priorize os próximos movimentos comerciais.</p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="btn btn-brand">Cadastrar produto</a>
        </section>

        <section class="admin-dashboard-metrics mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores da plataforma">
            <x-stat-card label="Total de pedidos" :value="data_get($stats, 'orders', 0)" hint="Pedidos registrados na plataforma" icon="clipboard-list" />
            <x-stat-card label="Total vendido" :value="'R$ ' . number_format((float) data_get($stats, 'revenue', 0), 2, ',', '.')" hint="Valor de todos os pedidos" icon="coin" />
            <x-stat-card label="Produtos ativos" :value="data_get($stats, 'products', 0)" hint="Itens disponíveis no catálogo" icon="package" />
            <x-stat-card label="Revendedores" :value="data_get($stats, 'resellers', 0)" hint="Empresas cadastradas" icon="users" />
        </section>

        <section class="admin-dashboard-main-grid mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(17rem,0.65fr)]">
            <article class="panel admin-dashboard-chart-panel p-5 sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="panel-title">Evolução de pedidos</h2>
                        <p class="panel-subtitle">Tendência recente de pedidos registrados.</p>
                    </div>
                    <span class="admin-dashboard-chart-badge rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700">Pedidos</span>
                </div>
                <div class="admin-dashboard-chart-frame relative mt-6 rounded-xl border border-slate-100 p-3 sm:p-5">
                    <svg viewBox="0 0 300 132" class="h-44 w-full overflow-visible" role="img" aria-label="Gráfico de tendência dos pedidos">

                        <path d="M16 20H284M16 64H284M16 108H284" stroke="var(--color-ink-300)" stroke-width="1" stroke-dasharray="3 5"/>
                        <polyline points="{{ $trendPoints }}" fill="none" stroke="var(--color-brand-500)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        @if ($trendValues->count() > 1)
                            @php
                                $columnWidth = round(268 / max(1, $trendValues->count() - 1), 1);
                            @endphp
                            @foreach ($trendValues as $value)
                                @php
                                    $pointIndex = $loop->index;
                                    $pointX = round(16 + ($pointIndex * (268 / max(1, $trendValues->count() - 1))), 1);
                                    $pointY = round(108 - (($value / $trendMax) * 78), 1);
                                    $pointLabel = $trendLabels->get($pointIndex, '');
                                    $orderCount = (int) $value;
                                    $orderText = $orderCount === 1 ? '1 pedido feito' : "{$orderCount} pedidos feitos";
                                @endphp
                                <g class="chart-point-group cursor-pointer focus:outline-none" tabindex="0" data-label="{{ ucfirst($pointLabel) }}" data-value="{{ $orderText }}" data-count="{{ $orderCount }}">
                                    <!-- Hover vertical guideline -->
                                    <line x1="{{ $pointX }}" y1="16" x2="{{ $pointX }}" y2="108" stroke="#0755f8" stroke-width="1.5" stroke-dasharray="2 3" class="chart-guide" />
                                    <!-- Hit area for easy cursor hovering -->
                                    <rect x="{{ max(0, $pointX - ($columnWidth / 2)) }}" y="10" width="{{ $columnWidth }}" height="106" fill="transparent" />
                                    <!-- Glow / Halo on hover -->
                                    <circle cx="{{ $pointX }}" cy="{{ $pointY }}" r="9" fill="#0755f8" class="chart-halo" />
                                    <!-- Main dot -->
                                    <circle cx="{{ $pointX }}" cy="{{ $pointY }}" r="4" fill="var(--color-ink-900)" stroke="var(--color-white)" stroke-width="2" class="chart-dot" />
                                    <!-- Month label -->
                                    @if ($pointLabel)
                                        <text x="{{ $pointX }}" y="124" fill="#94a3b8" font-size="8" font-family="sans-serif" font-weight="600" text-anchor="middle" class="select-none uppercase tracking-wider">{{ $pointLabel }}</text>
                                    @endif
                                    <title>{{ ucfirst($pointLabel) ? ucfirst($pointLabel) . ': ' : '' }}{{ $orderText }}</title>
                                </g>
                                <circle cx="{{ $pointX }}" cy="{{ $pointY }}" r="4" fill="var(--color-ink-900)" stroke="var(--color-white)" stroke-width="2"/>
                            @endforeach
                        @endif
                    </svg>

                    <!-- Floating Tooltip -->
                    <div id="chart-tooltip" class="pointer-events-none absolute z-20 hidden -translate-x-1/2 transition-all duration-75 ease-out" style="top: 0; left: 0;">
                        <div id="chart-tooltip-wrapper" class="flex flex-col items-center">
                            <div id="chart-tooltip-arrow-top" class="hidden h-1.5 w-2 -mb-px border-l-4 border-r-4 border-b-4 border-l-transparent border-r-transparent border-b-slate-900"></div>
                            <div class="rounded-lg bg-slate-900 px-3 py-1.5 text-center shadow-lg ring-1 ring-white/10">
                                <p id="chart-tooltip-label" class="text-[10px] font-semibold uppercase tracking-wider text-slate-300"></p>
                                <p id="chart-tooltip-value" class="text-xs font-bold text-white"></p>
                            </div>
                            <div id="chart-tooltip-arrow-bottom" class="h-1.5 w-2 -mt-px border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-slate-900"></div>
                        </div>
                    </div>
                </div>
                @if (! $trendValues->count())
                    <p class="admin-dashboard-chart-empty mt-3 text-xs leading-5 text-slate-500">Ainda não há dados suficientes para desenhar uma tendência de pedidos.</p>
                @endif
            </article>

            <article class="panel admin-dashboard-shortcuts-panel p-5 sm:p-6">
                <h2 class="panel-title">Atalhos de gestão</h2>
                <p class="panel-subtitle">Acesse rapidamente as rotinas mais frequentes.</p>
                <div class="admin-dashboard-shortcuts mt-5 space-y-3">
                    <a href="{{ route('admin.products.index') }}" class="admin-dashboard-shortcut flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-navy-900 transition hover:border-brand-400 hover:bg-brand-50"><span>Gerenciar catálogo</span><span aria-hidden="true"><x-icon name="arrow-right" size="1em" /></span></a>
                    <a href="{{ route('admin.resellers.index') }}" class="admin-dashboard-shortcut flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-navy-900 transition hover:border-brand-400 hover:bg-brand-50"><span>Consultar revendedores</span><span aria-hidden="true"><x-icon name="arrow-right" size="1em" /></span></a>
                    <a href="{{ route('admin.orders.index') }}" class="admin-dashboard-shortcut flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-navy-900 transition hover:border-brand-400 hover:bg-brand-50"><span>Atualizar pedidos</span><span aria-hidden="true"><x-icon name="arrow-right" size="1em" /></span></a>
                </div>
            </article>
        </section>

        <section class="panel admin-dashboard-orders-panel mt-6">
            <div class="panel-heading">
                <div>
                    <h2 class="panel-title">Pedidos recentes</h2>
                    <p class="panel-subtitle">Últimas movimentações comerciais recebidas pela plataforma.</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">Ver pedidos</a>
            </div>
            @if (count($recentOrders))
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Pedido</th><th>Revendedor</th><th>Status</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr>
                                    <td class="font-mono font-medium text-navy-950">#{{ data_get($order, 'id', '—') }}</td>
                                    <td>{{ data_get($order, 'reseller.company_name', data_get($order, 'reseller_name', '—')) }}</td>
                                    <td><x-status-badge :status="data_get($order, 'status', 'PENDENTE')" /></td>
                                    <td class="font-mono text-right font-medium text-navy-950">R$ {{ number_format((float) data_get($order, 'total', 0), 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state class="admin-dashboard-empty-state" title="Nenhum pedido registrado" description="Os pedidos confirmados pelos revendedores serão exibidos aqui." />
            @endif
        </section>
    </div>

    <script>
        (function () {
            const frame = document.querySelector('.admin-dashboard-chart-frame');
            const tooltip = document.getElementById('chart-tooltip');
            const tooltipLabel = document.getElementById('chart-tooltip-label');
            const tooltipValue = document.getElementById('chart-tooltip-value');
            const arrowTop = document.getElementById('chart-tooltip-arrow-top');
            const arrowBottom = document.getElementById('chart-tooltip-arrow-bottom');

            if (!frame || !tooltip || !tooltipLabel || !tooltipValue) return;

            const pointGroups = frame.querySelectorAll('.chart-point-group');

            function showTooltip(group) {
                const label = group.getAttribute('data-label') || '';
                const value = group.getAttribute('data-value') || '';
                if (!value) return;

                tooltipLabel.textContent = label;
                tooltipLabel.style.display = label ? 'block' : 'none';
                tooltipValue.textContent = value;

                const dot = group.querySelector('.chart-dot');
                const target = dot || group;
                const targetRect = target.getBoundingClientRect();
                const frameRect = frame.getBoundingClientRect();

                let x = targetRect.left + (targetRect.width / 2) - frameRect.left;
                let y = targetRect.top - frameRect.top - 8;
                let isBelow = false;

                if (y < 42) {
                    y = targetRect.bottom - frameRect.top + 8;
                    isBelow = true;
                }

                const tooltipWidth = tooltip.offsetWidth || 110;
                const minX = (tooltipWidth / 2) + 6;
                const maxX = frameRect.width - (tooltipWidth / 2) - 6;
                x = Math.max(minX, Math.min(maxX, x));

                tooltip.style.left = `${x}px`;
                tooltip.style.top = `${y}px`;

                if (isBelow) {
                    tooltip.classList.remove('-translate-y-full');
                    if (arrowTop) arrowTop.classList.remove('hidden');
                    if (arrowBottom) arrowBottom.classList.add('hidden');
                } else {
                    tooltip.classList.add('-translate-y-full');
                    if (arrowTop) arrowTop.classList.add('hidden');
                    if (arrowBottom) arrowBottom.classList.remove('hidden');
                }

                tooltip.classList.remove('hidden');
                group.classList.add('is-active');
            }

            function hideTooltip(group) {
                tooltip.classList.add('hidden');
                if (group && group.classList) {
                    group.classList.remove('is-active');
                } else {
                    pointGroups.forEach(function (g) {
                        g.classList.remove('is-active');
                    });
                }
            }

            pointGroups.forEach(function (group) {
                group.addEventListener('mouseenter', function () {
                    showTooltip(group);
                });
                group.addEventListener('focus', function () {
                    showTooltip(group);
                });
                group.addEventListener('mouseleave', function () {
                    hideTooltip(group);
                });
                group.addEventListener('blur', function () {
                    hideTooltip(group);
                });
            });

            frame.addEventListener('mouseleave', function () {
                hideTooltip();
            });
        })();
    </script>
</x-layouts.app>

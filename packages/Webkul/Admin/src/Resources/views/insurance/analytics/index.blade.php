<x-admin::layouts>
    <x-slot:title>
        Analítica Ejecutiva & Valuación de Cartera | Krayin Health CRM
    </x-slot>

    <div class="flex flex-col gap-6 p-6">
        <!-- Page Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <div class="flex items-center gap-3">
                    <span class="text-3xl">📊</span>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            Analítica Ejecutiva & Valuación de Cartera
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">
                                Book of Business 2026
                            </span>
                        </h1>
                        <p class="text-sm text-gray-500">Valoración financiera del libro de negocio de salud, KPIs de retención y leaderboard de productores</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <a
                    href="{{ route('admin.insurance.analytics.export_csv') }}"
                    class="secondary-button text-sm py-2 px-4 flex items-center gap-2"
                >
                    <span>📥</span>
                    Exportar Informe Ejecutivo (CSV)
                </a>

                <a
                    href="{{ route('admin.policies.index') }}"
                    class="primary-button text-sm py-2 px-4 flex items-center gap-2"
                >
                    <span>📁</span>
                    Ver Cartera de Pólizas
                </a>
            </div>
        </div>

        <!-- 1. Book of Business Valuation Cards (Industry Multiples) -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>💎</span> Valuación Estimada del Libro de Negocio (M&A / Mercado USA)
                </h2>
                <span class="text-xs text-gray-500">Basado en Ingreso Anual Recurrente (ARR) de ${{ number_format($metrics['valuation']['annual_recurring_revenue'], 2) }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Conservative (1.5x) -->
                <div class="p-5 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-gray-800 dark:to-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl shadow-sm">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">
                        <span>Valuación Conservadora</span>
                        <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-gray-700 text-slate-700 dark:text-gray-300 font-bold">1.5x ARR</span>
                    </div>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">
                        ${{ number_format($metrics['valuation']['valuation_conservative'], 2) }}
                    </div>
                    <p class="text-xs text-slate-500 dark:text-gray-400 mt-2">
                        Referencia para liquidación rápida, compraventa interna o carteras con persistencia estándar (<80%).
                    </p>
                </div>

                <!-- Standard (2.0x) -->
                <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/40 dark:to-indigo-950/40 border-2 border-blue-400 dark:border-blue-600 rounded-xl shadow-md relative">
                    <div class="absolute -top-3 right-4 px-2.5 py-0.5 bg-blue-600 text-white text-[10px] font-bold rounded-full uppercase tracking-wider">
                        Múltiplo Recomendado
                    </div>
                    <div class="flex items-center justify-between text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wider">
                        <span>Valor de Mercado Estándar</span>
                        <span class="px-2 py-0.5 rounded bg-blue-200 dark:bg-blue-900 text-blue-900 dark:text-blue-200 font-bold">2.0x ARR</span>
                    </div>
                    <div class="text-3xl font-extrabold text-blue-900 dark:text-blue-100 mt-2">
                        ${{ number_format($metrics['valuation']['valuation_standard'], 2) }}
                    </div>
                    <p class="text-xs text-blue-700/80 dark:text-blue-300/80 mt-2">
                        Múltiplo promedio en transacciones de agencias ACA/Medicare con retención saludable y contratos directos.
                    </p>
                </div>

                <!-- High Growth (2.5x) -->
                <div class="p-5 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/40 dark:to-teal-950/40 border border-emerald-300 dark:border-emerald-700 rounded-xl shadow-sm">
                    <div class="flex items-center justify-between text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                        <span>Alta Retención & Expansión</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-200 dark:bg-emerald-900 text-emerald-900 dark:text-emerald-200 font-bold">2.5x ARR</span>
                    </div>
                    <div class="text-3xl font-extrabold text-emerald-900 dark:text-emerald-100 mt-2">
                        ${{ number_format($metrics['valuation']['valuation_aggressive'], 2) }}
                    </div>
                    <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80 mt-2">
                        Valor premium para carteras con persistencia superior al 90%, baja siniestralidad y diversificación multicarrier.
                    </p>
                </div>
            </div>
        </div>

        <!-- 2. Core Health Portfolio KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <div class="text-xs font-medium text-gray-500">Pólizas en Vigor</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ number_format($metrics['valuation']['active_policies']) }}
                </div>
                <div class="text-xs text-gray-400 mt-1">De un total de {{ $metrics['retention']['total_policies'] }}</div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <div class="text-xs font-medium text-gray-500">Vidas Cubiertas (Lives)</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                    {{ number_format($metrics['valuation']['covered_lives']) }}
                </div>
                <div class="text-xs text-gray-400 mt-1">Beneficiarios activos</div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <div class="text-xs font-medium text-gray-500">Prima Anualizada (Volume)</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    ${{ number_format($metrics['valuation']['annualized_gross_premium'], 2) }}
                </div>
                <div class="text-xs text-emerald-600 mt-1">${{ number_format($metrics['valuation']['monthly_gross_premium'], 2) }} / mes</div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <div class="text-xs font-medium text-gray-500">Tasa de Persistencia</div>
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ $metrics['valuation']['persistency_rate'] }}%
                </div>
                <div class="text-xs text-gray-400 mt-1">Benchmark industria: >85%</div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <div class="text-xs font-medium text-gray-500">En Riesgo (Período Gracia)</div>
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                    ${{ number_format($metrics['retention']['premium_at_risk'], 2) }}
                </div>
                <div class="text-xs text-rose-500 mt-1">{{ $metrics['retention']['grace_count'] }} pólizas atrasadas</div>
            </div>
        </div>

        <!-- 3. OEP Season Progress & Targets -->
        <div class="p-5 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-2">
            <div class="flex items-center justify-between text-sm">
                <div class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🗓️</span> Avance de Meta Temporada Open Enrollment (OEP)
                </div>
                <div class="font-bold text-blue-600 dark:text-blue-400">
                    {{ $metrics['oep']['enrolled'] }} / {{ $metrics['oep']['target'] }} pólizas ({{ $metrics['oep']['progress_pct'] }}%)
                </div>
            </div>

            <div class="w-full bg-gray-100 dark:bg-gray-800 h-3 rounded-full overflow-hidden">
                <div
                    class="bg-gradient-to-r from-blue-500 to-indigo-600 h-full rounded-full transition-all duration-500"
                    style="width: {{ $metrics['oep']['progress_pct'] }}%;"
                ></div>
            </div>
            <div class="flex justify-between text-[11px] text-gray-400 pt-1">
                <span>Inicio: 1 Nov</span>
                <span>Cierre Nacional: 15 Ene</span>
            </div>
        </div>

        <!-- 4. Two-Column Analytics: Carrier Market Share & Plan Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Carrier Market Share -->
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span>🏢</span> Distribución por Aseguradora (Carrier Share)
                </h3>

                @if (empty($metrics['carrier_distribution']))
                    <div class="text-xs text-gray-400 py-6 text-center">No hay datos de carriers registrados.</div>
                @else
                    <div class="space-y-3">
                        @foreach ($metrics['carrier_distribution'] as $carrier)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $carrier['carrier'] }}</span>
                                    <span class="text-gray-500">{{ $carrier['policies'] }} pólizas ({{ $carrier['share_pct'] }}%) • ${{ number_format($carrier['monthly_premium'], 2) }}/mes</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden">
                                    <div class="bg-blue-500 h-full rounded-full" style="width: {{ $carrier['share_pct'] }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Metal Tiers & Network Types -->
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-6">
                <!-- Metal Tiers -->
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <span>🏅</span> Segmentación por Nivel de Metal (Metal Tiers)
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach ($metrics['metal_tiers'] as $tier)
                            <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-lg text-center border border-gray-100 dark:border-gray-800">
                                <div class="text-xs font-semibold text-gray-500">{{ $tier['label'] }}</div>
                                <div class="text-xl font-bold mt-1 text-gray-900 dark:text-white">{{ $tier['count'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Network Types -->
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <span>🩺</span> Tipo de Red Médica
                    </h3>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-3 bg-blue-50/50 dark:bg-blue-950/20 rounded-lg text-center border border-blue-100 dark:border-blue-900">
                            <div class="text-xs font-semibold text-blue-700 dark:text-blue-300">HMO</div>
                            <div class="text-lg font-bold text-blue-900 dark:text-blue-100 mt-1">{{ $metrics['network_types']['HMO'] }}</div>
                        </div>
                        <div class="p-3 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-lg text-center border border-emerald-100 dark:border-emerald-900">
                            <div class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">EPO</div>
                            <div class="text-lg font-bold text-emerald-900 dark:text-emerald-100 mt-1">{{ $metrics['network_types']['EPO'] }}</div>
                        </div>
                        <div class="p-3 bg-purple-50/50 dark:bg-purple-950/20 rounded-lg text-center border border-purple-100 dark:border-purple-900">
                            <div class="text-xs font-semibold text-purple-700 dark:text-purple-300">PPO</div>
                            <div class="text-lg font-bold text-purple-900 dark:text-purple-100 mt-1">{{ $metrics['network_types']['PPO'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Producer & Downline Leaderboard -->
        <div class="p-5 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🏆</span> Leaderboard de Productores (Top Agentes)
                </h3>
                <span class="text-xs text-gray-500">{{ count($metrics['leaderboard']) }} agentes con producción activa</span>
            </div>

            @if (empty($metrics['leaderboard']))
                <div class="p-6 text-center text-xs text-gray-400">No hay pólizas asignadas a productores aún.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-400 font-semibold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3">Posición / Agente</th>
                                <th class="p-3 text-center">Pólizas Activas</th>
                                <th class="p-3 text-center">Vidas Cubiertas</th>
                                <th class="p-3 text-right">Prima Anualizada</th>
                                <th class="p-3 text-center">Persistencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($metrics['leaderboard'] as $index => $agent)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                    <td class="p-3 font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                        <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold {{ $index === 0 ? 'bg-amber-100 text-amber-800' : ($index === 1 ? 'bg-slate-200 text-slate-800' : 'bg-gray-100 text-gray-600') }}">
                                            {{ $index + 1 }}
                                        </span>
                                        {{ $agent['agent_name'] }}
                                    </td>
                                    <td class="p-3 text-center font-semibold text-gray-900 dark:text-white">
                                        {{ $agent['active_policies'] }}
                                    </td>
                                    <td class="p-3 text-center font-medium text-blue-600 dark:text-blue-400">
                                        {{ $agent['covered_lives'] }}
                                    </td>
                                    <td class="p-3 text-right font-medium text-gray-900 dark:text-white">
                                        ${{ number_format($agent['annualized_premium'], 2) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $agent['persistency_rate'] >= 85 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                            {{ $agent['persistency_rate'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>

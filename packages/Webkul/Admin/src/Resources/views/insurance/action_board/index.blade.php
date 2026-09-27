<x-admin::layouts>
    <x-slot:title>
        @lang('admin::insurance.action_board.title')
    </x-slot>

    <div class="flex flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>⚡</span> @lang('admin::insurance.action_board.title')
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @lang('admin::insurance.action_board.subtitle')
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('admin.insurance.action_board.index') }}"
                    class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                    title="@lang('admin::insurance.action_board.refresh')"
                >
                    <span class="icon-refresh text-xs"></span>
                    @lang('admin::insurance.action_board.refresh')
                </a>

                @if (request()->has('all_agents'))
                    <a
                        href="{{ route('admin.insurance.action_board.index') }}"
                        class="secondary-button text-xs py-1.5 px-3"
                    >
                        👤 @lang('admin::insurance.action_board.my_actions')
                    </a>
                @else
                    <a
                        href="{{ route('admin.insurance.action_board.index', ['all_agents' => 1]) }}"
                        class="primary-button text-xs py-1.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white"
                    >
                        👥 @lang('admin::insurance.action_board.all_agency_actions')
                    </a>
                @endif
            </div>
        </div>

        <!-- Metrics Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Total Actions -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase text-gray-500">@lang('admin::insurance.action_board.kpi_total_actions')</div>
                <div class="text-2xl font-black text-indigo-600 mt-1 flex items-center justify-between">
                    <span>{{ $data['metrics']['total_urgent_actions'] }}</span>
                    <span class="text-xl">🎯</span>
                </div>
            </div>

            <!-- Binder Pending -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase text-amber-600">@lang('admin::insurance.action_board.kpi_binder_pending')</div>
                <div class="text-2xl font-black text-amber-600 mt-1 flex items-center justify-between">
                    <span>{{ $data['metrics']['binder_pending_count'] }}</span>
                    <span class="text-xl">💳</span>
                </div>
            </div>

            <!-- DMI Critical -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase text-rose-600">@lang('admin::insurance.action_board.kpi_dmi_critical')</div>
                <div class="text-2xl font-black text-rose-600 mt-1 flex items-center justify-between">
                    <span>{{ $data['metrics']['dmi_critical_count'] }}</span>
                    <span class="text-xl">🚨</span>
                </div>
            </div>

            <!-- Grace Period -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase text-purple-600">@lang('admin::insurance.action_board.kpi_grace_period')</div>
                <div class="text-2xl font-black text-purple-600 mt-1 flex items-center justify-between">
                    <span>{{ $data['metrics']['grace_period_count'] }}</span>
                    <span class="text-xl">⏳</span>
                </div>
            </div>

            <!-- Revenue at Risk -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase text-rose-700">@lang('admin::insurance.action_board.kpi_revenue_at_risk')</div>
                <div class="text-2xl font-black text-rose-700 mt-1 flex items-center justify-between">
                    <span>${{ number_format($data['metrics']['revenue_at_risk_amount'], 2) }}</span>
                    <span class="text-xl">🛡️</span>
                </div>
            </div>
        </div>

        <!-- Section 1: Binder Pending -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 bg-amber-50/60 dark:bg-amber-950/20 border-b border-amber-200 dark:border-amber-900/60">
                <div class="flex items-center gap-2">
                    <span class="text-lg">💳</span>
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                        @lang('admin::insurance.action_board.section_binder_title') ({{ count($data['binder_pending']) }})
                    </h2>
                </div>
                <span class="text-[11px] text-amber-700 font-semibold">@lang('admin::insurance.action_board.section_binder_desc')</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 uppercase text-[10px]">
                        <tr>
                            <th class="px-4 py-2.5">@lang('admin::insurance.action_board.col_client')</th>
                            <th class="px-4 py-2.5">@lang('admin::insurance.action_board.col_carrier_plan')</th>
                            <th class="px-4 py-2.5">@lang('admin::insurance.action_board.col_binder_amount')</th>
                            <th class="px-4 py-2.5">@lang('admin::insurance.action_board.col_due_date')</th>
                            <th class="px-4 py-2.5">@lang('admin::insurance.action_board.col_urgency')</th>
                            <th class="px-4 py-2.5 text-right">@lang('admin::insurance.action_board.col_action')</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($data['binder_pending'] as $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                    {{ $row['client_name'] }}
                                    @if ($row['client_phone'])
                                        <div class="text-[10px] text-gray-400">{{ $row['client_phone'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="font-semibold">{{ $row['carrier_name'] }}</div>
                                    <div class="text-[10px] text-gray-500 truncate max-w-xs">{{ $row['plan_name'] }}</div>
                                </td>
                                <td class="px-4 py-2.5 font-bold text-gray-900 dark:text-white">
                                    ${{ number_format($row['binder_amount'], 2) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    {{ $row['due_date'] ?? 'N/D' }}
                                    <div class="text-[10px] text-gray-400">({{ $row['days_remaining'] }} d restantes)</div>
                                </td>
                                <td class="px-4 py-2.5">
                                    @if ($row['urgency'] === 'critical')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">🚨 Crítico</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">⚠️ Advertencia</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right space-x-1.5">
                                    @if ($row['client_phone'])
                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $row['client_phone']) }}" target="_blank" class="px-2 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-semibold text-[10px]">
                                            💬 WhatsApp
                                        </a>
                                    @endif
                                    <a href="{{ $row['action_url'] }}" class="primary-button text-xs py-1 px-2.5 bg-indigo-600 hover:bg-indigo-700 text-white">
                                        Registrar Pago
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                                    ✨ @lang('admin::insurance.action_board.no_binder_pending')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: DMI Critical (< 15 days) -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 bg-rose-50/60 dark:bg-rose-950/20 border-b border-rose-200 dark:border-rose-900/60">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🚨</span>
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                        @lang('admin::insurance.action_board.section_dmi_title') ({{ count($data['dmi_critical']) }})
                    </h2>
                </div>
                <span class="text-[11px] text-rose-700 font-semibold">@lang('admin::insurance.action_board.section_dmi_desc')</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 uppercase text-[10px]">
                        <tr>
                            <th class="px-4 py-2.5">@lang('admin::insurance.action_board.col_client')</th>
                            <th class="px-4 py-2.5">Inconsistencia DMI</th>
                            <th class="px-4 py-2.5">Fecha Límite CMS</th>
                            <th class="px-4 py-2.5">Urgencia</th>
                            <th class="px-4 py-2.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($data['dmi_critical'] as $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                    {{ $row['client_name'] }}
                                    @if ($row['client_phone'])
                                        <div class="text-[10px] text-gray-400">{{ $row['client_phone'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="font-semibold">{{ $row['title'] }}</div>
                                    <div class="text-[10px] text-gray-500">{{ $row['dmi_type'] }}</div>
                                </td>
                                <td class="px-4 py-2.5">
                                    {{ $row['due_date'] }}
                                    <div class="text-[10px] text-rose-600 font-bold">({{ $row['days_remaining'] }} d restantes)</div>
                                </td>
                                <td class="px-4 py-2.5">
                                    @if ($row['urgency'] === 'critical')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">🚨 Riesgo Inminente</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">⚠️ Por Vencer</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <a href="{{ $row['action_url'] }}" class="primary-button text-xs py-1 px-2.5 bg-rose-600 hover:bg-rose-700 text-white">
                                        Subir Documentos
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                                    ✨ @lang('admin::insurance.action_board.no_dmi_critical')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: Grace Period Policies -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 bg-purple-50/60 dark:bg-purple-950/20 border-b border-purple-200 dark:border-purple-900/60">
                <div class="flex items-center gap-2">
                    <span class="text-lg">⏳</span>
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                        @lang('admin::insurance.action_board.section_grace_title') ({{ count($data['grace_periods']) }})
                    </h2>
                </div>
                <span class="text-[11px] text-purple-700 font-semibold">@lang('admin::insurance.action_board.section_grace_desc')</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 uppercase text-[10px]">
                        <tr>
                            <th class="px-4 py-2.5">@lang('admin::insurance.action_board.col_client')</th>
                            <th class="px-4 py-2.5">Aseguradora / Póliza</th>
                            <th class="px-4 py-2.5">Prima Mensual</th>
                            <th class="px-4 py-2.5">Fase de Gracia</th>
                            <th class="px-4 py-2.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($data['grace_periods'] as $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                    {{ $row['client_name'] }}
                                    @if ($row['client_phone'])
                                        <div class="text-[10px] text-gray-400">{{ $row['client_phone'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="font-semibold">{{ $row['carrier_name'] }}</div>
                                    <div class="text-[10px] text-gray-500">#{{ $row['policy_number'] }}</div>
                                </td>
                                <td class="px-4 py-2.5 font-bold">
                                    ${{ number_format($row['monthly_premium'], 2) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $row['urgency'] === 'critical' ? 'bg-rose-100 text-rose-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $row['stage_label'] }} ({{ $row['grace_period_days'] }} d mora)
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <a href="{{ $row['action_url'] }}" class="primary-button text-xs py-1 px-2.5 bg-indigo-600 hover:bg-indigo-700 text-white">
                                        Contactar / Rescatar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                                    ✨ @lang('admin::insurance.action_board.no_grace_period')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin::layouts>

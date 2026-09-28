<x-admin::layouts>
    <x-slot:title>
        @lang('admin::insurance.policies.title_page')
    </x-slot>

    <v-book-of-business></v-book-of-business>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-book-of-business-template">
            <div class="content-wrapper p-6 space-y-6">
                
                <!-- Header -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">📚</span>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                                @lang('admin::insurance.policies.title_page')
                            </h1>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            @lang('admin::insurance.policies.subtitle')
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="openOepHub()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm"
                        >
                            <span>🔄</span>
                            <span>@lang('admin::insurance.renewals.btn_hub')</span>
                        </button>

                        <button
                            type="button"
                            @click="scanGracePeriods()"
                            :disabled="isScanning"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm disabled:opacity-50"
                        >
                            <span :class="{'animate-spin': isScanning}">🔄</span>
                            <span>@{{ isScanning ? '@lang('admin::insurance.policies.scanning')' : '@lang('admin::insurance.policies.scan_btn')' }}</span>
                        </button>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- In Force Policies -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.policies.kpi_in_force')</span>
                            <span class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center font-bold">
                                🛡️
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">
                            @{{ metrics.in_force_count || 0 }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            <strong>@{{ metrics.covered_lives_count || 0 }}</strong> @lang('admin::insurance.policies.covered_lives')
                        </div>
                    </div>

                    <!-- Persistency Rate -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.policies.kpi_persistency')</span>
                            <span class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-600 flex items-center justify-center font-bold">
                                📈
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 mt-2">
                            @{{ metrics.persistency_rate || 0 }}%
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            @lang('admin::insurance.policies.persistency_sub')
                        </div>
                    </div>

                    <!-- Monthly Net Volume -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.policies.kpi_premium')</span>
                            <span class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 flex items-center justify-center font-bold">
                                💵
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">
                            $@{{ formatMoney(metrics.net_monthly_volume) }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            @lang('admin::insurance.policies.gross') $@{{ formatMoney(metrics.gross_monthly_volume) }}
                        </div>
                    </div>

                    <!-- Grace Period At Risk -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-rose-600 uppercase tracking-wider">@lang('admin::insurance.policies.kpi_grace_period')</span>
                            <span class="w-8 h-8 rounded-full bg-rose-50 dark:bg-rose-950/50 text-rose-600 flex items-center justify-center font-bold">
                                ⚠️
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 mt-2">
                            @{{ metrics.grace_count || 0 }}
                        </div>
                        <div class="text-xs text-rose-500 mt-1">
                            $@{{ formatMoney(metrics.premium_at_risk) }} @lang('admin::insurance.policies.grace_period_sub')
                        </div>
                    </div>

                </div>

                <!-- Filters & Search Bar -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        
                        <!-- Status Filter Tabs -->
                        <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-800 p-1 bg-slate-50 dark:bg-slate-900 text-xs font-semibold">
                            <button
                                type="button"
                                @click="filterStatus('all')"
                                :class="statusFilter === 'all' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                class="px-3 py-1.5 rounded-md transition-all"
                            >
                                @lang('admin::insurance.policies.tabs_all') (@{{ metrics.total_policies || 0 }})
                            </button>
                            <button
                                type="button"
                                @click="filterStatus('active')"
                                :class="statusFilter === 'active' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800'"
                                class="px-3 py-1.5 rounded-md transition-all"
                            >
                                @lang('admin::insurance.policies.tabs_active') (@{{ metrics.in_force_count || 0 }})
                            </button>
                            <button
                                type="button"
                                @click="filterStatus('binder_pending')"
                                :class="statusFilter === 'binder_pending' ? 'bg-amber-500 text-white font-bold shadow-sm' : 'text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950'"
                                class="px-3 py-1.5 rounded-md transition-all"
                            >
                                💳 @lang('admin::insurance.policies.status_binder_pending')
                            </button>
                            <button
                                type="button"
                                @click="filterStatus('in_grace')"
                                :class="statusFilter === 'in_grace' ? 'bg-rose-600 text-white font-bold shadow-sm' : 'text-rose-600 hover:bg-rose-50'"
                                class="px-3 py-1.5 rounded-md transition-all"
                            >
                                ⚠️ @lang('admin::insurance.policies.tabs_grace') (@{{ metrics.grace_count || 0 }})
                            </button>
                            <button
                                type="button"
                                @click="filterStatus('cancelled')"
                                :class="statusFilter === 'cancelled' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                class="px-3 py-1.5 rounded-md transition-all"
                            >
                                @lang('admin::insurance.policies.tabs_cancelled') (@{{ metrics.cancelled_count || 0 }})
                            </button>
                        </div>

                    </div>

                    <!-- Search Input -->
                    <div class="w-full sm:w-72">
                        <input
                            type="text"
                            v-model="searchTerm"
                            @input="debounceSearch()"
                            placeholder="{{ trans('admin::insurance.policies.search_placeholder') }}"
                            class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-slate-50 dark:bg-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500"
                        >
                    </div>
                </div>

                <!-- Policies Table -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700">
                                    <th class="p-3.5">@lang('admin::insurance.policies.col_carrier')</th>
                                    <th class="p-3.5">@lang('admin::insurance.policies.col_client')</th>
                                    <th class="p-3.5">@lang('admin::insurance.policies.col_plan_network')</th>
                                    <th class="p-3.5">@lang('admin::insurance.policies.col_premium')</th>
                                    <th class="p-3.5">@lang('admin::insurance.policies.col_paid_to')</th>
                                    <th class="p-3.5">@lang('admin::insurance.policies.col_payment_status')</th>
                                    <th class="p-3.5 text-right">@lang('admin::insurance.policies.col_retention_actions')</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="isLoading">
                                    <td colspan="7" class="p-8 text-center text-slate-500">
                                        @lang('admin::insurance.policies.loading')
                                    </td>
                                </tr>
                                <tr v-else-if="!policies.length">
                                    <td colspan="7" class="p-8 text-center text-slate-500">
                                        @lang('admin::insurance.policies.no_records')
                                    </td>
                                </tr>
                                <tr v-else v-for="policy in policies" :key="policy.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    
                                    <!-- Policy & Carrier -->
                                    <td class="p-3.5">
                                        <div class="font-extrabold text-slate-900 dark:text-white">
                                            @{{ policy.policy_number }}
                                        </div>
                                        <div class="text-[11px] font-bold text-sky-600 mt-0.5">
                                            @{{ policy.carrier_name }}
                                        </div>
                                    </td>

                                    <!-- Client -->
                                    <td class="p-3.5">
                                        <div class="font-semibold text-slate-900 dark:text-white">
                                            @{{ (policy.person && policy.person.name) || (policy.lead && policy.lead.person && policy.lead.person.name) || (policy.lead && policy.lead.title) || 'Cliente' }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            @lang('admin::insurance.policies.agent') @{{ (policy.user && policy.user.name) || 'Principal' }}
                                        </div>
                                    </td>

                                    <!-- Plan & Tier -->
                                    <td class="p-3.5">
                                        <div class="font-medium text-slate-800 dark:text-slate-200">
                                            @{{ policy.plan_name }}
                                        </div>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-700">
                                                @{{ policy.metal_tier }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-semibold">
                                                @{{ policy.network_type }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Monthly Cost -->
                                    <td class="p-3.5">
                                        <div class="font-extrabold text-slate-900 dark:text-white">
                                            $@{{ formatMoney(policy.net_premium) }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            @lang('admin::insurance.policies.subsidy') $@{{ formatMoney(policy.aptc_subsidy) }}
                                        </div>
                                    </td>

                                    <!-- Paid To Date -->
                                    <td class="p-3.5">
                                        <div class="font-semibold text-slate-900 dark:text-white">
                                            @{{ policy.paid_to_date || 'N/A' }}
                                        </div>
                                        <div v-if="policy.days_overdue > 0" class="text-[10px] text-rose-500 font-bold">
                                            @lang('admin::insurance.policies.overdue') (@{{ policy.days_overdue }}d)
                                        </div>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="p-3.5">
                                        <span 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border"
                                            :class="policy.grace_status_badge.bg"
                                        >
                                            <span>@{{ policy.grace_status_badge.icon }}</span>
                                            <span>@{{ policy.grace_status_badge.label }}</span>
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="p-3.5 text-right space-x-1.5">
                                        <a
                                            :href="policy.portal_url"
                                            target="_blank"
                                            class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-bold transition-colors inline-block"
                                            title="{{ trans('admin::insurance.policies.portal_link') }}"
                                        >
                                            @lang('admin::insurance.policies.btn_card')
                                        </a>

                                        <button
                                            v-if="policy.status === 'binder_pending'"
                                            type="button"
                                            @click="openBinderModal(policy)"
                                            class="px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded text-xs font-bold transition-colors"
                                            title="{{ trans('admin::insurance.policies.btn_confirm_binder') }}"
                                        >
                                            💳 @lang('admin::insurance.policies.btn_confirm_binder')
                                        </button>

                                        <button
                                            type="button"
                                            @click="openPaymentModal(policy)"
                                            class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold transition-colors"
                                            title="{{ trans('admin::insurance.policies.btn_payment') }}"
                                        >
                                            @lang('admin::insurance.policies.btn_payment')
                                        </button>

                                        <button
                                            type="button"
                                            @click="sendWhatsAppReminder(policy.id)"
                                            class="px-2.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded text-xs font-bold transition-colors"
                                            title="{{ trans('admin::insurance.policies.btn_reminder') }}"
                                        >
                                            @lang('admin::insurance.policies.btn_reminder')
                                        </button>

                                        <button
                                            type="button"
                                            @click="openRenewalComparator(policy)"
                                            class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 dark:hover:bg-indigo-900 rounded text-xs font-bold transition-colors inline-flex items-center gap-1"
                                            title="{{ trans('admin::insurance.renewals.modal_title') }}"
                                        >
                                            ⚖️ @lang('admin::insurance.renewals.btn_preview')
                                        </button>

                                        <button
                                            v-if="policy.status !== 'renewed'"
                                            type="button"
                                            @click="renewPolicy(policy.id)"
                                            class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded text-xs font-bold transition-colors"
                                            title="{{ trans('admin::insurance.policies.btn_renew') }}"
                                        >
                                            @lang('admin::insurance.policies.btn_renew')
                                        </button>

                                        <button
                                            type="button"
                                            @click="openCasesModal(policy)"
                                            class="px-2.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-bold transition-colors inline-flex items-center gap-1"
                                            title="Casos de Servicio y 1095-A"
                                        >
                                            🎧 Casos
                                        </button>
                                    </td>

                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RECORD PAYMENT MODAL -->
                <div v-if="showPaymentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">
                            @lang('admin::insurance.policies.modal_payment_title')
                        </h3>
                        <p class="text-xs text-slate-500 mb-4">
                            @lang('admin::insurance.policies.modal_policy_label') <strong>@{{ activePolicy.policy_number }}</strong> (@{{ activePolicy.carrier_name }})
                        </p>

                        <div class="mb-4">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                @lang('admin::insurance.policies.modal_paid_to_label')
                            </label>
                            <input
                                type="date"
                                v-model="paymentForm.paid_to_date"
                                class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            >
                            <p class="text-[11px] text-slate-400 mt-1">
                                @lang('admin::insurance.policies.modal_paid_to_hint')
                            </p>
                        </div>

                        <div class="mb-5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                @lang('admin::insurance.policies.modal_notes_label')
                            </label>
                            <input
                                type="text"
                                v-model="paymentForm.notes"
                                placeholder="{{ trans('admin::insurance.policies.modal_notes_placeholder') }}"
                                class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white"
                            >
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="showPaymentModal = false"
                                class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs"
                            >
                                @lang('admin::insurance.policies.btn_cancel')
                            </button>
                            <button
                                type="button"
                                @click="submitPayment()"
                                class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition-colors"
                            >
                                @lang('admin::insurance.policies.btn_confirm_payment')
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RECORD BINDER PAYMENT MODAL -->
                <div v-if="showBinderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">
                            @lang('admin::insurance.policies.modal_binder_title')
                        </h3>
                        <p class="text-xs text-slate-500 mb-4">
                            @lang('admin::insurance.policies.modal_policy_label') <strong>@{{ activePolicy.policy_number }}</strong> (@{{ activePolicy.carrier_name }})
                        </p>

                        <div class="p-3 bg-amber-50 dark:bg-amber-950/50 rounded-xl border border-amber-200 dark:border-amber-800 mb-4 text-xs text-amber-800 dark:text-amber-300">
                            @lang('admin::insurance.policies.modal_binder_hint')
                        </div>

                        <div class="space-y-3 mb-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    @lang('admin::insurance.policies.confirmation_number')
                                </label>
                                <input
                                    type="text"
                                    v-model="binderForm.confirmation_number"
                                    placeholder="Ej. REC-FLB-987654"
                                    class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-slate-50 dark:bg-slate-800 dark:text-white"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    @lang('admin::insurance.policies.payment_method')
                                </label>
                                <select
                                    v-model="binderForm.payment_method"
                                    class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-slate-50 dark:bg-slate-800 dark:text-white"
                                >
                                    <option value="carrier_portal">@lang('admin::insurance.policies.payment_method_options.carrier_portal')</option>
                                    <option value="credit_card">@lang('admin::insurance.policies.payment_method_options.credit_card')</option>
                                    <option value="ach">@lang('admin::insurance.policies.payment_method_options.ach')</option>
                                    <option value="phone">@lang('admin::insurance.policies.payment_method_options.phone')</option>
                                    <option value="check">@lang('admin::insurance.policies.payment_method_options.check')</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    @lang('admin::insurance.policies.modal_notes_label')
                                </label>
                                <textarea
                                    v-model="binderForm.notes"
                                    rows="2"
                                    placeholder="{{ trans('admin::insurance.policies.modal_notes_placeholder') }}"
                                    class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-slate-50 dark:bg-slate-800 dark:text-white"
                                ></textarea>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="showBinderModal = false"
                                class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs"
                            >
                                @lang('admin::insurance.policies.btn_cancel')
                            </button>
                            <button
                                type="button"
                                @click="submitBinderPayment()"
                                class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg text-xs transition-colors"
                            >
                                @lang('admin::insurance.policies.btn_confirm_binder')
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SERVICE CASES MODAL (1095-A, Address Change, Claims) -->
                <div v-if="showCasesModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] flex flex-col">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>🎧</span> @lang('admin::insurance.service_cases.title')
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Póliza: <strong>@{{ activePolicy.policy_number }}</strong> (@{{ activePolicy.carrier_name }})
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="showNewCaseForm = !showNewCaseForm"
                                class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold transition-colors"
                            >
                                @{{ showNewCaseForm ? 'Ver Casos' : '@lang('admin::insurance.service_cases.create_btn')' }}
                            </button>
                        </div>

                        <!-- Form Create New Case -->
                        <div v-if="showNewCaseForm" class="py-4 space-y-3 overflow-y-auto">
                            <form @submit.prevent="createServiceCase" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 required">
                                            @lang('admin::insurance.service_cases.category')
                                        </label>
                                        <select
                                            v-model="caseForm.category"
                                            required
                                            class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                        >
                                            <option value="tax_1095a">Fiscal / Declaración 1095-A</option>
                                            <option value="address_change">Cambio de Dirección</option>
                                            <option value="income_update">Actualización de Ingresos (Marketplace)</option>
                                            <option value="pcp_change">Cambio de Médico Primario (PCP)</option>
                                            <option value="id_card_replacement">Reemplazo de Tarjeta / Carnet</option>
                                            <option value="claims_billing">Facturación y Reclamos Médicos</option>
                                            <option value="dependent_change">Modificación de Dependientes</option>
                                            <option value="general">Consulta / Trámite General</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 required">
                                            @lang('admin::insurance.service_cases.priority')
                                        </label>
                                        <select
                                            v-model="caseForm.priority"
                                            required
                                            class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                        >
                                            <option value="low">Baja</option>
                                            <option value="normal">Normal</option>
                                            <option value="high">Alta</option>
                                            <option value="urgent">Urgente</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 required">
                                        @lang('admin::insurance.service_cases.subject')
                                    </label>
                                    <input
                                        type="text"
                                        v-model="caseForm.subject"
                                        required
                                        placeholder="Ej. Solicitud de Forma 1095-A para declaración de impuestos"
                                        class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        @lang('admin::insurance.service_cases.description')
                                    </label>
                                    <textarea
                                        v-model="caseForm.description"
                                        rows="2"
                                        placeholder="Detalles del trámite o solicitud del asegurado..."
                                        class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        @lang('admin::insurance.service_cases.attachment')
                                    </label>
                                    <input
                                        type="file"
                                        ref="caseAttachment"
                                        accept=".pdf,image/*"
                                        class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer"
                                    />
                                </div>

                                <div class="flex items-center gap-2 pt-1">
                                    <input
                                        type="checkbox"
                                        id="shareClient"
                                        v-model="caseForm.is_shared_with_client"
                                        class="rounded text-purple-600"
                                    />
                                    <label for="shareClient" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                        @lang('admin::insurance.service_cases.share_client')
                                    </label>
                                </div>

                                <div class="flex justify-end gap-2 pt-2">
                                    <button
                                        type="button"
                                        @click="showNewCaseForm = false"
                                        class="px-4 py-2 border border-slate-300 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="isSubmittingCase"
                                        class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold"
                                    >
                                        @{{ isSubmittingCase ? 'Guardando...' : 'Crear Caso' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- List of Existing Cases -->
                        <div v-else class="py-4 space-y-3 overflow-y-auto flex-1">
                            <div v-if="isLoadingCases" class="py-6 text-center text-xs text-slate-400">
                                Cargando casos...
                            </div>
                            <div v-else-if="!policyCases.length" class="py-8 text-center text-xs text-slate-400">
                                @lang('admin::insurance.service_cases.empty_cases')
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="kase in policyCases"
                                    :key="kase.id"
                                    class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 space-y-2"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs font-extrabold text-purple-600 dark:text-purple-400">
                                                @{{ kase.ticket_number }}
                                            </span>
                                            <span class="text-xs font-bold text-slate-800 dark:text-white">
                                                @{{ kase.subject }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-1.5">
                                            <span
                                                v-if="kase.is_shared_with_client"
                                                class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300"
                                            >
                                                🌐 @lang('admin::insurance.service_cases.shared_badge')
                                            </span>
                                            <select
                                                :value="kase.status"
                                                @change="updateCaseStatus(kase.id, $event.target.value)"
                                                class="text-[11px] font-bold py-0.5 px-2 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900"
                                            >
                                                <option value="open">Abierto</option>
                                                <option value="in_progress">En Trámite</option>
                                                <option value="pending_carrier">Pend. Aseguradora</option>
                                                <option value="pending_client">Pend. Cliente</option>
                                                <option value="resolved">Resuelto</option>
                                                <option value="closed">Cerrado</option>
                                            </select>
                                        </div>
                                    </div>

                                    <p v-if="kase.description" class="text-xs text-slate-600 dark:text-slate-300">
                                        @{{ kase.description }}
                                    </p>

                                    <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-200 dark:border-slate-800">
                                        <div>
                                            <span>@{{ kase.category_label }}</span> •
                                            <span>Prioridad: @{{ kase.priority_label }}</span> •
                                            <span>@{{ kase.created_at ? kase.created_at.substring(0, 10) : '' }}</span>
                                        </div>

                                        <div v-if="kase.attachment_path">
                                            <a
                                                :href="`/admin/service-cases/${kase.id}/download`"
                                                target="_blank"
                                                class="font-bold text-sky-600 hover:text-sky-700 inline-flex items-center gap-1"
                                            >
                                                📥 @lang('admin::insurance.service_cases.download_file')
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                            <button
                                type="button"
                                @click="showCasesModal = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- OEP RENEWALS HUB MODAL -->
                <div v-if="showOepHubModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-5xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] flex flex-col">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>🔄</span> @lang('admin::insurance.renewals.title')
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    @lang('admin::insurance.renewals.subtitle')
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <label class="text-xs font-bold text-slate-600 dark:text-slate-300">
                                    @lang('admin::insurance.renewals.cohort_year'):
                                </label>
                                <select
                                    v-model="oepCohortYear"
                                    @change="loadOepHubData"
                                    class="text-xs font-bold py-1.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                >
                                    <option :value="2025">Campaña 2025</option>
                                    <option :value="2026">Campaña 2026</option>
                                    <option :value="2027">Campaña 2027</option>
                                </select>
                                <button
                                    type="button"
                                    @click="showOepHubModal = false"
                                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <!-- OEP KPI Stats Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 py-4">
                            <div class="p-3.5 bg-indigo-50/70 dark:bg-indigo-950/40 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                                <span class="text-[11px] font-bold text-indigo-700 dark:text-indigo-400 uppercase">@lang('admin::insurance.renewals.kpi_retention_rate')</span>
                                <div class="text-2xl font-black text-indigo-950 dark:text-white mt-1">
                                    @{{ oepData.kpis ? (oepData.kpis.retention_rate || 0) : 0 }}%
                                </div>
                                <span class="text-[10px] text-indigo-600/80 dark:text-indigo-400">@{{ oepData.kpis ? (oepData.kpis.renewed_total || 0) : 0 }} de @{{ oepData.kpis ? (oepData.kpis.total_cohort || 0) : 0 }} renovadas</span>
                            </div>

                            <div class="p-3.5 bg-emerald-50/70 dark:bg-emerald-950/40 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                                <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 uppercase">@lang('admin::insurance.renewals.kpi_retained_same_carrier')</span>
                                <div class="text-2xl font-black text-emerald-950 dark:text-white mt-1">
                                    @{{ oepData.kpis ? (oepData.kpis.renewed_same_carrier || 0) : 0 }}
                                </div>
                                <span class="text-[10px] text-emerald-600/80 dark:text-emerald-400">Fidelidad con misma aseguradora</span>
                            </div>

                            <div class="p-3.5 bg-sky-50/70 dark:bg-sky-950/40 rounded-xl border border-sky-100 dark:border-sky-900/50">
                                <span class="text-[11px] font-bold text-sky-700 dark:text-sky-400 uppercase">@lang('admin::insurance.renewals.kpi_switched_carrier')</span>
                                <div class="text-2xl font-black text-sky-950 dark:text-white mt-1">
                                    @{{ oepData.kpis ? (oepData.kpis.renewed_cross_carrier || 0) : 0 }}
                                </div>
                                <span class="text-[10px] text-sky-600/80 dark:text-sky-400">Cliente retenido / cambio de carrier</span>
                            </div>

                            <div class="p-3.5 bg-amber-50/70 dark:bg-amber-950/40 rounded-xl border border-amber-100 dark:border-amber-900/50">
                                <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400 uppercase">@lang('admin::insurance.renewals.kpi_pending')</span>
                                <div class="text-2xl font-black text-amber-950 dark:text-white mt-1">
                                    @{{ oepData.kpis ? (oepData.kpis.pending_review || 0) : 0 }}
                                </div>
                                <span class="text-[10px] text-amber-600/80 dark:text-amber-400">Acción requerida para OEP</span>
                            </div>
                        </div>

                        <!-- Cohort Policy List -->
                        <div class="overflow-y-auto flex-1 border border-slate-200 dark:border-slate-800 rounded-xl">
                            <div v-if="isLoadingOepHub" class="py-12 text-center text-xs text-slate-400">
                                Cargando cohorte de renovación...
                            </div>
                            <div v-else-if="!oepData.policies || !oepData.policies.length" class="py-12 text-center text-xs text-slate-400">
                                No se encontraron pólizas para la campaña seleccionada.
                            </div>
                            <table v-else class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 font-bold uppercase text-[10px]">
                                        <th class="p-3">Cliente / Teléfono</th>
                                        <th class="p-3">Póliza Base</th>
                                        <th class="p-3">Aseguradora / Plan</th>
                                        <th class="p-3">Prima Neta</th>
                                        <th class="p-3">Estado OEP</th>
                                        <th class="p-3 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                    <tr v-for="item in oepData.policies" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                        <td class="p-3">
                                            <div class="font-bold text-slate-900 dark:text-white">@{{ item.client_name }}</div>
                                            <div class="text-[11px] text-slate-400">@{{ item.client_phone || 'Sin Tel.' }}</div>
                                        </td>
                                        <td class="p-3 font-mono font-bold text-slate-700 dark:text-slate-300">
                                            @{{ item.policy_number }}
                                        </td>
                                        <td class="p-3">
                                            <div class="font-bold text-slate-800 dark:text-slate-200">@{{ item.carrier_name }}</div>
                                            <div class="text-[11px] text-slate-400">@{{ item.plan_name }}</div>
                                        </td>
                                        <td class="p-3 font-extrabold text-slate-900 dark:text-white">
                                            $@{{ formatMoney(item.net_premium) }}/mes
                                        </td>
                                        <td class="p-3">
                                            <span
                                                v-if="item.oep_renewal_status === 'renewed_same_carrier'"
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                            >
                                                🟢 Mismo Carrier
                                            </span>
                                            <span
                                                v-else-if="item.oep_renewal_status === 'renewed_cross_carrier'"
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300"
                                            >
                                                🔵 Cross-Carrier
                                            </span>
                                            <span
                                                v-else-if="item.oep_renewal_status === 'cancelled'"
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300"
                                            >
                                                🔴 Cancelada
                                            </span>
                                            <span
                                                v-else
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                                            >
                                                🟡 Pendiente
                                            </span>
                                        </td>
                                        <td class="p-3 text-right">
                                            <button
                                                type="button"
                                                @click="openRenewalComparator(item)"
                                                class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-bold transition-colors inline-flex items-center gap-1"
                                            >
                                                ⚖️ Comparar / Renovar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                            <button
                                type="button"
                                @click="showOepHubModal = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 rounded-lg text-xs font-bold"
                            >
                                @lang('admin::insurance.renewals.btn_close')
                            </button>
                        </div>
                    </div>
                </div>

                <!-- YEAR-OVER-YEAR RENEWAL COMPARATOR MODAL -->
                <div v-if="showRenewalModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-4xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[92vh] flex flex-col">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>⚖️</span> @lang('admin::insurance.renewals.modal_title')
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Asegurado: <strong>@{{ activePolicy.client_name || (activePolicy.person ? activePolicy.person.name : activePolicy.policy_number) }}</strong>
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="showRenewalModal = false"
                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold"
                            >
                                ✕
                            </button>
                        </div>

                        <div class="py-4 space-y-4 overflow-y-auto flex-1">
                            <!-- Side-by-Side Comparison Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Prior Policy Column (Base) -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-3">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800">
                                        <span class="text-xs font-extrabold uppercase text-slate-500">
                                            @lang('admin::insurance.renewals.prior_year_col')
                                        </span>
                                        <span class="text-xs font-bold text-purple-600 font-mono">
                                            @{{ activePolicy.policy_number }}
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">@lang('admin::insurance.renewals.carrier')</span>
                                        <div class="text-xs font-bold text-slate-800 dark:text-white">@{{ activePolicy.carrier_name }}</div>
                                    </div>

                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">@lang('admin::insurance.renewals.plan_name')</span>
                                        <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">@{{ activePolicy.plan_name }}</div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase">@lang('admin::insurance.renewals.metal_tier')</span>
                                            <div class="capitalize font-bold text-slate-700 dark:text-slate-300">@{{ activePolicy.metal_tier }}</div>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase">Año Cobertura</span>
                                            <div class="font-bold text-slate-700 dark:text-slate-300">@{{ activePolicy.plan_year || (activePolicy.effective_date ? activePolicy.effective_date.substring(0,4) : 'Base') }}</div>
                                        </div>
                                    </div>

                                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1.5 text-xs">
                                        <div class="flex justify-between">
                                            <span class="text-slate-500">@lang('admin::insurance.renewals.gross_premium'):</span>
                                            <span class="font-bold text-slate-800 dark:text-white">$@{{ formatMoney(activePolicy.gross_premium) }}/m</span>
                                        </div>
                                        <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                                            <span>@lang('admin::insurance.renewals.subsidy'):</span>
                                            <span class="font-bold">-$@{{ formatMoney(activePolicy.aptc_subsidy) }}/m</span>
                                        </div>
                                        <div class="flex justify-between text-sm font-extrabold text-slate-900 dark:text-white pt-1 border-t border-slate-200 dark:border-slate-800">
                                            <span>@lang('admin::insurance.renewals.net_premium'):</span>
                                            <span>$@{{ formatMoney(activePolicy.net_premium) }}/m</span>
                                        </div>
                                        <div class="flex justify-between text-[11px] text-slate-500 pt-1">
                                            <span>@lang('admin::insurance.renewals.deductible'):</span>
                                            <span>$@{{ formatMoney(activePolicy.deductible || 0) }}</span>
                                        </div>
                                        <div class="flex justify-between text-[11px] text-slate-500">
                                            <span>@lang('admin::insurance.renewals.max_out_of_pocket'):</span>
                                            <span>$@{{ formatMoney(activePolicy.max_out_of_pocket || 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- New Year Renewal Form Column -->
                                <div class="p-4 rounded-xl border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/20 dark:bg-indigo-950/20 space-y-3">
                                    <div class="flex items-center justify-between pb-2 border-b border-indigo-100 dark:border-indigo-900/50">
                                        <span class="text-xs font-extrabold uppercase text-indigo-700 dark:text-indigo-400">
                                            @lang('admin::insurance.renewals.renewed_year_col')
                                        </span>
                                        <span class="text-xs font-bold text-indigo-600">
                                            Año @{{ renewalForm.plan_year }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase mb-0.5">Año Plan</label>
                                            <input
                                                type="number"
                                                v-model="renewalForm.plan_year"
                                                class="w-full text-xs font-bold border border-slate-300 dark:border-slate-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase mb-0.5">@lang('admin::insurance.renewals.metal_tier')</label>
                                            <select
                                                v-model="renewalForm.metal_tier"
                                                class="w-full text-xs font-bold border border-slate-300 dark:border-slate-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white"
                                            >
                                                <option value="bronze">Bronce</option>
                                                <option value="silver">Plata (Silver CSR)</option>
                                                <option value="gold">Oro (Gold)</option>
                                                <option value="platinum">Platino</option>
                                                <option value="catastrophic">Catastrófico</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase mb-0.5">@lang('admin::insurance.renewals.carrier')</label>
                                        <input
                                            type="text"
                                            v-model="renewalForm.carrier_name"
                                            required
                                            class="w-full text-xs font-bold border border-slate-300 dark:border-slate-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase mb-0.5">@lang('admin::insurance.renewals.plan_name')</label>
                                        <input
                                            type="text"
                                            v-model="renewalForm.plan_name"
                                            required
                                            class="w-full text-xs font-bold border border-slate-300 dark:border-slate-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white"
                                        />
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase mb-0.5">Prima Bruta ($)</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                v-model="renewalForm.gross_premium"
                                                @input="updateRenewalPreview"
                                                class="w-full text-xs font-bold border border-slate-300 dark:border-slate-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase mb-0.5">Subsidio APTC ($)</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                v-model="renewalForm.aptc_subsidy"
                                                @input="updateRenewalPreview"
                                                class="w-full text-xs font-bold border border-slate-300 dark:border-slate-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white text-emerald-600"
                                            />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase mb-0.5">Prima Neta</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                v-model="renewalForm.net_premium"
                                                class="w-full text-xs font-extrabold border border-indigo-300 dark:border-indigo-700 rounded-lg p-2 bg-indigo-50/50 dark:bg-indigo-950/50 dark:text-white text-indigo-900"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase mb-0.5">Deducible</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                v-model="renewalForm.deductible"
                                                class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase mb-0.5">MOOP</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                v-model="renewalForm.max_out_of_pocket"
                                                class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Variance Analysis Badge Card -->
                            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
                                <div>
                                    <div class="font-bold text-slate-700 dark:text-slate-200">
                                        @lang('admin::insurance.renewals.variance_title'):
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <!-- Net Premium Variance -->
                                        <span
                                            v-if="calculatedVariance.net_diff < 0"
                                            class="px-2 py-1 rounded-md text-xs font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                        >
                                            🟢 Ahorro Cliente: -$@{{ formatMoney(Math.abs(calculatedVariance.net_diff)) }}/mes
                                        </span>
                                        <span
                                            v-else-if="calculatedVariance.net_diff > 0"
                                            class="px-2 py-1 rounded-md text-xs font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300"
                                        >
                                            🔴 Incremento: +$@{{ formatMoney(calculatedVariance.net_diff) }}/mes
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-1 rounded-md text-xs font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            ⚪ Misma Prima Neta ($0.00)
                                        </span>

                                        <!-- Carrier Status -->
                                        <span
                                            v-if="calculatedVariance.is_carrier_changed"
                                            class="px-2 py-1 rounded-md text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300"
                                        >
                                            🔀 @lang('admin::insurance.renewals.cross_carrier_switch')
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-1 rounded-md text-xs font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300"
                                        >
                                            🛡️ @lang('admin::insurance.renewals.same_carrier_switch')
                                        </span>
                                    </div>
                                </div>

                                <div v-if="calculatedVariance.subsidy_loss_warning" class="text-xs font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 p-2 rounded-lg border border-amber-200 dark:border-amber-800">
                                    ⚠️ @lang('admin::insurance.renewals.subsidy_warning')
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                            <button
                                type="button"
                                @click="showRenewalModal = false"
                                class="px-4 py-2 border border-slate-300 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold"
                            >
                                @lang('admin::insurance.renewals.btn_close')
                            </button>
                            <button
                                type="button"
                                @click="submitRenewal"
                                :disabled="isSubmittingRenewal"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm disabled:opacity-50"
                            >
                                @{{ isSubmittingRenewal ? 'Procesando...' : '@lang('admin::insurance.renewals.btn_confirm_renewal')' }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </script>

        <script type="module">
            app.component('v-book-of-business', {
                template: '#v-book-of-business-template',

                data() {
                    return {
                        isLoading: true,
                        isScanning: false,
                        metrics: {},
                        policies: [],
                        statusFilter: 'all',
                        searchTerm: '',
                        showPaymentModal: false,
                        showBinderModal: false,
                        showCasesModal: false,
                        showNewCaseForm: false,
                        showOepHubModal: false,
                        showRenewalModal: false,
                        isLoadingOepHub: false,
                        isSubmittingRenewal: false,
                        oepCohortYear: new Date().getMonth() >= 9 ? new Date().getFullYear() + 1 : new Date().getFullYear(),
                        oepData: {
                            kpis: {},
                            policies: [],
                        },
                        policyCases: [],
                        isLoadingCases: false,
                        isSubmittingCase: false,
                        caseForm: {
                            category: 'tax_1095a',
                            priority: 'normal',
                            subject: '',
                            description: '',
                            is_shared_with_client: false,
                        },
                        renewalForm: {
                            plan_year: 2026,
                            carrier_name: '',
                            plan_name: '',
                            metal_tier: 'silver',
                            network_type: 'HMO',
                            gross_premium: 0,
                            aptc_subsidy: 0,
                            net_premium: 0,
                            deductible: 0,
                            max_out_of_pocket: 0,
                            notes: '',
                        },
                        activePolicy: {},
                        paymentForm: {
                            paid_to_date: '',
                            notes: '',
                        },
                        binderForm: {
                            confirmation_number: '',
                            payment_method: 'carrier_portal',
                            notes: '',
                        },
                        searchTimeout: null,
                    }
                },

                mounted() {
                    this.loadPolicies();
                },

                methods: {
                    formatMoney(val) {
                        return (parseFloat(val) || 0).toFixed(2);
                    },

                    loadPolicies() {
                        this.isLoading = true;
                        const params = {
                            status: this.statusFilter,
                            search: this.searchTerm,
                        };

                        this.$axios.get("{{ route('admin.policies.index') }}", { params })
                            .then(res => {
                                this.isLoading = false;
                                this.metrics = res.data.metrics || {};
                                this.policies = (res.data.policies && res.data.policies.data) ? res.data.policies.data : [];
                            })
                            .catch(err => {
                                this.isLoading = false;
                                console.error('Error loading policies:', err);
                            });
                    },

                    filterStatus(status) {
                        this.statusFilter = status;
                        this.loadPolicies();
                    },

                    debounceSearch() {
                        clearTimeout(this.searchTimeout);
                        this.searchTimeout = setTimeout(() => {
                            this.loadPolicies();
                        }, 300);
                    },

                    scanGracePeriods() {
                        this.isScanning = true;
                        this.$axios.post("{{ route('admin.policies.scan_grace_periods') }}")
                            .then(res => {
                                this.isScanning = false;
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: res.data.message || 'Escaneo completado',
                                });
                                this.loadPolicies();
                            })
                            .catch(err => {
                                this.isScanning = false;
                                alert('Error al escanear períodos de gracia');
                            });
                    },

                    openPaymentModal(policy) {
                        this.activePolicy = policy;
                        // Default paid_to to end of next month
                        const d = new Date();
                        d.setMonth(d.getMonth() + 1);
                        d.setDate(0); // Last day of next month
                        this.paymentForm.paid_to_date = d.toISOString().split('T')[0];
                        this.paymentForm.notes = '';
                        this.showPaymentModal = true;
                    },

                    submitPayment() {
                        if (! this.paymentForm.paid_to_date) {
                            alert('Ingrese la fecha');
                            return;
                        }

                        this.$axios.post("{{ route('admin.policies.record_payment', ['id' => 'xxx']) }}".replace('xxx', this.activePolicy.id), this.paymentForm)
                            .then(res => {
                                this.showPaymentModal = false;
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: res.data.message,
                                });
                                this.loadPolicies();
                            })
                            .catch(err => {
                                alert(err?.response?.data?.message || 'Error al registrar el pago');
                            });
                    },

                    openBinderModal(policy) {
                        this.activePolicy = policy;
                        this.binderForm.confirmation_number = '';
                        this.binderForm.payment_method = 'carrier_portal';
                        this.binderForm.notes = '';
                        this.showBinderModal = true;
                    },

                    submitBinderPayment() {
                        this.$axios.post("{{ route('admin.policies.record_binder_payment', ['id' => 'xxx']) }}".replace('xxx', this.activePolicy.id), this.binderForm)
                            .then(res => {
                                this.showBinderModal = false;
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: res.data.message,
                                });
                                this.loadPolicies();
                            })
                            .catch(err => {
                                alert(err?.response?.data?.message || 'Error al registrar pago inicial (Binder)');
                            });
                    },

                    sendWhatsAppReminder(policyId) {
                        this.$axios.get("{{ route('admin.policies.whatsapp_reminder', ['id' => 'xxx']) }}".replace('xxx', policyId))
                            .then(res => {
                                if (res.data.whatsapp_url) {
                                    window.open(res.data.whatsapp_url, '_blank');
                                }
                            })
                            .catch(err => {
                                alert('Error al generar enlace de WhatsApp');
                            });
                    },

                    renewPolicy(policyId) {
                        if (! confirm('¿Desea marcar esta póliza como renovada para el próximo año de cobertura?')) {
                            return;
                        }

                        this.$axios.post("{{ route('admin.policies.renew', ['id' => 'xxx']) }}".replace('xxx', policyId))
                            .then(res => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: res.data.message,
                                });
                                this.loadPolicies();
                            })
                            .catch(err => {
                                alert('Error al renovar la póliza');
                            });
                    },

                    openCasesModal(policy) {
                        this.activePolicy = policy;
                        this.showCasesModal = true;
                        this.showNewCaseForm = false;
                        this.loadCasesForPolicy(policy.id);
                    },

                    loadCasesForPolicy(policyId) {
                        this.isLoadingCases = true;
                        this.$axios.get(`/admin/policies/${policyId}/service-cases`)
                            .then(res => {
                                this.isLoadingCases = false;
                                this.policyCases = res.data.cases || [];
                            })
                            .catch(err => {
                                this.isLoadingCases = false;
                                console.error('Error loading service cases:', err);
                            });
                    },

                    createServiceCase() {
                        this.isSubmittingCase = true;
                        const formData = new FormData();
                        formData.append('category', this.caseForm.category);
                        formData.append('priority', this.caseForm.priority);
                        formData.append('subject', this.caseForm.subject);
                        formData.append('description', this.caseForm.description || '');
                        formData.append('is_shared_with_client', this.caseForm.is_shared_with_client ? 1 : 0);

                        if (this.$refs.caseAttachment && this.$refs.caseAttachment.files[0]) {
                            formData.append('attachment', this.$refs.caseAttachment.files[0]);
                        }

                        this.$axios.post(`/admin/policies/${this.activePolicy.id}/service-cases`, formData, {
                            headers: { 'Content-Type': 'multipart/form-data' }
                        })
                            .then(res => {
                                this.isSubmittingCase = false;
                                this.showNewCaseForm = false;
                                this.caseForm.subject = '';
                                this.caseForm.description = '';
                                this.caseForm.is_shared_with_client = false;
                                if (this.$refs.caseAttachment) {
                                    this.$refs.caseAttachment.value = '';
                                }
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: res.data.message || 'Caso creado exitosamente',
                                });
                                this.loadCasesForPolicy(this.activePolicy.id);
                            })
                            .catch(err => {
                                this.isSubmittingCase = false;
                                alert(err?.response?.data?.message || 'Error al crear el caso de servicio');
                            });
                    },

                    updateCaseStatus(caseId, newStatus) {
                        this.$axios.put(`/admin/service-cases/${caseId}`, { status: newStatus })
                            .then(res => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: res.data.message || 'Estado actualizado',
                                });
                                this.loadCasesForPolicy(this.activePolicy.id);
                            })
                            .catch(err => {
                                alert(err?.response?.data?.message || 'Error al actualizar estado del caso');
                            });
                    },

                    openOepHub() {
                        this.showOepHubModal = true;
                        this.loadOepHubData();
                    },

                    loadOepHubData() {
                        this.isLoadingOepHub = true;
                        this.$axios.get("{{ route('admin.policies.renewals.hub') }}", {
                            params: { plan_year: this.oepCohortYear }
                        })
                            .then(res => {
                                this.isLoadingOepHub = false;
                                this.oepData = res.data.data || { kpis: {}, policies: [] };
                            })
                            .catch(err => {
                                this.isLoadingOepHub = false;
                                console.error('Error loading OEP hub data:', err);
                            });
                    },

                    openRenewalComparator(policy) {
                        this.activePolicy = policy;
                        const priorYear = policy.plan_year || (policy.effective_date ? parseInt(policy.effective_date.substring(0, 4)) : new Date().getFullYear());
                        this.renewalForm.plan_year = priorYear + 1;
                        this.renewalForm.carrier_name = policy.carrier_name || '';
                        this.renewalForm.plan_name = policy.plan_name || '';
                        this.renewalForm.metal_tier = policy.metal_tier || 'silver';
                        this.renewalForm.network_type = policy.network_type || 'HMO';
                        this.renewalForm.gross_premium = parseFloat(policy.gross_premium || 0);
                        this.renewalForm.aptc_subsidy = parseFloat(policy.aptc_subsidy || 0);
                        this.renewalForm.net_premium = parseFloat(policy.net_premium || 0);
                        this.renewalForm.deductible = parseFloat(policy.deductible || 0);
                        this.renewalForm.max_out_of_pocket = parseFloat(policy.max_out_of_pocket || 0);
                        this.renewalForm.notes = '';
                        this.showRenewalModal = true;
                    },

                    updateRenewalPreview() {
                        const gross = parseFloat(this.renewalForm.gross_premium || 0);
                        const subsidy = parseFloat(this.renewalForm.aptc_subsidy || 0);
                        this.renewalForm.net_premium = Math.max(0, Math.round((gross - subsidy) * 100) / 100);
                    },

                    submitRenewal() {
                        if (! this.renewalForm.carrier_name || ! this.renewalForm.plan_name) {
                            alert('Ingrese aseguradora y nombre del plan');
                            return;
                        }

                        this.isSubmittingRenewal = true;
                        const url = "{{ route('admin.policies.process_renewal', ['id' => 'xxx']) }}".replace('xxx', this.activePolicy.id);

                        this.$axios.post(url, this.renewalForm)
                            .then(res => {
                                this.isSubmittingRenewal = false;
                                this.showRenewalModal = false;
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: res.data.message || 'Renovación procesada con éxito',
                                });
                                this.loadPolicies();
                                if (this.showOepHubModal) {
                                    this.loadOepHubData();
                                }
                            })
                            .catch(err => {
                                this.isSubmittingRenewal = false;
                                alert(err?.response?.data?.message || 'Error al procesar la renovación');
                            });
                    }
                },

                computed: {
                    calculatedVariance() {
                        const priorGross = parseFloat(this.activePolicy.gross_premium || 0);
                        const priorSubsidy = parseFloat(this.activePolicy.aptc_subsidy || 0);
                        const priorNet = parseFloat(this.activePolicy.net_premium || 0);

                        const newGross = parseFloat(this.renewalForm.gross_premium || 0);
                        const newSubsidy = parseFloat(this.renewalForm.aptc_subsidy || 0);
                        const newNet = parseFloat(this.renewalForm.net_premium || 0);

                        const netDiff = Math.round((newNet - priorNet) * 100) / 100;
                        const subsidyDiff = Math.round((newSubsidy - priorSubsidy) * 100) / 100;
                        const isCarrierChanged = (this.renewalForm.carrier_name || '').trim().toLowerCase() !== (this.activePolicy.carrier_name || '').trim().toLowerCase();

                        return {
                            net_diff: netDiff,
                            subsidy_diff: subsidyDiff,
                            is_carrier_changed: isCarrierChanged,
                            subsidy_loss_warning: subsidyDiff < -50 || (priorNet === 0 && newNet > 0),
                        };
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>

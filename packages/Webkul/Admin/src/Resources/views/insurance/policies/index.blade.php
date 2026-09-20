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
                                            v-if="policy.status !== 'renewed'"
                                            type="button"
                                            @click="renewPolicy(policy.id)"
                                            class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded text-xs font-bold transition-colors"
                                            title="{{ trans('admin::insurance.policies.btn_renew') }}"
                                        >
                                            @lang('admin::insurance.policies.btn_renew')
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
                        activePolicy: {},
                        paymentForm: {
                            paid_to_date: '',
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
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>

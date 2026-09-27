<x-admin::layouts>
    <x-slot:title>
        @lang('admin::insurance.ledger.title')
    </x-slot>

    <v-agent-ledger></v-agent-ledger>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-agent-ledger-template">
            <div class="flex flex-col gap-6 p-4">
                <!-- Page Header -->
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5 dark:border-slate-800">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">📒</span>
                            <div>
                                <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    @lang('admin::insurance.ledger.title')
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                        Audit & Clawbacks Engine
                                    </span>
                                </h1>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    @lang('admin::insurance.ledger.subtitle')
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route('admin.commissions.index') }}"
                            class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs transition-colors"
                        >
                            ← Volver a Comisiones
                        </a>

                        <button
                            type="button"
                            @click="loadLedgerData"
                            class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs transition-colors shadow-sm"
                        >
                            🔄 Refrescar Libro Mayor
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Payable -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.ledger.kpi_total_payable')</span>
                            <span class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center font-bold">
                                💵
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">
                            $@{{ formatMoney(kpis.total_payable_balance) }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            Saldo neto a favor de productores
                        </div>
                    </div>

                    <!-- Negative Debt (Clawbacks at Risk) -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.ledger.kpi_negative_debt')</span>
                            <span class="w-8 h-8 rounded-full bg-rose-50 dark:bg-rose-950/50 text-rose-600 flex items-center justify-center font-bold">
                                ⚠️
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 mt-2">
                            $@{{ formatMoney(kpis.total_negative_debt) }}
                        </div>
                        <div class="text-xs text-rose-600/80 dark:text-rose-400 mt-1">
                            <strong>@{{ kpis.agents_with_debt || 0 }}</strong> @lang('admin::insurance.ledger.kpi_agents_with_debt')
                        </div>
                    </div>

                    <!-- All-Time Earned -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.ledger.kpi_total_earned')</span>
                            <span class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 flex items-center justify-center font-bold">
                                📈
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">
                            $@{{ formatMoney(kpis.total_earned_all_time) }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            Comisiones y sobrecomisiones devengadas
                        </div>
                    </div>

                    <!-- All-Time Clawbacks -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.ledger.kpi_total_clawbacks')</span>
                            <span class="w-8 h-8 rounded-full bg-amber-50 dark:bg-amber-950/50 text-amber-600 flex items-center justify-center font-bold">
                                🔄
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">
                            $@{{ formatMoney(kpis.total_clawbacks_all_time) }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            Cargos por retrocesión o cancelaciones
                        </div>
                    </div>
                </div>

                <!-- Agent Balances Table -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>👥</span> Saldos Corrientes de Agentes
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 font-bold uppercase text-[10px]">
                                    <th class="p-3.5">@lang('admin::insurance.ledger.col_agent')</th>
                                    <th class="p-3.5">@lang('admin::insurance.ledger.col_earned')</th>
                                    <th class="p-3.5">@lang('admin::insurance.ledger.col_clawbacks')</th>
                                    <th class="p-3.5">@lang('admin::insurance.ledger.col_paid_out')</th>
                                    <th class="p-3.5">@lang('admin::insurance.ledger.col_balance')</th>
                                    <th class="p-3.5">@lang('admin::insurance.ledger.col_status')</th>
                                    <th class="p-3.5 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                <tr v-if="isLoading" class="text-center">
                                    <td colspan="7" class="p-8 text-slate-400">Cargando libro mayor...</td>
                                </tr>
                                <tr v-else-if="!balances.length" class="text-center">
                                    <td colspan="7" class="p-8 text-slate-400">No hay cuentas de agentes registradas aún.</td>
                                </tr>
                                <tr v-for="b in balances" :key="b.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                    <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                                        <div>@{{ b.user ? b.user.name : 'Agente #' + b.user_id }}</div>
                                        <div class="text-[11px] font-normal text-slate-400">@{{ b.user ? b.user.email : '' }}</div>
                                    </td>
                                    <td class="p-3.5 font-bold text-slate-700 dark:text-slate-300">
                                        $@{{ formatMoney(b.total_earned) }}
                                    </td>
                                    <td class="p-3.5 font-bold text-amber-600 dark:text-amber-400">
                                        -$@{{ formatMoney(b.total_clawbacks) }}
                                    </td>
                                    <td class="p-3.5 font-bold text-slate-500">
                                        $@{{ formatMoney(b.total_paid_out) }}
                                    </td>
                                    <td class="p-3.5">
                                        <span
                                            :class="b.current_balance < 0 ? 'text-rose-600 dark:text-rose-400 font-black' : 'text-emerald-600 dark:text-emerald-400 font-black'"
                                            class="text-sm"
                                        >
                                            $@{{ formatMoney(b.current_balance) }}
                                        </span>
                                    </td>
                                    <td class="p-3.5">
                                        <span
                                            v-if="b.current_balance < 0"
                                            class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300"
                                        >
                                            @lang('admin::insurance.ledger.status_negative_alert')
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300"
                                        >
                                            @lang('admin::insurance.ledger.status_in_good_standing')
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-right space-x-1">
                                        <button
                                            type="button"
                                            @click="openHistory(b)"
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 rounded text-xs font-bold transition-colors inline-flex items-center gap-1"
                                            title="{{ trans('admin::insurance.ledger.btn_view_ledger') }}"
                                        >
                                            📜 @lang('admin::insurance.ledger.btn_view_ledger')
                                        </button>

                                        <button
                                            v-if="b.current_balance > 0"
                                            type="button"
                                            @click="openDisburseModal(b)"
                                            class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold transition-colors inline-flex items-center gap-1"
                                            title="{{ trans('admin::insurance.ledger.btn_disburse') }}"
                                        >
                                            💸 @lang('admin::insurance.ledger.btn_disburse')
                                        </button>

                                        <button
                                            type="button"
                                            @click="openClawbackModal(b)"
                                            class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 rounded text-xs font-bold transition-colors inline-flex items-center gap-1"
                                            title="{{ trans('admin::insurance.ledger.btn_clawback') }}"
                                        >
                                            ⚠️ @lang('admin::insurance.ledger.btn_clawback')
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TRANSACTION HISTORY DRAWER / MODAL -->
                <div v-if="showHistoryModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-4xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] flex flex-col">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>📜</span> Libro Mayor: @{{ activeAgent.name }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Saldo Disponible: <strong>$@{{ formatMoney(activeAgentBalance.current_balance) }}</strong>
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="showHistoryModal = false"
                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold"
                            >
                                ✕
                            </button>
                        </div>

                        <div class="overflow-y-auto flex-1 py-4">
                            <div v-if="isLoadingHistory" class="py-12 text-center text-xs text-slate-400">
                                Cargando movimientos del libro mayor...
                            </div>
                            <table v-else class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 font-bold uppercase text-[10px]">
                                        <th class="p-2.5">Fecha</th>
                                        <th class="p-2.5">Tipo de Movimiento</th>
                                        <th class="p-2.5">Póliza / Referencia</th>
                                        <th class="p-2.5">Descripción</th>
                                        <th class="p-2.5 text-right">Monto</th>
                                        <th class="p-2.5 text-right">Saldo Posterior</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                    <tr v-for="t in historyTransactions" :key="t.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                        <td class="p-2.5 font-mono text-[11px] text-slate-500">
                                            @{{ t.created_at ? t.created_at.substring(0, 10) : '' }}
                                        </td>
                                        <td class="p-2.5">
                                            <span
                                                :class="{
                                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300': t.amount > 0,
                                                    'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300': t.amount < 0 && t.transaction_type === 'clawback_debit',
                                                    'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300': t.transaction_type === 'payout_disbursement'
                                                }"
                                                class="px-2 py-0.5 rounded text-[10px] font-bold"
                                            >
                                                @{{ t.type_label }}
                                            </span>
                                        </td>
                                        <td class="p-2.5 font-mono font-bold text-slate-700 dark:text-slate-300">
                                            @{{ t.policy_number || t.reference_code || '--' }}
                                        </td>
                                        <td class="p-2.5 text-slate-600 dark:text-slate-400">
                                            @{{ t.description }}
                                        </td>
                                        <td class="p-2.5 text-right font-extrabold" :class="t.amount > 0 ? 'text-emerald-600' : 'text-rose-600'">
                                            @{{ t.amount > 0 ? '+$' : '-$' }}@{{ formatMoney(Math.abs(t.amount)) }}
                                        </td>
                                        <td class="p-2.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                            $@{{ formatMoney(t.balance_after) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                            <button
                                type="button"
                                @click="showHistoryModal = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 rounded-lg text-xs font-bold"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CLAWBACK MODAL -->
                <div v-if="showClawbackModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">
                            @lang('admin::insurance.ledger.modal_clawback_title')
                        </h3>
                        <p class="text-xs text-slate-500 mb-4">
                            Agente: <strong>@{{ activeAgent.name }}</strong> (Saldo actual: $@{{ formatMoney(activeAgentBalance.current_balance) }})
                        </p>

                        <form @submit.prevent="submitClawback" class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 required">Número de Póliza</label>
                                <input
                                    type="text"
                                    v-model="clawbackForm.policy_number"
                                    required
                                    placeholder="Ej. POL-FLB-987654"
                                    class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 required">Monto de Clawback ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="clawbackForm.amount"
                                    required
                                    placeholder="0.00"
                                    class="w-full text-xs font-bold border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white text-rose-600"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Aseguradora</label>
                                <input
                                    type="text"
                                    v-model="clawbackForm.carrier_name"
                                    placeholder="Florida Blue, Ambetter, etc."
                                    class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Motivo / Razón</label>
                                <textarea
                                    v-model="clawbackForm.reason"
                                    rows="2"
                                    placeholder="Cancelación anticipada por falta de pago del cliente..."
                                    class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                ></textarea>
                            </div>

                            <div class="flex gap-2 pt-2">
                                <button
                                    type="button"
                                    @click="showClawbackModal = false"
                                    class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 font-bold rounded-lg text-xs"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs transition-colors shadow-sm disabled:opacity-50"
                                >
                                    @{{ isSubmitting ? 'Aplicando...' : 'Aplicar Clawback' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- DISBURSE PAYOUT MODAL -->
                <div v-if="showDisburseModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">
                            @lang('admin::insurance.ledger.modal_disburse_title')
                        </h3>
                        <p class="text-xs text-slate-500 mb-4">
                            Agente: <strong>@{{ activeAgent.name }}</strong> (Saldo Disponible: <strong class="text-emerald-600">$@{{ formatMoney(activeAgentBalance.current_balance) }}</strong>)
                        </p>

                        <form @submit.prevent="submitDisburse" class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 required">Monto a Liquidar ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="disburseForm.amount"
                                    :max="activeAgentBalance.current_balance"
                                    required
                                    class="w-full text-xs font-extrabold border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white text-emerald-600"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 required">Código / Referencia de Pago</label>
                                <input
                                    type="text"
                                    v-model="disburseForm.reference_code"
                                    required
                                    placeholder="Ej. ACH-FLB-2026-03-001"
                                    class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Notas</label>
                                <textarea
                                    v-model="disburseForm.notes"
                                    rows="2"
                                    placeholder="Pago procesado por transferencia bancaria ACH..."
                                    class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                ></textarea>
                            </div>

                            <div class="flex gap-2 pt-2">
                                <button
                                    type="button"
                                    @click="showDisburseModal = false"
                                    class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 font-bold rounded-lg text-xs"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition-colors shadow-sm disabled:opacity-50"
                                >
                                    @{{ isSubmitting ? 'Procesando...' : 'Confirmar Desembolso' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </script>

        <script type="module">
            app.component('v-agent-ledger', {
                template: '#v-agent-ledger-template',

                data() {
                    return {
                        isLoading: true,
                        isSubmitting: false,
                        isLoadingHistory: false,
                        kpis: {},
                        balances: [],
                        showHistoryModal: false,
                        showClawbackModal: false,
                        showDisburseModal: false,
                        activeAgent: {},
                        activeAgentBalance: {},
                        historyTransactions: [],
                        clawbackForm: {
                            policy_number: '',
                            amount: '',
                            carrier_name: '',
                            reason: '',
                        },
                        disburseForm: {
                            amount: '',
                            reference_code: '',
                            notes: '',
                        },
                    };
                },

                mounted() {
                    this.loadLedgerData();
                },

                methods: {
                    formatMoney(val) {
                        return (parseFloat(val) || 0).toFixed(2);
                    },

                    loadLedgerData() {
                        this.isLoading = true;
                        this.$axios.get("{{ route('admin.insurance.ledger.index') }}")
                            .then(res => {
                                this.isLoading = false;
                                const data = res.data.data || {};
                                this.kpis = data.kpis || {};
                                this.balances = data.balances || [];
                            })
                            .catch(err => {
                                this.isLoading = false;
                                console.error('Error loading ledger:', err);
                            });
                    },

                    openHistory(balance) {
                        this.activeAgent = balance.user || { id: balance.user_id, name: 'Agente #' + balance.user_id };
                        this.activeAgentBalance = balance;
                        this.showHistoryModal = true;
                        this.isLoadingHistory = true;

                        const url = "{{ route('admin.insurance.ledger.agent_history', ['userId' => 'xxx']) }}".replace('xxx', balance.user_id);
                        this.$axios.get(url)
                            .then(res => {
                                this.isLoadingHistory = false;
                                this.historyTransactions = (res.data.transactions && res.data.transactions.data) ? res.data.transactions.data : [];
                            })
                            .catch(err => {
                                this.isLoadingHistory = false;
                                console.error('Error loading transactions:', err);
                            });
                    },

                    openClawbackModal(balance) {
                        this.activeAgent = balance.user || { id: balance.user_id, name: 'Agente #' + balance.user_id };
                        this.activeAgentBalance = balance;
                        this.clawbackForm = {
                            policy_number: '',
                            amount: '',
                            carrier_name: '',
                            reason: '',
                        };
                        this.showClawbackModal = true;
                    },

                    submitClawback() {
                        this.isSubmitting = true;
                        const url = "{{ route('admin.insurance.ledger.clawback', ['userId' => 'xxx']) }}".replace('xxx', this.activeAgentBalance.user_id);

                        this.$axios.post(url, this.clawbackForm)
                            .then(res => {
                                this.isSubmitting = false;
                                this.showClawbackModal = false;
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: res.data.message || 'Clawback aplicado',
                                });
                                this.loadLedgerData();
                            })
                            .catch(err => {
                                this.isSubmitting = false;
                                alert(err?.response?.data?.message || 'Error al aplicar clawback');
                            });
                    },

                    openDisburseModal(balance) {
                        this.activeAgent = balance.user || { id: balance.user_id, name: 'Agente #' + balance.user_id };
                        this.activeAgentBalance = balance;
                        this.disburseForm = {
                            amount: balance.current_balance,
                            reference_code: 'ACH-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000),
                            notes: '',
                        };
                        this.showDisburseModal = true;
                    },

                    submitDisburse() {
                        this.isSubmitting = true;
                        const url = "{{ route('admin.insurance.ledger.disburse', ['userId' => 'xxx']) }}".replace('xxx', this.activeAgentBalance.user_id);

                        this.$axios.post(url, this.disburseForm)
                            .then(res => {
                                this.isSubmitting = false;
                                this.showDisburseModal = false;
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: res.data.message || 'Desembolso liquidado con éxito',
                                });
                                this.loadLedgerData();
                            })
                            .catch(err => {
                                this.isSubmitting = false;
                                alert(err?.response?.data?.message || 'Error al liquidar pago');
                            });
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::insurance.commissions.title')
    </x-slot>

    <v-insurance-commissions
        :initial-commissions='@json($commissions)'
        :initial-rates='@json($rates)'
        :agents='@json($agents)'
        :initial-kpis='@json([
            "total_gross_monthly" => $totalGrossMonthly,
            "total_agent_monthly" => $totalAgentMonthly,
            "total_agency_monthly" => $totalAgencyMonthly,
            "active_policies" => $activePoliciesCount
        ])'
    ></v-insurance-commissions>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-insurance-commissions-template">
            <div class="flex flex-col gap-6 p-4">
                <!-- Page Header -->
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-5 dark:border-gray-800">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">💰</span>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    Comisiones de Seguros (ACA / Salud)
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        PMPM Ledger 2026
                                    </span>
                                </h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Control mensual de comisiones por miembro, distribución de splits entre agencia y agentes productores.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route('admin.commissions.reconciliation.index') }}"
                            class="secondary-button text-xs py-2 px-3 flex items-center gap-1.5 font-semibold text-blue-700 dark:text-blue-300"
                        >
                            <span>⚖️</span>
                            Reconciliador de Statements
                        </a>

                        <button
                            type="button"
                            @click="showRateModal = true"
                            class="secondary-button text-xs py-2 px-3 flex items-center gap-1.5"
                        >
                            <span>⚙️</span>
                            Tarifas por Aseguradora
                        </button>

                        <button
                            type="button"
                            @click="openCreateModal"
                            class="primary-button text-xs py-2 px-4 flex items-center gap-1.5"
                        >
                            <span>+</span>
                            Registrar Póliza Manual
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Gross -->
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm relative overflow-hidden">
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                            Proyección Bruta Mensual
                        </div>
                        <div class="text-2xl font-extrabold text-gray-900 dark:text-white">
                            $@{{ formatMoney(kpis.total_gross_monthly) }}
                        </div>
                        <div class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                            <span class="text-blue-500 font-bold">@{{ kpis.active_policies }}</span> pólizas activas en cartera
                        </div>
                        <div class="absolute right-3 top-4 text-3xl opacity-15">📈</div>
                    </div>

                    <!-- Total Agent Net -->
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-emerald-100 dark:border-emerald-900/40 shadow-sm relative overflow-hidden bg-gradient-to-br from-white to-emerald-50/20 dark:from-gray-900 dark:to-emerald-950/20">
                        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-1">
                            Comisiones Agentes (Neto)
                        </div>
                        <div class="text-2xl font-extrabold text-emerald-700 dark:text-emerald-300">
                            $@{{ formatMoney(kpis.total_agent_monthly) }}
                        </div>
                        <div class="text-xs text-emerald-600/80 mt-2 flex items-center gap-1">
                            <span>👥</span> Pago estimado a productores
                        </div>
                        <div class="absolute right-3 top-4 text-3xl opacity-15">💵</div>
                    </div>

                    <!-- Total Agency Net -->
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-blue-100 dark:border-blue-900/40 shadow-sm relative overflow-hidden bg-gradient-to-br from-white to-blue-50/20 dark:from-gray-900 dark:to-blue-950/20">
                        <div class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-1">
                            Retención / Spread Agencia
                        </div>
                        <div class="text-2xl font-extrabold text-blue-700 dark:text-blue-300">
                            $@{{ formatMoney(kpis.total_agency_monthly) }}
                        </div>
                        <div class="text-xs text-blue-600/80 mt-2 flex items-center gap-1">
                            <span>🏢</span> Ganancia neta de la agencia
                        </div>
                        <div class="absolute right-3 top-4 text-3xl opacity-15">🏛️</div>
                    </div>

                    <!-- Active Policies -->
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-purple-100 dark:border-purple-900/40 shadow-sm relative overflow-hidden">
                        <div class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400 mb-1">
                            Total Vidas Cubiertas
                        </div>
                        <div class="text-2xl font-extrabold text-purple-700 dark:text-purple-300">
                            @{{ totalMembersCount }}
                        </div>
                        <div class="text-xs text-purple-600/80 mt-2 flex items-center gap-1">
                            <span>👨‍👩‍👧‍👦</span> Miembros familiares registrados
                        </div>
                        <div class="absolute right-3 top-4 text-3xl opacity-15">🛡️</div>
                    </div>
                </div>

                <!-- Filters & Search Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-gray-900 p-3 rounded-xl border border-gray-200 dark:border-gray-800 text-xs">
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="filterCarrier"
                            class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 font-medium text-gray-700 dark:text-gray-200"
                        >
                            <option value="">Todas las Aseguradoras</option>
                            <option v-for="rate in rates" :key="rate.id" :value="rate.carrier_name">
                                @{{ rate.carrier_name }} ($@{{ rate.rate_per_member }}/m)
                            </option>
                        </select>

                        <select
                            v-model="filterAgent"
                            class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 font-medium text-gray-700 dark:text-gray-200"
                        >
                            <option value="">Todos los Agentes</option>
                            <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                                @{{ agent.name }}
                            </option>
                        </select>

                        <select
                            v-model="filterStatus"
                            class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 font-medium text-gray-700 dark:text-gray-200"
                        >
                            <option value="">Todos los Estados</option>
                            <option value="active">Activas</option>
                            <option value="pending">Pendientes</option>
                            <option value="paid">Pagadas</option>
                            <option value="cancelled">Canceladas</option>
                        </select>
                    </div>

                    <div class="text-gray-500 dark:text-gray-400">
                        Mostrando <strong>@{{ filteredCommissions.length }}</strong> de <strong>@{{ commissions.length }}</strong> registros
                    </div>
                </div>

                <!-- Commissions Table -->
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 uppercase font-semibold">
                                <tr>
                                    <th class="py-3 px-4">Póliza / Cliente</th>
                                    <th class="py-3 px-4">Aseguradora & Plan</th>
                                    <th class="py-3 px-4">Agente Productor</th>
                                    <th class="py-3 px-4 text-center">Vidas (Miembros)</th>
                                    <th class="py-3 px-4 text-right">Tarifa PMPM</th>
                                    <th class="py-3 px-4 text-right">Bruto / Mes</th>
                                    <th class="py-3 px-4 text-center">Split Agente</th>
                                    <th class="py-3 px-4 text-right text-emerald-600 dark:text-emerald-400">Neto Agente</th>
                                    <th class="py-3 px-4 text-right text-blue-600 dark:text-blue-400">Agencia</th>
                                    <th class="py-3 px-4 text-center">Estado</th>
                                    <th class="py-3 px-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-if="filteredCommissions.length === 0">
                                    <td colspan="11" class="py-12 text-center text-gray-400">
                                        <div class="text-3xl mb-2">📋</div>
                                        <div class="font-medium text-sm">No hay registros de comisiones que coincidan con los filtros.</div>
                                        <div class="text-xs mt-1">Al emitir pólizas desde las cotizaciones o agregar registros manuales se acumularán aquí.</div>
                                    </td>
                                </tr>

                                <tr
                                    v-for="comm in filteredCommissions"
                                    :key="comm.id"
                                    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors"
                                >
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-gray-900 dark:text-white">
                                            @{{ comm.policy_number || ('#POL-' + comm.id) }}
                                        </div>
                                        <div class="text-[11px] text-gray-500">
                                            @{{ comm.lead?.person?.name || comm.lead?.title || 'Cliente Directo' }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900 dark:text-white flex items-center gap-1.5">
                                            <span>🏥</span>
                                            @{{ comm.carrier_name }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 truncate max-w-xs">
                                            @{{ comm.plan_name || comm.metal_tier || 'Salud ACA' }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-800 dark:text-gray-200">
                                            @{{ comm.user?.name || 'Sin Asignar' }}
                                        </div>
                                        <div class="text-[11px] text-gray-400">
                                            @{{ comm.user?.email || '' }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold">
                                        @{{ comm.members_count }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono">
                                        $@{{ formatMoney(comm.rate_per_member) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-900 dark:text-white">
                                        $@{{ formatMoney(comm.gross_monthly) }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                            @{{ comm.agent_split_percentage }}%
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        $@{{ formatMoney(comm.agent_monthly) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-blue-600 dark:text-blue-400">
                                        $@{{ formatMoney(comm.agency_monthly) }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                            :class="getStatusBadgeClass(comm.status)"
                                        >
                                            @{{ comm.status }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                @click="openEditModal(comm)"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteCommission(comm.id)"
                                                class="text-xs text-red-500 hover:text-red-700 font-medium"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Create/Edit Modal -->
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                >
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 max-w-lg w-full overflow-hidden">
                        <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>💵</span>
                                @{{ isEditing ? 'Modificar Registro de Comisión' : 'Registrar Póliza y Comisión Manual' }}
                            </h3>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                        </div>

                        <form @submit.prevent="saveCommission" class="p-5 space-y-4 text-xs">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Aseguradora *</label>
                                    <select
                                        v-model="form.carrier_name"
                                        @change="onCarrierChange"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-medium"
                                        required
                                    >
                                        <option v-for="rate in rates" :key="rate.id" :value="rate.carrier_name">
                                            @{{ rate.carrier_name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Agente Asignado *</label>
                                    <select
                                        v-model="form.user_id"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-medium"
                                        required
                                    >
                                        <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                                            @{{ agent.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Número de Póliza</label>
                                    <input
                                        type="text"
                                        v-model="form.policy_number"
                                        placeholder="Ej: FLB-992140"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-medium"
                                    >
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Plan / Metal</label>
                                    <input
                                        type="text"
                                        v-model="form.plan_name"
                                        placeholder="Ej: Silver Simple"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-medium"
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3 bg-gray-50 dark:bg-gray-800/40 p-3 rounded-lg border border-gray-200 dark:border-gray-800">
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Miembros (Vidas)</label>
                                    <input
                                        type="number"
                                        min="1"
                                        v-model.number="form.members_count"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-bold"
                                        required
                                    >
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Tarifa PMPM ($)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model.number="form.rate_per_member"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-bold"
                                        required
                                    >
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Split Agente (%)</label>
                                    <input
                                        type="number"
                                        step="1"
                                        min="0"
                                        max="100"
                                        v-model.number="form.agent_split_percentage"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-bold text-emerald-600"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Live Split Calculator Box -->
                            <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-gray-500 block">Bruto Mensual:</span>
                                    <strong class="text-sm text-gray-900 dark:text-white">$@{{ formatMoney(computedGross) }}</strong>
                                </div>
                                <div>
                                    <span class="text-gray-500 block">Pago Agente:</span>
                                    <strong class="text-sm text-emerald-600 dark:text-emerald-400">$@{{ formatMoney(computedAgentNet) }}</strong>
                                </div>
                                <div>
                                    <span class="text-gray-500 block">Agencia:</span>
                                    <strong class="text-sm text-blue-600 dark:text-blue-400">$@{{ formatMoney(computedAgencyNet) }}</strong>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Estado de Póliza</label>
                                    <select
                                        v-model="form.status"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-medium"
                                    >
                                        <option value="active">Activa</option>
                                        <option value="pending">Pendiente</option>
                                        <option value="paid">Pagada</option>
                                        <option value="cancelled">Cancelada</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de Efectividad</label>
                                    <input
                                        type="date"
                                        v-model="form.effective_date"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-medium"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Notas / Observaciones</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2"
                                    placeholder="Comentarios sobre el enrolamiento o pago..."
                                ></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-800">
                                <button
                                    type="button"
                                    @click="showModal = false"
                                    class="secondary-button"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    class="primary-button"
                                    :disabled="isSaving"
                                >
                                    @{{ isSaving ? 'Guardando...' : (isEditing ? 'Actualizar Comisión' : 'Guardar Póliza') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Carrier Rates Settings Modal -->
                <div
                    v-if="showRateModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                >
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 max-w-md w-full overflow-hidden">
                        <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>⚙️</span>
                                Tarifas PMPM por Aseguradora (2026)
                            </h3>
                            <button @click="showRateModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                        </div>
                        <div class="p-5 space-y-3 text-xs">
                            <p class="text-gray-500 mb-2">Configure la tarifa base por miembro al mes (PMPM) pagada por cada aseguradora:</p>

                            <div
                                v-for="rate in rates"
                                :key="rate.id"
                                class="flex items-center justify-between gap-3 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-800"
                            >
                                <span class="font-semibold text-gray-900 dark:text-white">@{{ rate.carrier_name }}</span>
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-400">$</span>
                                    <input
                                        type="number"
                                        step="0.5"
                                        v-model.number="rate.rate_per_member"
                                        class="w-20 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-1 text-right font-bold"
                                    >
                                    <button
                                        type="button"
                                        @click="saveRate(rate)"
                                        class="primary-button text-[11px] py-1 px-2"
                                    >
                                        ✓
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-insurance-commissions', {
                template: '#v-insurance-commissions-template',

                props: ['initialCommissions', 'initialRates', 'agents', 'initialKpis'],

                data() {
                    return {
                        commissions: this.initialCommissions || [],
                        rates: this.initialRates || [],
                        kpis: this.initialKpis || {
                            total_gross_monthly: 0,
                            total_agent_monthly: 0,
                            total_agency_monthly: 0,
                            active_policies: 0,
                        },
                        filterCarrier: '',
                        filterAgent: '',
                        filterStatus: '',
                        showModal: false,
                        showRateModal: false,
                        isEditing: false,
                        isSaving: false,
                        editingId: null,
                        form: {
                            carrier_name: 'Florida Blue',
                            user_id: this.agents && this.agents.length ? this.agents[0].id : 1,
                            policy_number: '',
                            plan_name: '',
                            metal_tier: 'Silver',
                            members_count: 1,
                            rate_per_member: 28.0,
                            agent_split_percentage: 70.0,
                            status: 'active',
                            effective_date: new Date().toISOString().substring(0, 10),
                            notes: '',
                        },
                    };
                },

                computed: {
                    filteredCommissions() {
                        return this.commissions.filter(comm => {
                            if (this.filterCarrier && comm.carrier_name !== this.filterCarrier) return false;
                            if (this.filterAgent && comm.user_id != this.filterAgent) return false;
                            if (this.filterStatus && comm.status !== this.filterStatus) return false;
                            return true;
                        });
                    },

                    totalMembersCount() {
                        return this.commissions
                            .filter(c => c.status === 'active')
                            .reduce((sum, c) => sum + (c.members_count || 1), 0);
                    },

                    computedGross() {
                        return (this.form.rate_per_member || 0) * (this.form.members_count || 1);
                    },

                    computedAgentNet() {
                        return this.computedGross * ((this.form.agent_split_percentage || 70) / 100);
                    },

                    computedAgencyNet() {
                        return this.computedGross - this.computedAgentNet;
                    },
                },

                methods: {
                    formatMoney(val) {
                        return Number(val || 0).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        });
                    },

                    getStatusBadgeClass(status) {
                        switch (status) {
                            case 'active':
                                return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
                            case 'paid':
                                return 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300';
                            case 'pending':
                                return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
                            case 'cancelled':
                                return 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300';
                            default:
                                return 'bg-gray-100 text-gray-800';
                        }
                    },

                    onCarrierChange() {
                        const rateObj = this.rates.find(r => r.carrier_name === this.form.carrier_name);
                        if (rateObj) {
                            this.form.rate_per_member = rateObj.rate_per_member;
                        }
                    },

                    openCreateModal() {
                        this.isEditing = false;
                        this.editingId = null;
                        this.form = {
                            carrier_name: this.rates[0]?.carrier_name || 'Florida Blue',
                            user_id: this.agents[0]?.id || 1,
                            policy_number: '',
                            plan_name: '',
                            metal_tier: 'Silver',
                            members_count: 1,
                            rate_per_member: this.rates[0]?.rate_per_member || 28.0,
                            agent_split_percentage: 70.0,
                            status: 'active',
                            effective_date: new Date().toISOString().substring(0, 10),
                            notes: '',
                        };
                        this.showModal = true;
                    },

                    openEditModal(comm) {
                        this.isEditing = true;
                        this.editingId = comm.id;
                        this.form = {
                            carrier_name: comm.carrier_name,
                            user_id: comm.user_id,
                            policy_number: comm.policy_number,
                            plan_name: comm.plan_name,
                            metal_tier: comm.metal_tier || 'Silver',
                            members_count: comm.members_count,
                            rate_per_member: comm.rate_per_member,
                            agent_split_percentage: comm.agent_split_percentage,
                            status: comm.status,
                            effective_date: comm.effective_date ? comm.effective_date.substring(0, 10) : '',
                            notes: comm.notes || '',
                        };
                        this.showModal = true;
                    },

                    saveCommission() {
                        this.isSaving = true;

                        const url = this.isEditing
                            ? "{{ route('admin.commissions.update', ['id' => 'xxx']) }}".replace('xxx', this.editingId)
                            : "{{ route('admin.commissions.store') }}";

                        const method = this.isEditing ? 'put' : 'post';

                        this.$axios[method](url, this.form)
                            .then(response => {
                                this.isSaving = false;
                                this.showModal = false;

                                if (this.isEditing) {
                                    const idx = this.commissions.findIndex(c => c.id === this.editingId);
                                    if (idx !== -1) {
                                        this.commissions[idx] = response.data.commission;
                                    }
                                } else {
                                    this.commissions.unshift(response.data.commission);
                                }

                                this.recalculateKPIs();

                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                            })
                            .catch(error => {
                                this.isSaving = false;
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || 'Error al guardar comisión.',
                                });
                            });
                    },

                    deleteCommission(id) {
                        if (!confirm('¿Desea eliminar este registro de comisión?')) return;

                        const url = "{{ route('admin.commissions.destroy', ['id' => 'xxx']) }}".replace('xxx', id);

                        this.$axios.delete(url)
                            .then(response => {
                                this.commissions = this.commissions.filter(c => c.id !== id);
                                this.recalculateKPIs();

                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: 'Error al eliminar comisión.',
                                });
                            });
                    },

                    saveRate(rate) {
                        const url = "{{ route('admin.commissions.rates.update', ['id' => 'xxx']) }}".replace('xxx', rate.id);

                        this.$axios.put(url, { rate_per_member: rate.rate_per_member })
                            .then(response => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                            });
                    },

                    recalculateKPIs() {
                        const active = this.commissions.filter(c => c.status === 'active');
                        this.kpis = {
                            total_gross_monthly: active.reduce((s, c) => s + Number(c.gross_monthly || 0), 0),
                            total_agent_monthly: active.reduce((s, c) => s + Number(c.agent_monthly || 0), 0),
                            total_agency_monthly: active.reduce((s, c) => s + Number(c.agency_monthly || 0), 0),
                            active_policies: active.length,
                        };
                    },
                },
            });
        </script>
    @endpushOnce
</x-admin::layouts>

<x-admin::layouts>
    <x-slot:title>
        Reconciliador de Comisiones de Aseguradoras
    </x-slot>

    <v-commission-reconciliation
        :initial-statements='@json($statements)'
        :rates='@json($rates)'
        :initial-kpis='@json([
            "total_paid" => $totalPaidCarrier,
            "total_missed" => $totalMissedDiscovered,
            "total_statements" => $totalStatementsCount,
            "avg_match_rate" => round($averageMatchRate, 1)
        ])'
    ></v-commission-reconciliation>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-commission-reconciliation-template">
            <div class="flex flex-col gap-6 p-4">
                <!-- Page Navigation & Header -->
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-5 dark:border-gray-800">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <a
                                href="{{ route('admin.commissions.index') }}"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1"
                            >
                                <span>←</span> Volver al Libro de Comisiones PMPM
                            </a>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">⚖️</span>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    Reconciliador de Statements de Aseguradoras
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        Audit & Matching Engine
                                    </span>
                                </h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Cruce masivo de liquidaciones de aseguradoras vs. pólizas activas. Detección automática de comisiones omitidas (*missed commissions*), variaciones de pago y chargebacks.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="openUploadModal"
                            class="primary-button text-xs py-2 px-4 flex items-center gap-1.5 shadow-sm"
                        >
                            <span>📥</span>
                            Cargar Statement (CSV)
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Paid -->
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm relative overflow-hidden">
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                            Total Pagado por Carriers
                        </div>
                        <div class="text-2xl font-extrabold text-gray-900 dark:text-white">
                            $@{{ formatMoney(kpis.total_paid) }}
                        </div>
                        <div class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                            <span>🧾</span> En @{{ kpis.total_statements }} statement(s) procesados
                        </div>
                        <div class="absolute right-3 top-4 text-3xl opacity-15">💰</div>
                    </div>

                    <!-- Total Missed / Unpaid -->
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-red-200 dark:border-red-900/50 shadow-sm relative overflow-hidden bg-gradient-to-br from-white to-red-50/25 dark:from-gray-900 dark:to-red-950/20">
                        <div class="text-xs font-semibold uppercase tracking-wider text-red-600 dark:text-red-400 mb-1">
                            Comisiones Omitidas Detectadas
                        </div>
                        <div class="text-2xl font-extrabold text-red-600 dark:text-red-400">
                            $@{{ formatMoney(kpis.total_missed) }}
                        </div>
                        <div class="text-xs text-red-700 dark:text-red-300 mt-2 font-semibold flex items-center gap-1">
                            <span>🚨</span> Dinero no pagado listo para reclamar
                        </div>
                        <div class="absolute right-3 top-4 text-3xl opacity-15">⚠️</div>
                    </div>

                    <!-- Average Match Rate -->
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-emerald-100 dark:border-emerald-900/40 shadow-sm relative overflow-hidden">
                        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-1">
                            Tasa de Coincidencia Promedio
                        </div>
                        <div class="text-2xl font-extrabold text-emerald-700 dark:text-emerald-300">
                            @{{ kpis.avg_match_rate }}%
                        </div>
                        <div class="text-xs text-emerald-600/80 mt-2 flex items-center gap-1">
                            <span>✓</span> Precisión del libro activo
                        </div>
                        <div class="absolute right-3 top-4 text-3xl opacity-15">🎯</div>
                    </div>

                    <!-- Total Statements -->
                    <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-blue-100 dark:border-blue-900/40 shadow-sm relative overflow-hidden">
                        <div class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-1">
                            Archivos Reconciliados
                        </div>
                        <div class="text-2xl font-extrabold text-blue-700 dark:text-blue-300">
                            @{{ kpis.total_statements }}
                        </div>
                        <div class="text-xs text-blue-600/80 mt-2 flex items-center gap-1">
                            <span>📁</span> Historial de auditoría mensual
                        </div>
                        <div class="absolute right-3 top-4 text-3xl opacity-15">📑</div>
                    </div>
                </div>

                <!-- Statements History Table -->
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📂</span>
                            Historial de Estados de Cuenta Procesados
                        </h3>
                        <span class="text-xs text-gray-500">
                            @{{ statements.length }} archivo(s) registrados
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 uppercase font-semibold">
                                <tr>
                                    <th class="py-3 px-4">Aseguradora</th>
                                    <th class="py-3 px-4">Período</th>
                                    <th class="py-3 px-4">Archivo</th>
                                    <th class="py-3 px-4 text-center">Líneas</th>
                                    <th class="py-3 px-4 text-center">Conciliadas (100%)</th>
                                    <th class="py-3 px-4 text-center">Discrepancias</th>
                                    <th class="py-3 px-4 text-center">Omitidas</th>
                                    <th class="py-3 px-4 text-right">Total Pagado</th>
                                    <th class="py-3 px-4 text-right text-red-600 dark:text-red-400">Total Faltante</th>
                                    <th class="py-3 px-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-if="statements.length === 0">
                                    <td colspan="10" class="py-12 text-center text-gray-400">
                                        <div class="text-3xl mb-2">⚖️</div>
                                        <div class="font-medium text-sm">Aún no se ha cargado ningún statement de comisiones.</div>
                                        <div class="text-xs mt-1">Sube un archivo CSV de Florida Blue, Ambetter, Oscar, etc. para iniciar el cruce automático.</div>
                                    </td>
                                </tr>

                                <tr
                                    v-for="st in statements"
                                    :key="st.id"
                                    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors"
                                >
                                    <td class="py-3 px-4 font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                        <span>🏥</span>
                                        @{{ st.carrier_name }}
                                    </td>
                                    <td class="py-3 px-4 font-mono font-semibold text-blue-600 dark:text-blue-400">
                                        @{{ st.period_month }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300 truncate max-w-xs">
                                        @{{ st.file_name }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold">
                                        @{{ st.total_records }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            @{{ st.matched_records }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                                            :class="st.discrepancy_records > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'text-gray-400'"
                                        >
                                            @{{ st.discrepancy_records }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                                            :class="st.missed_records > 0 ? 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300 animate-pulse' : 'text-gray-400'"
                                        >
                                            @{{ st.missed_records }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-900 dark:text-white">
                                        $@{{ formatMoney(st.total_carrier_amount) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-red-600 dark:text-red-400">
                                        $@{{ formatMoney(st.total_missed_amount) }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                @click="inspectStatement(st.id)"
                                                class="text-xs px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950 dark:text-blue-300 font-semibold rounded"
                                            >
                                                🔍 Inspeccionar
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteStatement(st.id)"
                                                class="text-xs text-red-500 hover:text-red-700 font-medium px-1"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Inspection Drill-down Modal -->
                <div
                    v-if="showDetailModal && activeStatement"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                >
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 max-w-5xl w-full max-h-[90vh] flex flex-col overflow-hidden">
                        <!-- Modal Header -->
                        <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span>🔍</span>
                                    Auditoría Detallada: @{{ activeStatement.carrier_name }} (@{{ activeStatement.period_month }})
                                </h3>
                                <p class="text-xs text-gray-500">
                                    Archivo: @{{ activeStatement.file_name }} • @{{ activeStatement.total_records }} registros analizados • Coincidencia: <strong class="text-emerald-600">@{{ activeStatement.match_rate }}%</strong>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="exportDiscrepanciesCsv"
                                    class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1"
                                >
                                    <span>📤</span>
                                    Descargar Discrepancias (CSV)
                                </button>
                                <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold px-2">✕</button>
                            </div>
                        </div>

                        <!-- Filter Tabs -->
                        <div class="flex items-center gap-2 p-3 bg-gray-100/70 dark:bg-gray-800/40 border-b border-gray-200 dark:border-gray-800 text-xs overflow-x-auto">
                            <button
                                type="button"
                                @click="detailFilter = 'all'"
                                class="px-3 py-1.5 rounded-lg font-semibold transition-colors"
                                :class="detailFilter === 'all' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            >
                                Todos (@{{ activeStatement.items?.length || 0 }})
                            </button>
                            <button
                                type="button"
                                @click="detailFilter = 'matched_exact'"
                                class="px-3 py-1.5 rounded-lg font-semibold transition-colors"
                                :class="detailFilter === 'matched_exact' ? 'bg-emerald-600 text-white shadow-sm' : 'text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50'"
                            >
                                ✓ Conciliados Exactos (@{{ countStatus('matched_exact') }})
                            </button>
                            <button
                                type="button"
                                @click="detailFilter = 'missed_commission'"
                                class="px-3 py-1.5 rounded-lg font-semibold transition-colors"
                                :class="detailFilter === 'missed_commission' ? 'bg-red-600 text-white shadow-sm' : 'text-red-700 dark:text-red-400 hover:bg-red-50'"
                            >
                                🚨 Omitidas / No Pagadas (@{{ countStatus('missed_commission') }})
                            </button>
                            <button
                                type="button"
                                @click="detailFilter = 'matched_variance'"
                                class="px-3 py-1.5 rounded-lg font-semibold transition-colors"
                                :class="detailFilter === 'matched_variance' ? 'bg-amber-600 text-white shadow-sm' : 'text-amber-700 dark:text-amber-400 hover:bg-amber-50'"
                            >
                                ⚠️ Variaciones (@{{ countStatus('matched_variance') }})
                            </button>
                            <button
                                type="button"
                                @click="detailFilter = 'chargeback'"
                                class="px-3 py-1.5 rounded-lg font-semibold transition-colors"
                                :class="detailFilter === 'chargeback' ? 'bg-orange-600 text-white shadow-sm' : 'text-orange-700 dark:text-orange-400 hover:bg-orange-50'"
                            >
                                ❌ Chargebacks (@{{ countStatus('chargeback') }})
                            </button>
                            <button
                                type="button"
                                @click="detailFilter = 'unmatched_orphan'"
                                class="px-3 py-1.5 rounded-lg font-semibold transition-colors"
                                :class="detailFilter === 'unmatched_orphan' ? 'bg-purple-600 text-white shadow-sm' : 'text-purple-700 dark:text-purple-400 hover:bg-purple-50'"
                            >
                                ❓ Pólizas Huérfanas (@{{ countStatus('unmatched_orphan') }})
                            </button>
                        </div>

                        <!-- Modal Table Body -->
                        <div class="overflow-y-auto p-4 flex-1">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 uppercase font-semibold">
                                    <tr>
                                        <th class="py-2.5 px-3">Póliza / Identificador</th>
                                        <th class="py-2.5 px-3">Asegurado Titular</th>
                                        <th class="py-2.5 px-3 text-right">Pagado por Carrier</th>
                                        <th class="py-2.5 px-3 text-right">Esperado por CRM</th>
                                        <th class="py-2.5 px-3 text-right">Diferencia</th>
                                        <th class="py-2.5 px-3 text-center">Estado del Cruce</th>
                                        <th class="py-2.5 px-3">Observaciones / Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <tr
                                        v-for="item in filteredDetailItems"
                                        :key="item.id"
                                        class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40"
                                    >
                                        <td class="py-2.5 px-3 font-mono font-bold text-gray-900 dark:text-white">
                                            @{{ item.policy_number }}
                                        </td>
                                        <td class="py-2.5 px-3 font-medium text-gray-800 dark:text-gray-200">
                                            @{{ item.insured_name || 'N/A' }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-bold" :class="item.carrier_amount < 0 ? 'text-red-500' : 'text-gray-900 dark:text-white'">
                                            $@{{ formatMoney(item.carrier_amount) }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono text-gray-600 dark:text-gray-300">
                                            $@{{ formatMoney(item.expected_amount) }}
                                        </td>
                                        <td
                                            class="py-2.5 px-3 text-right font-mono font-bold"
                                            :class="item.difference < 0 ? 'text-red-600' : (item.difference > 0 ? 'text-emerald-600' : 'text-gray-400')"
                                        >
                                            @{{ item.difference > 0 ? '+' : '' }}$@{{ formatMoney(item.difference) }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span
                                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                                :class="item.status_badge_class"
                                            >
                                                @{{ item.status_label }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-gray-500 text-[11px]">
                                            @{{ item.notes || '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Upload Statement Modal -->
                <div
                    v-if="showUploadModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                >
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 max-w-lg w-full overflow-hidden">
                        <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>📥</span>
                                Cargar Estado de Cuenta de Comisiones
                            </h3>
                            <button @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                        </div>

                        <form @submit.prevent="submitStatement" class="p-5 space-y-4 text-xs">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Aseguradora *</label>
                                    <select
                                        v-model="uploadForm.carrier_name"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-medium"
                                        required
                                    >
                                        <option v-for="rate in rates" :key="rate.id" :value="rate.carrier_name">
                                            @{{ rate.carrier_name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Período (Año-Mes) *</label>
                                    <input
                                        type="month"
                                        v-model="uploadForm.period_month"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-medium"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- File Upload or Paste Option -->
                            <div class="space-y-2">
                                <label class="block font-medium text-gray-700 dark:text-gray-300">
                                    Archivo CSV de la Aseguradora
                                </label>
                                <input
                                    type="file"
                                    ref="fileInput"
                                    accept=".csv,.txt"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2"
                                >
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    O Pegar contenido CSV directamente:
                                </label>
                                <textarea
                                    v-model="uploadForm.csv_content"
                                    rows="4"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2 font-mono text-[11px]"
                                    placeholder="Policy_Number,Insured_Name,Commission_Paid&#10;FLB-10023,Juan Perez,28.00&#10;FLB-10024,Maria Diaz,56.00"
                                ></textarea>
                                <p class="text-[10px] text-gray-400 mt-1">
                                    * Columnas reconocidas automáticamente: Póliza, Nombre Asegurado, Monto Pagado.
                                </p>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-800">
                                <button
                                    type="button"
                                    @click="showUploadModal = false"
                                    class="secondary-button"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    class="primary-button"
                                    :disabled="isUploading"
                                >
                                    @{{ isUploading ? 'Procesando y Cruzando...' : 'Reconciliar Statement' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-commission-reconciliation', {
                template: '#v-commission-reconciliation-template',

                props: ['initialStatements', 'rates', 'initialKpis'],

                data() {
                    return {
                        statements: this.initialStatements || [],
                        kpis: this.initialKpis || {
                            total_paid: 0,
                            total_missed: 0,
                            total_statements: 0,
                            avg_match_rate: 0,
                        },
                        showUploadModal: false,
                        showDetailModal: false,
                        isUploading: false,
                        activeStatement: null,
                        detailFilter: 'all',
                        uploadForm: {
                            carrier_name: this.rates && this.rates.length ? this.rates[0].carrier_name : 'Florida Blue',
                            period_month: new Date().toISOString().substring(0, 7),
                            csv_content: '',
                        },
                    };
                },

                computed: {
                    filteredDetailItems() {
                        if (!this.activeStatement || !this.activeStatement.items) return [];
                        if (this.detailFilter === 'all') return this.activeStatement.items;
                        return this.activeStatement.items.filter(item => item.match_status === this.detailFilter);
                    },
                },

                methods: {
                    formatMoney(val) {
                        return Number(val || 0).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        });
                    },

                    countStatus(status) {
                        if (!this.activeStatement || !this.activeStatement.items) return 0;
                        return this.activeStatement.items.filter(i => i.match_status === status).length;
                    },

                    openUploadModal() {
                        this.uploadForm.carrier_name = this.rates && this.rates.length ? this.rates[0].carrier_name : 'Florida Blue';
                        this.uploadForm.period_month = new Date().toISOString().substring(0, 7);
                        this.uploadForm.csv_content = '';
                        this.showUploadModal = true;
                    },

                    submitStatement() {
                        this.isUploading = true;

                        const formData = new FormData();
                        formData.append('carrier_name', this.uploadForm.carrier_name);
                        formData.append('period_month', this.uploadForm.period_month);

                        if (this.$refs.fileInput && this.$refs.fileInput.files[0]) {
                            formData.append('statement_file', this.$refs.fileInput.files[0]);
                        } else if (this.uploadForm.csv_content) {
                            formData.append('csv_content', this.uploadForm.csv_content);
                        } else {
                            alert('Por favor adjunte un archivo CSV o pegue el contenido.');
                            this.isUploading = false;
                            return;
                        }

                        this.$axios.post("{{ route('admin.commissions.reconciliation.upload') }}", formData, {
                            headers: { 'Content-Type': 'multipart/form-data' },
                        })
                        .then(response => {
                            this.isUploading = false;
                            this.showUploadModal = false;

                            this.statements.unshift(response.data.statement);
                            this.recalculateKPIs();

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message,
                            });

                            // Open details immediately
                            this.inspectStatement(response.data.statement.id);
                        })
                        .catch(error => {
                            this.isUploading = false;
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || 'Error al procesar el archivo CSV.',
                            });
                        });
                    },

                    inspectStatement(id) {
                        const url = "{{ route('admin.commissions.reconciliation.show', ['id' => 'xxx']) }}".replace('xxx', id);

                        this.$axios.get(url)
                            .then(response => {
                                this.activeStatement = response.data.statement;
                                this.detailFilter = 'all';
                                this.showDetailModal = true;
                            });
                    },

                    deleteStatement(id) {
                        if (!confirm('¿Desea eliminar este estado de cuenta de comisiones?')) return;

                        const url = "{{ route('admin.commissions.reconciliation.destroy', ['id' => 'xxx']) }}".replace('xxx', id);

                        this.$axios.delete(url)
                            .then(response => {
                                this.statements = this.statements.filter(s => s.id !== id);
                                this.recalculateKPIs();

                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                            });
                    },

                    exportDiscrepanciesCsv() {
                        if (!this.activeStatement || !this.activeStatement.items) return;

                        const discrepancies = this.activeStatement.items.filter(i => i.match_status !== 'matched_exact');
                        if (discrepancies.length === 0) {
                            alert('¡Excelente! No hay discrepancias en este statement para exportar.');
                            return;
                        }

                        let csv = "Poliza,Asegurado,Monto_Carrier,Monto_Esperado,Diferencia,Estado,Notas\n";
                        discrepancies.forEach(d => {
                            csv += `"${d.policy_number}","${d.insured_name || ''}","${d.carrier_amount}","${d.expected_amount}","${d.difference}","${d.status_label}","${d.notes || ''}"\n`;
                        });

                        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.setAttribute('download', `Discrepancias_${this.activeStatement.carrier_name}_${this.activeStatement.period_month}.csv`);
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    },

                    recalculateKPIs() {
                        this.kpis = {
                            total_paid: this.statements.reduce((s, st) => s + Number(st.total_carrier_amount || 0), 0),
                            total_missed: this.statements.reduce((s, st) => s + Number(st.total_missed_amount || 0), 0),
                            total_statements: this.statements.length,
                            avg_match_rate: this.statements.length
                                ? round(this.statements.reduce((s, st) => s + Number(st.match_rate || 0), 0) / this.statements.length, 1)
                                : 0,
                        };
                    },
                },
            });
        </script>
    @endpushOnce
</x-admin::layouts>

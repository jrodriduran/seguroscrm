<x-admin::layouts>
    <x-slot:title>
        @lang('admin::insurance.revenue_shield.title')
    </x-slot>

    <v-revenue-shield-manager
        :initial-summary="{{ json_encode($summary) }}"
        scan-endpoint="{{ route('admin.insurance.revenue_shield.scan') }}"
    >
    </v-revenue-shield-manager>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-revenue-shield-manager-template">
            <div class="flex flex-col gap-4 p-4">
                <!-- Header -->
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>🛡️</span> @lang('admin::insurance.revenue_shield.title')
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            @lang('admin::insurance.revenue_shield.subtitle')
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="triggerScan"
                            :disabled="isScanning"
                            class="primary-button text-xs py-2 px-3.5 bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1.5 shadow-sm"
                        >
                            <span v-if="isScanning" class="animate-spin text-xs">⏳</span>
                            <span v-else>⚡</span>
                            @lang('admin::insurance.revenue_shield.btn_run_audit')
                        </button>
                    </div>
                </div>

                <!-- KPI Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/30 dark:bg-rose-950/20 p-4 shadow-sm">
                        <div class="text-[11px] font-semibold uppercase text-rose-700">@lang('admin::insurance.revenue_shield.kpi_uncollected_revenue')</div>
                        <div class="text-2xl font-black text-rose-700 mt-1 flex items-center justify-between">
                            <span>$@{{ Number(summary.metrics.total_uncollected_revenue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                            <span class="text-xl">💰</span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                        <div class="text-[11px] font-semibold uppercase text-gray-500">@lang('admin::insurance.revenue_shield.kpi_missing_policies')</div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white mt-1 flex items-center justify-between">
                            <span>@{{ summary.metrics.total_missing_policies }}</span>
                            <span class="text-xl">📋</span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                        <div class="text-[11px] font-semibold uppercase text-amber-600">@lang('admin::insurance.revenue_shield.kpi_carriers_affected')</div>
                        <div class="text-2xl font-black text-amber-600 mt-1 flex items-center justify-between">
                            <span>@{{ summary.metrics.carriers_affected_count }}</span>
                            <span class="text-xl">🏢</span>
                        </div>
                    </div>
                </div>

                <!-- Carrier Breakdown Badges -->
                <div v-if="Object.keys(summary.carrier_breakdown || {}).length > 0" class="flex flex-wrap gap-2 items-center p-3 rounded-lg border bg-white dark:bg-gray-900 dark:border-gray-800 text-xs">
                    <span class="font-bold text-gray-600 dark:text-gray-300">Desglose por Aseguradora:</span>
                    <span
                        v-for="(info, carrier) in summary.carrier_breakdown"
                        :key="carrier"
                        class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-medium text-[11px] flex items-center gap-1 border border-gray-200 dark:border-gray-700"
                    >
                        <strong>@{{ carrier }}:</strong> @{{ info.count }} pólizas ($@{{ Number(info.amount).toFixed(2) }})
                    </span>
                </div>

                <!-- Flagged Policies Table -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>🚨</span> Pólizas con Comisiones No Conciliadas (@{{ summary.policies.length }})
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 uppercase text-[10px]">
                                <tr>
                                    <th class="px-4 py-2.5">Asegurado</th>
                                    <th class="px-4 py-2.5">Carrier / Póliza</th>
                                    <th class="px-4 py-2.5">Fecha Efecto</th>
                                    <th class="px-4 py-2.5">Días sin Pago</th>
                                    <th class="px-4 py-2.5">Monto Reclamable</th>
                                    <th class="px-4 py-2.5 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-for="policy in summary.policies" :key="policy.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                    <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                        @{{ policy.client_name }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="font-semibold">@{{ policy.carrier_name }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">#@{{ policy.policy_number }}</div>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500">
                                        @{{ policy.effective_date || 'N/D' }}
                                    </td>
                                    <td class="px-4 py-2.5 font-bold text-rose-600">
                                        @{{ policy.missing_days }} días
                                    </td>
                                    <td class="px-4 py-2.5 font-black text-rose-700">
                                        $@{{ Number(policy.missing_amount).toFixed(2) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right space-x-1.5">
                                        <button
                                            type="button"
                                            @click="openResolveModal(policy)"
                                            class="primary-button text-xs py-1 px-2.5 bg-indigo-600 hover:bg-indigo-700 text-white"
                                        >
                                            Gestionar Reclamo
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="summary.policies.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                        🎉 ¡Excelente! No hay comisiones pendientes de cobro no reportadas en la cartera activa.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Resolve Claim Modal -->
                <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-gray-900 rounded-xl max-w-md w-full p-5 shadow-2xl border border-gray-200 dark:border-gray-800 space-y-4">
                        <div class="flex items-center justify-between border-b pb-3 dark:border-gray-800">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                Resolver Reclamo: @{{ selectedPolicy?.carrier_name }} (#@{{ selectedPolicy?.policy_number }})
                            </h3>
                            <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                        </div>

                        <form @submit.prevent="submitResolution" class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Ticket o Caso ante Aseguradora (Opcional)
                                </label>
                                <input
                                    type="text"
                                    v-model="resolveForm.claim_ticket"
                                    placeholder="Ej: CAS-FLB-883921"
                                    class="w-full text-xs rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Nota de Resolución o Acuerdo *
                                </label>
                                <textarea
                                    v-model="resolveForm.resolution_note"
                                    required
                                    rows="3"
                                    placeholder="Describa el resultado de la gestión ante el carrier..."
                                    class="w-full text-xs rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                ></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t dark:border-gray-800">
                                <button type="button" @click="showModal = false" class="secondary-button text-xs py-1.5 px-3">
                                    Cancelar
                                </button>
                                <button type="submit" :disabled="isSubmitting" class="primary-button text-xs py-1.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white">
                                    <span v-if="isSubmitting" class="animate-spin text-xs">⏳</span>
                                    Guardar y Desmarcar Alerta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-revenue-shield-manager', {
                template: '#v-revenue-shield-manager-template',
                props: ['initialSummary', 'scanEndpoint'],
                data() {
                    return {
                        summary: this.initialSummary,
                        isScanning: false,
                        showModal: false,
                        selectedPolicy: null,
                        isSubmitting: false,
                        resolveForm: {
                            resolution_note: '',
                            claim_ticket: '',
                        },
                    };
                },
                methods: {
                    triggerScan() {
                        this.isScanning = true;
                        this.$axios.post(this.scanEndpoint)
                            .then(response => {
                                alert(response.data.message || 'Auditoría completada.');
                                window.location.reload();
                            })
                            .catch(err => {
                                alert('Error al ejecutar la auditoría de Revenue Shield.');
                            })
                            .finally(() => {
                                this.isScanning = false;
                            });
                    },
                    openResolveModal(policy) {
                        this.selectedPolicy = policy;
                        this.resolveForm.resolution_note = '';
                        this.resolveForm.claim_ticket = '';
                        this.showModal = true;
                    },
                    submitResolution() {
                        if (!this.resolveForm.resolution_note.trim()) return;

                        this.isSubmitting = true;
                        this.$axios.post(`/admin/insurance/revenue-shield/${this.selectedPolicy.id}/resolve`, this.resolveForm)
                            .then(response => {
                                alert(response.data.message || 'Alerta resuelta con éxito.');
                                this.showModal = false;
                                window.location.reload();
                            })
                            .catch(err => {
                                alert('Error al resolver la alerta de comisión.');
                            })
                            .finally(() => {
                                this.isSubmitting = false;
                            });
                    }
                }
            });
        </script>
    @endpushOnce
</x-admin::layouts>

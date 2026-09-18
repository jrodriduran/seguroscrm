<v-lead-client-snapshot :lead-id="{{ $lead->id }}"></v-lead-client-snapshot>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-client-snapshot-template">
        <div>
            <!-- Trigger Button in Header Area -->
            <button
                type="button"
                class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5 bg-gradient-to-r from-purple-50 to-indigo-50 text-purple-700 hover:from-purple-100 hover:to-indigo-100 border border-purple-200 dark:from-purple-950/40 dark:to-indigo-950/40 dark:text-purple-300 dark:border-purple-800 shadow-sm font-semibold"
                @click="openModal"
                title="Resumen ejecutivo instantáneo generado con IA"
            >
                <span class="animate-pulse">✨</span>
                AI Snapshot 360°
            </button>

            <!-- Modal Backdrop -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-800 space-y-4">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl">✨</span>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    Resumen Ejecutivo 360° (AI Client Snapshot)
                                    <span v-if="snapshot" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                                        Score: @{{ snapshot.ai_score }}/100
                                    </span>
                                </h3>
                                <p class="text-xs text-gray-500">Briefing instantáneo para llamadas y orientación del asegurado</p>
                            </div>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl font-bold" @click="showModal = false">&times;</button>
                    </div>

                    <!-- Loading -->
                    <div v-if="isLoading" class="py-12 flex flex-col items-center justify-center gap-3 text-xs text-gray-400">
                        <div class="w-8 h-8 border-3 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
                        <span>Generando síntesis de cliente...</span>
                    </div>

                    <!-- Content -->
                    <div v-else-if="snapshot" class="space-y-4 text-xs">
                        <!-- AI Narrative Card -->
                        <div class="p-4 rounded-xl bg-gradient-to-br from-purple-50/70 to-indigo-50/50 dark:from-purple-950/30 dark:to-indigo-950/20 border border-purple-200 dark:border-purple-800/60 leading-relaxed text-gray-800 dark:text-gray-200">
                            <div class="font-bold text-purple-900 dark:text-purple-300 text-[11px] mb-1 uppercase tracking-wider flex items-center gap-1.5">
                                <span>🤖</span> Diagnóstico Ejecutivo
                            </div>
                            @{{ snapshot.executive_narrative }}
                        </div>

                        <!-- 2-Column Key Metrics Grid -->
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                                <div class="text-[10px] text-gray-500 uppercase font-semibold">Beneficiario & Censo</div>
                                <div class="font-bold text-gray-900 dark:text-white mt-1">@{{ snapshot.client_name }}</div>
                                <div class="text-[11px] text-gray-500">@{{ snapshot.age_text }} • @{{ snapshot.household_size }} persona(s)</div>
                            </div>

                            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                                <div class="text-[10px] text-gray-500 uppercase font-semibold">Médico Primario (PCP)</div>
                                <div class="font-bold text-gray-900 dark:text-white mt-1 truncate">@{{ snapshot.primary_doctor }}</div>
                                <div class="text-[11px] text-emerald-600">Asignación médica verificada</div>
                            </div>

                            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                                <div class="text-[10px] text-gray-500 uppercase font-semibold">Medicamentos Registrados</div>
                                <div class="font-medium text-gray-800 dark:text-gray-200 mt-1 line-clamp-2">@{{ snapshot.medications_summary }}</div>
                            </div>

                            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                                <div class="text-[10px] text-gray-500 uppercase font-semibold">Estatus de Cumplimiento</div>
                                <div class="font-bold text-gray-900 dark:text-white mt-1">@{{ snapshot.compliance_status }}</div>
                            </div>
                        </div>

                        <!-- Next Action Callout -->
                        <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] uppercase font-bold text-blue-800 dark:text-blue-300">Próxima Mejor Acción:</div>
                                <div class="font-bold text-gray-900 dark:text-white">@{{ snapshot.next_action }}</div>
                            </div>
                            <span class="text-xl">🎯</span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end pt-2 border-t border-gray-200 dark:border-gray-800">
                        <button type="button" class="primary-button text-xs py-1.5 px-4" @click="showModal = false">Cerrar Briefing</button>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-lead-client-snapshot', {
            template: '#v-lead-client-snapshot-template',
            props: ['leadId'],
            data() {
                return {
                    showModal: false,
                    isLoading: false,
                    snapshot: null,
                };
            },
            methods: {
                openModal() {
                    this.showModal = true;
                    if (!this.snapshot) {
                        this.fetchSnapshot();
                    }
                },
                fetchSnapshot() {
                    this.isLoading = true;
                    this.$axios.get(`/admin/insurance/leads/${this.leadId}/client-snapshot`)
                        .then(res => {
                            if (res.data.success) {
                                this.snapshot = res.data.snapshot;
                            }
                        })
                        .catch(err => console.error('Error fetching snapshot:', err))
                        .finally(() => this.isLoading = false);
                }
            }
        });
    </script>
@endpushOnce

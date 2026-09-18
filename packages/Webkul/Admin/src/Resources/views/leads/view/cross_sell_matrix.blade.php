<v-lead-cross-sell :lead-id="{{ $lead->id }}"></v-lead-cross-sell>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-cross-sell-template">
        <div class="flex flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800 gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">🛍️</span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            Motor de Venta Cruzada (Cross-Selling & Gap Protection)
                            <span v-if="metrics" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                +$@{{ metrics.total_bundle_monthly }}/mes • Comisión: +$@{{ metrics.total_commission_kicker }}
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500">
                            Cierre de brechas de cobertura (Deducibles, Hospitalización, Dental y Visión) para maximizar la protección del cliente y los ingresos del agente.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                        @click="fetchOpportunities"
                        :disabled="isLoading"
                    >
                        <span class="icon-refresh text-sm" :class="{'animate-spin': isLoading}"></span>
                        Recalcular Oportunidades
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="space-y-3 py-4">
                <div class="h-20 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg"></div>
                <div class="h-20 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg"></div>
            </div>

            <!-- Bundles List -->
            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    v-for="opp in opportunities"
                    :key="opp.id"
                    class="p-4 rounded-xl border transition-all flex flex-col justify-between"
                    :class="opp.status === 'enrolled' ? 'border-emerald-300 bg-emerald-50/30 dark:border-emerald-800 dark:bg-emerald-950/20' : (opp.status === 'declined' ? 'border-gray-200 bg-gray-50/50 dark:border-gray-800 opacity-60' : 'border-purple-200 bg-white dark:border-purple-900 dark:bg-gray-900 shadow-sm')"
                >
                    <div class="space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-2xl">
                                @{{ opp.product_type === 'hospital_indemnity' ? '🏥' : (opp.product_type === 'dental_vision' ? '🦷' : '🛡️') }}
                            </span>
                            <span
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"
                                :class="opp.status === 'enrolled' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300' : (opp.status === 'presented' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : (opp.status === 'declined' ? 'bg-gray-200 text-gray-700' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'))"
                            >
                                @{{ opp.status }}
                            </span>
                        </div>

                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">@{{ opp.title }}</h4>
                            <div class="text-xs text-purple-600 dark:text-purple-400 font-medium">@{{ opp.carrier_name || 'Carrier Recomendado' }}</div>
                        </div>

                        <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800/60 text-xs text-gray-600 dark:text-gray-300 leading-relaxed border border-gray-100 dark:border-gray-800">
                            <strong>Brecha Detectada:</strong> @{{ opp.gap_reason }}
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1 text-xs">
                            <div class="p-2 rounded bg-purple-50/60 dark:bg-purple-950/40 text-center">
                                <div class="text-[10px] text-gray-500">Prima Estimada</div>
                                <div class="font-bold text-gray-900 dark:text-white mt-0.5">$@{{ Number(opp.estimated_monthly_premium).toFixed(2) }}/mes</div>
                            </div>
                            <div class="p-2 rounded bg-emerald-50/60 dark:bg-emerald-950/40 text-center">
                                <div class="text-[10px] text-gray-500">Comisión Agente</div>
                                <div class="font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">+$@{{ Number(opp.estimated_agent_commission).toFixed(2) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-1">
                        <button
                            type="button"
                            class="text-[11px] font-semibold px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300"
                            @click="updateStatus(opp.id, 'presented')"
                            v-if="opp.status !== 'presented' && opp.status !== 'enrolled'"
                        >
                            Presentado
                        </button>
                        <button
                            type="button"
                            class="text-[11px] font-bold px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1 shadow-sm"
                            @click="updateStatus(opp.id, 'enrolled')"
                            v-if="opp.status !== 'enrolled'"
                        >
                            <span>✓</span> Emitir
                        </button>
                        <button
                            type="button"
                            class="text-[11px] font-semibold px-2 py-1 rounded text-red-500 hover:text-red-700"
                            @click="updateStatus(opp.id, 'declined')"
                            v-if="opp.status !== 'declined'"
                        >
                            Declinado
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-lead-cross-sell', {
            template: '#v-lead-cross-sell-template',
            props: ['leadId'],
            data() {
                return {
                    isLoading: false,
                    opportunities: [],
                    metrics: null,
                };
            },
            mounted() {
                this.fetchOpportunities();
            },
            methods: {
                fetchOpportunities() {
                    this.isLoading = true;
                    this.$axios.get(`/admin/insurance/leads/${this.leadId}/cross-sell`)
                        .then(res => {
                            if (res.data.success) {
                                this.opportunities = res.data.data.opportunities || [];
                                this.metrics = res.data.data.metrics;
                            }
                        })
                        .catch(err => console.error('Error fetching cross sell:', err))
                        .finally(() => this.isLoading = false);
                },
                updateStatus(oppId, newStatus) {
                    this.$axios.put(`/admin/insurance/leads/${this.leadId}/cross-sell/${oppId}`, { status: newStatus })
                        .then(res => {
                            this.fetchOpportunities();
                        })
                        .catch(err => alert('Error al actualizar venta cruzada.'));
                }
            }
        });
    </script>
@endpushOnce

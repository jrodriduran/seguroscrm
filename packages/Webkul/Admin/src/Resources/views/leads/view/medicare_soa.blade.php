<v-lead-medicare-soa :lead-id="{{ $lead->id }}"></v-lead-medicare-soa>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-medicare-soa-template">
        <div class="flex flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">📋</span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            Scope of Appointment (SOA) Medicare
                            <span v-if="soa" :class="statusBadgeClass" class="text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                @{{ statusLabel }}
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500">Regulación obligatoria CMS 42 CFR § 422.2274 con regla de espera de 48 horas</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                        @click="fetchSoa"
                        :disabled="isLoading"
                    >
                        <span class="icon-refresh text-sm" :class="{'animate-spin': isLoading}"></span>
                        Actualizar
                    </button>

                    <a
                        v-if="soa && soa.status === 'signed'"
                        :href="'{{ route('admin.leads.soa.certificate', $lead->id) }}'"
                        target="_blank"
                        class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                    >
                        <span>🖨️</span>
                        Ver / Imprimir
                    </a>

                    <a
                        v-if="soa && soa.status === 'signed'"
                        :href="'{{ route('admin.leads.soa.certificate.pdf', $lead->id) }}'"
                        target="_blank"
                        class="primary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                    >
                        <span>📥</span>
                        Descargar PDF
                    </a>
                </div>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="isLoading && !soa" class="flex flex-col gap-3 py-6">
                <div class="h-12 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg"></div>
                <div class="h-32 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg"></div>
            </div>

            <div v-else-if="soa" class="space-y-4">

                <!-- 48-Hour CMS Rule Tracker Banner -->
                <div v-if="soa.status === 'signed'" class="rounded-xl border p-4 shadow-sm" :class="isEligible ? 'bg-emerald-50/80 dark:bg-emerald-950/20 border-emerald-300 dark:border-emerald-800' : 'bg-amber-50/80 dark:bg-amber-950/20 border-amber-300 dark:border-amber-800'">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">@{{ isEligible ? '✅' : '⏳' }}</span>
                            <div>
                                <h4 class="text-sm font-bold" :class="isEligible ? 'text-emerald-900 dark:text-emerald-300' : 'text-amber-900 dark:text-amber-300'">
                                    @{{ isEligible ? 'Regla CMS de 48 Horas Cumplida' : 'Esperando Cumplimiento del Período de 48 Horas CMS' }}
                                </h4>
                                <p class="text-xs" :class="isEligible ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'">
                                    <span v-if="soa.exception_reason && soa.exception_reason !== 'none'">
                                        Excepción CMS aplicada: <strong>@{{ soa.exception_reason === 'walk_in' ? 'Visita espontánea (Walk-in)' : 'Fin de período de enrolamiento' }}</strong>.
                                    </span>
                                    <span v-else-if="isEligible">
                                        El agente está legalmente autorizado por CMS para realizar la presentación personal desde el <strong>@{{ formatDateTime(soa.appointment_eligible_at) }}</strong>.
                                    </span>
                                    <span v-else>
                                        Tiempo restante para poder reunirse legalmente con el beneficiario: <strong class="text-amber-900 dark:text-amber-200">@{{ soa.hours_remaining_until_eligible }} hora(s)</strong> (Habilitada desde el @{{ formatDateTime(soa.appointment_eligible_at) }}).
                                    </span>
                                </p>
                            </div>
                        </div>

                        <button
                            v-if="!isEligible && soa.exception_reason === 'none'"
                            type="button"
                            @click="showExceptionModal = true"
                            class="text-xs font-semibold px-2.5 py-1.5 bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 hover:bg-amber-200 rounded-lg transition"
                        >
                            ⚡ Aplicar Excepción CMS
                        </button>
                    </div>
                </div>

                <!-- Signed Details Card -->
                <div v-if="soa.status === 'signed'" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 space-y-4 shadow-sm">
                    <!-- Audit Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-gray-400 block font-medium">Beneficiario:</span>
                            <strong class="text-gray-900 dark:text-white text-sm">@{{ soa.beneficiary_name }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">Fecha y Hora de Firma:</span>
                            <strong class="text-gray-800 dark:text-gray-200">@{{ formatDateTime(soa.signed_at) }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">IP Auditada:</span>
                            <strong class="text-gray-800 dark:text-gray-200 font-mono">@{{ soa.ip_address || 'Registrada' }}</strong>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-gray-400 block font-medium">Agente Certificado:</span>
                            <span class="text-gray-700 dark:text-gray-300">@{{ soa.agent_name }} • NPN: <strong class="text-blue-600">@{{ soa.agent_npn }}</strong></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">Dispositivo:</span>
                            <span class="text-gray-600 dark:text-gray-400 truncate block">@{{ soa.user_agent || 'Web/Móvil' }}</span>
                        </div>
                    </div>

                    <!-- Products Authorized List -->
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-3 text-xs">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 block mb-2">Planes Autorizados por el Beneficiario:</span>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="product in soa.products_list"
                                :key="product"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-900"
                            >
                                ☑ @{{ product }}
                            </span>
                        </div>
                    </div>

                    <!-- Signature Preview -->
                    <div v-if="soa.signature_data" class="border-t border-gray-100 dark:border-gray-800 pt-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 block mb-2">Firma Digital Capturada:</span>
                        <div class="flex items-center justify-center p-2 bg-gray-50 dark:bg-gray-950 rounded border border-gray-200 dark:border-gray-800">
                            <img :src="soa.signature_data" alt="Firma SOA" class="h-16 object-contain max-w-full">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="button"
                            @click="regenerateLink"
                            class="text-xs font-medium text-gray-500 hover:text-gray-700 underline"
                        >
                            Solicitar nueva firma SOA
                        </button>
                    </div>
                </div>

                <!-- Pending State Card -->
                <div v-else class="space-y-4">
                    <div class="bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-xl p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">⏳</span>
                                <div>
                                    <h4 class="text-sm font-bold text-amber-900 dark:text-amber-300">Scope of Appointment Pendiente de Firma</h4>
                                    <p class="text-xs text-amber-700 dark:text-amber-400">Envíe el enlace directo al beneficiario por WhatsApp o SMS para que firme en pantalla desde su celular.</p>
                                </div>
                            </div>

                            <a
                                :href="publicUrl"
                                target="_blank"
                                class="secondary-button text-xs py-1 px-2.5 flex items-center gap-1"
                            >
                                <span>🔗</span>
                                Probar Enlace
                            </a>
                        </div>

                        <!-- Action Bar: WhatsApp & Copy Link -->
                        <div class="flex flex-wrap gap-2.5 pt-2">
                            <button
                                type="button"
                                @click="openWhatsApp"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors"
                            >
                                <span class="text-sm">📲</span>
                                Enviar SOA por WhatsApp en 1 Clic
                            </button>

                            <button
                                type="button"
                                @click="copyLink"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 font-semibold text-xs rounded-lg shadow-sm transition-colors"
                            >
                                <span>📋</span>
                                @{{ copyBtnText }}
                            </button>
                        </div>
                    </div>

                    <!-- URL Box -->
                    <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg border border-gray-200 dark:border-gray-800 text-xs">
                        <span class="text-gray-400 block font-medium mb-1">Enlace público de firma rápida:</span>
                        <input
                            type="text"
                            readonly
                            :value="publicUrl"
                            class="w-full bg-white dark:bg-gray-950 font-mono text-gray-700 dark:text-gray-300 p-2 rounded border border-gray-200 dark:border-gray-800 select-all"
                            @click="$event.target.select()"
                        >
                    </div>
                </div>

                <!-- Exception Modal -->
                <div
                    v-if="showExceptionModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                >
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 max-w-md w-full overflow-hidden">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                <span>⚡</span> Aplicar Excepción Regulatoria CMS a Regla de 48h
                            </h3>
                            <button @click="showExceptionModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                        </div>

                        <div class="p-4 space-y-3 text-xs">
                            <p class="text-gray-500">CMS solo permite omitir las 48 horas bajo causas estrictamente tipificadas:</p>

                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo de Excepción *</label>
                                <select
                                    v-model="exceptionForm.reason"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2"
                                >
                                    <option value="walk_in">Beneficiario se presentó espontáneamente en la oficina (Walk-in)</option>
                                    <option value="end_of_enrollment">Final del período de inscripción (faltan menos de 4 días para AEP/OEP)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Justificación del Agente para Auditoría CMS *</label>
                                <textarea
                                    v-model="exceptionForm.notes"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2"
                                    placeholder="Detalle las circunstancias específicas de la visita o la fecha límite de inscripción..."
                                ></textarea>
                            </div>

                            <div class="flex justify-end gap-2 pt-2 border-t border-gray-200 dark:border-gray-800">
                                <button type="button" @click="showExceptionModal = false" class="secondary-button">Cancelar</button>
                                <button type="button" @click="applyException" class="primary-button" :disabled="isSubmittingException">
                                    @{{ isSubmittingException ? 'Registrando...' : 'Confirmar Excepción' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-lead-medicare-soa', {
            template: '#v-lead-medicare-soa-template',

            props: ['leadId'],

            data() {
                return {
                    soa: null,
                    publicUrl: '',
                    whatsappUrl: '',
                    whatsappMessage: '',
                    isLoading: true,
                    copyBtnText: 'Copiar Enlace',
                    showExceptionModal: false,
                    isSubmittingException: false,
                    exceptionForm: {
                        reason: 'walk_in',
                        notes: '',
                    },
                };
            },

            computed: {
                statusLabel() {
                    if (!this.soa) return 'Cargando...';
                    if (this.soa.status === 'signed') {
                        if (this.soa.exception_reason && this.soa.exception_reason !== 'none') {
                            return 'Excepción CMS Aplicada';
                        }
                        return this.soa.is_eligible_for_appointment ? 'Cumplido 48h (Listo para Cita)' : 'Firmado (Esperando 48h)';
                    }
                    return 'Pendiente de Firma';
                },

                statusBadgeClass() {
                    if (!this.soa) return 'bg-gray-100 text-gray-600';
                    if (this.soa.status === 'signed') {
                        return this.soa.is_eligible_for_appointment
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                            : 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300';
                    }
                    return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
                },

                isEligible() {
                    return this.soa && this.soa.is_eligible_for_appointment;
                },
            },

            mounted() {
                this.fetchSoa();
            },

            methods: {
                fetchSoa() {
                    this.isLoading = true;
                    const url = "{{ route('admin.leads.soa.get', ['lead_id' => 'xxx']) }}".replace('xxx', this.leadId);

                    this.$axios.get(url)
                        .then(response => {
                            this.isLoading = false;
                            this.soa = response.data.soa;
                            this.publicUrl = response.data.public_url;
                            this.whatsappMessage = response.data.whatsapp_message;
                        })
                        .catch(() => {
                            this.isLoading = false;
                        });
                },

                openWhatsApp() {
                    const url = "{{ route('admin.leads.soa.whatsapp', ['lead_id' => 'xxx']) }}".replace('xxx', this.leadId);

                    this.$axios.get(url)
                        .then(response => {
                            if (response.data.whatsapp_url) {
                                window.open(response.data.whatsapp_url, '_blank');
                            }
                        });
                },

                copyLink() {
                    if (!this.publicUrl) return;

                    navigator.clipboard.writeText(this.publicUrl).then(() => {
                        this.copyBtnText = '¡Copiado! ✓';
                        setTimeout(() => {
                            this.copyBtnText = 'Copiar Enlace';
                        }, 2500);
                    });
                },

                applyException() {
                    if (!this.exceptionForm.notes || this.exceptionForm.notes.length < 5) {
                        alert('Por favor ingrese la justificación de la excepción para el archivo de auditoría.');
                        return;
                    }

                    this.isSubmittingException = true;
                    const url = "{{ route('admin.leads.soa.exception', ['lead_id' => 'xxx']) }}".replace('xxx', this.leadId);

                    this.$axios.post(url, {
                        exception_reason: this.exceptionForm.reason,
                        notes: this.exceptionForm.notes,
                    })
                    .then(response => {
                        this.isSubmittingException = false;
                        this.showExceptionModal = false;
                        this.soa = response.data.soa;

                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message,
                        });
                    })
                    .catch(error => {
                        this.isSubmittingException = false;
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error.response?.data?.message || 'Error al aplicar excepción.',
                        });
                    });
                },

                regenerateLink() {
                    if (!confirm('¿Desea regenerar el enlace y solicitar una nueva firma SOA?')) return;

                    const url = "{{ route('admin.leads.soa.regenerate', ['lead_id' => 'xxx']) }}".replace('xxx', this.leadId);

                    this.$axios.post(url)
                        .then(response => {
                            this.soa = response.data.soa;
                            this.publicUrl = response.data.public_url;
                            this.whatsappMessage = response.data.whatsapp_message;

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message,
                            });
                        });
                },

                formatDateTime(dt) {
                    if (!dt) return 'N/A';
                    return new Date(dt).toLocaleString('es-ES', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                    });
                },
            },
        });
    </script>
@endpushOnce

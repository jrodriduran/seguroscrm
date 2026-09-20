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
                            @lang('admin::insurance.medicare_soa.title')
                            <span v-if="soa" :class="statusBadgeClass" class="text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                @{{ statusLabel }}
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500">@lang('admin::insurance.medicare_soa.subtitle')</p>
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
                        @lang('admin::insurance.medicare_soa.btn_refresh')
                    </button>

                    <a
                        v-if="soa && soa.status === 'signed'"
                        :href="'{{ route('admin.leads.soa.certificate', $lead->id) }}'"
                        target="_blank"
                        class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                    >
                        <span>🖨️</span>
                        @lang('admin::insurance.medicare_soa.btn_certificate')
                    </a>

                    <a
                        v-if="soa && soa.status === 'signed'"
                        :href="'{{ route('admin.leads.soa.certificate.pdf', $lead->id) }}'"
                        target="_blank"
                        class="primary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                    >
                        <span>📥</span>
                        @lang('admin::insurance.medicare_soa.btn_pdf')
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
                                    @{{ isEligible ? '{{ trans('admin::insurance.medicare_soa.rule_48h_met') }}' : '{{ trans('admin::insurance.medicare_soa.rule_48h_waiting') }}' }}
                                </h4>
                                <p class="text-xs" :class="isEligible ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'">
                                    <span v-if="soa.exception_reason && soa.exception_reason !== 'none'">
                                        @lang('admin::insurance.medicare_soa.rule_48h_exception'): <strong>@{{ soa.exception_reason === 'walk_in' ? '{{ trans('admin::insurance.medicare_soa.walk_in') }}' : '{{ trans('admin::insurance.medicare_soa.end_of_enrollment') }}' }}</strong>.
                                    </span>
                                    <span v-else-if="isEligible">
                                        @lang('admin::insurance.medicare_soa.authorized_from', ['date' => '']) <strong>@{{ formatDateTime(soa.appointment_eligible_at) }}</strong>.
                                    </span>
                                    <span v-else>
                                        @lang('admin::insurance.medicare_soa.waiting_time', ['hours' => '']) <strong class="text-amber-900 dark:text-amber-200">@{{ soa.hours_remaining_until_eligible }}</strong> (@{{ formatDateTime(soa.appointment_eligible_at) }}).
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
                            @lang('admin::insurance.medicare_soa.apply_exception')
                        </button>
                    </div>
                </div>

                <!-- Signed Details Card -->
                <div v-if="soa.status === 'signed'" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 space-y-4 shadow-sm">
                    <!-- Audit Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-gray-400 block font-medium">@lang('admin::insurance.medicare_soa.beneficiary')</span>
                            <strong class="text-gray-900 dark:text-white text-sm">@{{ soa.beneficiary_name }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">@lang('admin::insurance.medicare_soa.signed_at_label')</span>
                            <strong class="text-gray-800 dark:text-gray-200">@{{ formatDateTime(soa.signed_at) }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">@lang('admin::insurance.medicare_soa.audited_ip')</span>
                            <strong class="text-gray-800 dark:text-gray-200 font-mono">@{{ soa.ip_address || '{{ trans('admin::insurance.medicare_soa.registered') }}' }}</strong>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-gray-400 block font-medium">@lang('admin::insurance.medicare_soa.certified_agent')</span>
                            <span class="text-gray-700 dark:text-gray-300">@{{ soa.agent_name }} • NPN: <strong class="text-blue-600">@{{ soa.agent_npn }}</strong></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">@lang('admin::insurance.medicare_soa.device')</span>
                            <span class="text-gray-600 dark:text-gray-400 truncate block">@{{ soa.user_agent || '{{ trans('admin::insurance.medicare_soa.web_mobile') }}' }}</span>
                        </div>
                    </div>

                    <!-- Products Authorized List -->
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-3 text-xs">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 block mb-2">@lang('admin::insurance.medicare_soa.authorized_plans')</span>
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
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 block mb-2">@lang('admin::insurance.medicare_soa.digital_signature_captured')</span>
                        <div class="flex items-center justify-center p-2 bg-gray-50 dark:bg-gray-950 rounded border border-gray-200 dark:border-gray-800">
                            <img :src="soa.signature_data" alt="Signature" class="h-16 object-contain max-w-full">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="button"
                            @click="regenerateLink"
                            class="text-xs font-medium text-gray-500 hover:text-gray-700 underline"
                        >
                            @lang('admin::insurance.medicare_soa.request_new_soa')
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
                                    <h4 class="text-sm font-bold text-amber-900 dark:text-amber-300">@lang('admin::insurance.medicare_soa.pending_title')</h4>
                                    <p class="text-xs text-amber-700 dark:text-amber-400">@lang('admin::insurance.medicare_soa.pending_subtitle')</p>
                                </div>
                            </div>

                            <a
                                :href="publicUrl"
                                target="_blank"
                                class="secondary-button text-xs py-1 px-2.5 flex items-center gap-1"
                            >
                                <span>🔗</span>
                                @lang('admin::insurance.medicare_soa.test_link')
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
                                @lang('admin::insurance.medicare_soa.send_whatsapp_1click')
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
                        <span class="text-gray-400 block font-medium mb-1">@lang('admin::insurance.medicare_soa.public_quick_link')</span>
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
                                <span>⚡</span> @lang('admin::insurance.medicare_soa.modal_exception_title')
                            </h3>
                            <button @click="showExceptionModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                        </div>

                        <div class="p-4 space-y-3 text-xs">
                            <p class="text-gray-500">@lang('admin::insurance.medicare_soa.modal_exception_notice')</p>

                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.medicare_soa.modal_reason_label')</label>
                                <select
                                    v-model="exceptionForm.reason"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2"
                                >
                                    <option value="walk_in">@lang('admin::insurance.medicare_soa.walk_in')</option>
                                    <option value="end_of_enrollment">@lang('admin::insurance.medicare_soa.end_of_enrollment')</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.medicare_soa.modal_notes_label')</label>
                                <textarea
                                    v-model="exceptionForm.notes"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-2"
                                    placeholder="{{ trans('admin::insurance.medicare_soa.modal_notes_placeholder') }}"
                                ></textarea>
                            </div>

                            <div class="flex justify-end gap-2 pt-2 border-t border-gray-200 dark:border-gray-800">
                                <button type="button" @click="showExceptionModal = false" class="secondary-button">@lang('admin::insurance.medicare_soa.cancel')</button>
                                <button type="button" @click="applyException" class="primary-button" :disabled="isSubmittingException">
                                    @{{ isSubmittingException ? '{{ trans('admin::insurance.medicare_soa.registering') }}' : '{{ trans('admin::insurance.medicare_soa.confirm_exception') }}' }}
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
                    copyBtnText: '{{ trans('admin::insurance.medicare_soa.copy_link') }}',
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
                    if (!this.soa) return '{{ trans('admin::insurance.medicare_soa.loading') }}';
                    if (this.soa.status === 'signed') {
                        if (this.soa.exception_reason && this.soa.exception_reason !== 'none') {
                            return '{{ trans('admin::insurance.medicare_soa.status_exception_applied') }}';
                        }
                        return this.soa.is_eligible_for_appointment ? '{{ trans('admin::insurance.medicare_soa.status_ready_for_appointment') }}' : '{{ trans('admin::insurance.medicare_soa.status_waiting_48h') }}';
                    }
                    return '{{ trans('admin::insurance.medicare_soa.status_pending') }}';
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
                        this.copyBtnText = '{{ trans('admin::insurance.medicare_soa.copied') }}';
                        setTimeout(() => {
                            this.copyBtnText = '{{ trans('admin::insurance.medicare_soa.copy_link') }}';
                        }, 2500);
                    });
                },

                applyException() {
                    if (!this.exceptionForm.notes || this.exceptionForm.notes.length < 5) {
                        alert('{{ trans('admin::insurance.medicare_soa.prompt_missing_notes') }}');
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
                            message: error.response?.data?.message || 'Error.',
                        });
                    });
                },

                regenerateLink() {
                    if (!confirm('{{ trans('admin::insurance.medicare_soa.confirm_regenerate') }}')) return;

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
                    return new Date(dt).toLocaleString('{{ app()->getLocale() }}', {
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

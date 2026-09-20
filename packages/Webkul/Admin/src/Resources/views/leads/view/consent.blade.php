<v-lead-consent :lead-id="{{ $lead->id }}"></v-lead-consent>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-consent-template">
        <div class="flex flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">📋</span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            @lang('admin::insurance.consent.title')
                            <span v-if="consent" :class="statusBadgeClass" class="text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                @{{ statusLabel }}
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500">@lang('admin::insurance.consent.subtitle')</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                        @click="fetchConsent"
                        :disabled="isLoading"
                    >
                        <span class="icon-refresh text-sm" :class="{'animate-spin': isLoading}"></span>
                        @lang('admin::insurance.consent.btn_refresh')
                    </button>

                    <a
                        v-if="consent && consent.status === 'signed'"
                        :href="'{{ route('admin.leads.consent.certificate', $lead->id) }}'"
                        target="_blank"
                        class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                    >
                        <span>🖨️</span>
                        @lang('admin::insurance.consent.btn_print')
                    </a>

                    <a
                        v-if="consent && consent.status === 'signed'"
                        :href="'{{ route('admin.leads.consent.certificate.pdf', $lead->id) }}'"
                        target="_blank"
                        class="primary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                    >
                        <span>📥</span>
                        @lang('admin::insurance.consent.btn_pdf')
                    </a>
                </div>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="isLoading && !consent" class="flex flex-col gap-3 py-6">
                <div class="h-12 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg"></div>
                <div class="h-32 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg"></div>
            </div>

            <div v-else-if="consent" class="space-y-4">

                <!-- Signed State Card -->
                <div v-if="consent.status === 'signed'" class="bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-5 space-y-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl font-bold">
                                ✓
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-emerald-900 dark:text-emerald-300">@lang('admin::insurance.consent.signed_card_title')</h4>
                                <p class="text-xs text-emerald-700 dark:text-emerald-400">@lang('admin::insurance.consent.signed_card_sub')</p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="regenerateLink"
                            class="text-xs font-medium text-gray-500 hover:text-gray-700 underline"
                        >
                            @lang('admin::insurance.consent.request_resend')
                        </button>
                    </div>

                    <!-- Audit Trail Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs bg-white dark:bg-gray-900 p-4 rounded-lg border border-emerald-100 dark:border-emerald-900/40">
                        <div>
                            <span class="text-gray-400 block font-medium">@lang('admin::insurance.consent.signed_by')</span>
                            <strong class="text-gray-900 dark:text-white text-sm">@{{ consent.client_name }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">@lang('admin::insurance.consent.sign_date_time')</span>
                            <strong class="text-gray-800 dark:text-gray-200">@{{ formatDateTime(consent.signed_at) }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">@lang('admin::insurance.consent.ip_audited')</span>
                            <strong class="text-gray-800 dark:text-gray-200 font-mono">@{{ consent.ip_address || 'Registrada' }}</strong>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-gray-400 block font-medium">@lang('admin::insurance.consent.agent_npn')</span>
                            <span class="text-gray-700 dark:text-gray-300">@{{ consent.agent_name }} • NPN: <strong class="text-blue-600">@{{ consent.agent_npn }}</strong></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">@lang('admin::insurance.consent.browser_device')</span>
                            <span class="text-gray-600 dark:text-gray-400 truncate block">@{{ consent.user_agent || 'Web' }}</span>
                        </div>
                    </div>

                    <!-- Signature Preview -->
                    <div v-if="consent.signature_data" class="bg-white dark:bg-gray-900 p-3 rounded-lg border border-emerald-100 dark:border-emerald-900/40">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 block mb-2">@lang('admin::insurance.consent.signature_preview')</span>
                        <div class="flex items-center justify-center p-2 bg-gray-50 dark:bg-gray-950 rounded border border-gray-200 dark:border-gray-800">
                            <img :src="consent.signature_data" alt="Firma" class="h-20 object-contain max-w-full">
                        </div>
                    </div>
                </div>

                <!-- Pending State Card -->
                <div v-else class="space-y-4">
                    <div class="bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-xl p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl font-bold">
                                    ⏳
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-amber-900 dark:text-amber-300">@lang('admin::insurance.consent.pending_card_title')</h4>
                                    <p class="text-xs text-amber-700 dark:text-amber-400">@lang('admin::insurance.consent.pending_card_sub')</p>
                                </div>
                            </div>

                            <a
                                :href="publicUrl"
                                target="_blank"
                                class="secondary-button text-xs py-1 px-2.5 flex items-center gap-1"
                            >
                                <span>🔗</span>
                                @lang('admin::insurance.consent.test_link')
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
                                @lang('admin::insurance.consent.send_whatsapp_1click')
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
                        <span class="text-gray-400 block font-medium mb-1">@lang('admin::insurance.consent.public_quick_link')</span>
                        <input
                            type="text"
                            readonly
                            :value="publicUrl"
                            class="w-full bg-white dark:bg-gray-950 font-mono text-gray-700 dark:text-gray-300 p-2 rounded border border-gray-200 dark:border-gray-800 select-all"
                            @click="$event.target.select()"
                        >
                    </div>

                    <!-- WhatsApp Message Preview -->
                    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                            <span>💬</span> @lang('admin::insurance.consent.whatsapp_preview')
                        </span>
                        <div class="bg-emerald-50/40 dark:bg-gray-950 p-3 rounded-lg border border-emerald-100 dark:border-gray-800 text-xs text-gray-700 dark:text-gray-300 whitespace-pre-line font-normal leading-relaxed">
                            @{{ whatsappMessage }}
                        </div>
                    </div>
                </div>

                <!-- CMS Legal Disclosure Reference -->
                <div class="border-t border-gray-200 dark:border-gray-800 pt-3">
                    <details class="text-xs text-gray-500 cursor-pointer">
                        <summary class="font-medium hover:text-gray-700 dark:hover:text-gray-300">
                            @lang('admin::insurance.consent.cms_legal_reference')
                        </summary>
                        <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-800 whitespace-pre-line text-[11px] leading-relaxed">
                            @{{ consent.consent_text }}
                        </div>
                    </details>
                </div>

            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-lead-consent', {
            template: '#v-lead-consent-template',

            props: ['leadId'],

            data() {
                return {
                    isLoading: false,
                    consent: null,
                    publicUrl: '',
                    whatsappMessage: '',
                    copyBtnText: @json(trans('admin::insurance.consent.copy_link')),
                    labels: {
                        copyLink: @json(trans('admin::insurance.consent.copy_link')),
                        copied: @json(trans('admin::insurance.consent.copied')),
                        copyFlash: @json(trans('admin::insurance.consent.copy_link_flash')),
                        whatsappError: @json(trans('admin::insurance.consent.whatsapp_error')),
                        confirmRegenerate: @json(trans('admin::insurance.consent.confirm_regenerate')),
                        statusSigned: @json(trans('admin::insurance.consent.status_signed')),
                        statusPending: @json(trans('admin::insurance.consent.status_pending')),
                        locale: '{{ str_replace('_', '-', app()->getLocale()) }}',
                    }
                };
            },

            computed: {
                statusLabel() {
                    if (!this.consent) return this.labels.statusPending;
                    return this.consent.status === 'signed' ? ('🟢 ' + this.labels.statusSigned) : ('🟡 ' + this.labels.statusPending);
                },

                statusBadgeClass() {
                    if (!this.consent) return 'bg-gray-100 text-gray-700';
                    return this.consent.status === 'signed'
                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                        : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
                }
            },

            mounted() {
                this.fetchConsent();
            },

            methods: {
                fetchConsent() {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.leads.consent.get', $lead->id) }}")
                        .then(response => {
                            this.isLoading = false;
                            if (response.data.success) {
                                this.consent = response.data.consent;
                                this.publicUrl = response.data.public_url;
                                this.whatsappMessage = response.data.whatsapp_message;
                            }
                        })
                        .catch(err => {
                            this.isLoading = false;
                            console.error(err);
                        });
                },

                copyLink() {
                    if (!this.publicUrl) return;

                    navigator.clipboard.writeText(this.publicUrl).then(() => {
                        this.copyBtnText = this.labels.copied;
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: this.labels.copyFlash,
                        });
                        setTimeout(() => {
                            this.copyBtnText = this.labels.copyLink;
                        }, 2000);
                    });
                },

                openWhatsApp() {
                    this.$axios.get("{{ route('admin.leads.consent.whatsapp', $lead->id) }}")
                        .then(response => {
                            if (response.data.whatsapp_url) {
                                window.open(response.data.whatsapp_url, '_blank');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: this.labels.whatsappError,
                            });
                        });
                },

                regenerateLink() {
                    if (!confirm(this.labels.confirmRegenerate)) {
                        return;
                    }

                    this.isLoading = true;

                    this.$axios.post("{{ route('admin.leads.consent.regenerate', $lead->id) }}")
                        .then(response => {
                            this.isLoading = false;
                            if (response.data.success) {
                                this.consent = response.data.consent;
                                this.publicUrl = response.data.public_url;
                                this.whatsappMessage = response.data.whatsapp_message;
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                            }
                        })
                        .catch(err => {
                            this.isLoading = false;
                            console.error(err);
                        });
                },

                formatDateTime(dateStr) {
                    if (!dateStr) return 'N/A';
                    const d = new Date(dateStr);
                    return d.toLocaleString(this.labels.locale || 'es', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                    });
                }
            }
        });
    </script>
@endPushOnce

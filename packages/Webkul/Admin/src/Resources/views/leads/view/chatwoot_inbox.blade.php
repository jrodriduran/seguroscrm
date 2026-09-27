<v-lead-chatwoot-inbox :lead-id="{{ $lead->id }}"></v-lead-chatwoot-inbox>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-chatwoot-inbox-template">
        <div class="flex flex-col h-[520px] bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden relative">
            <!-- Header -->
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">💬</span>
                    <div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 flex-wrap">
                            @lang('admin::insurance.chatwoot.title')
                            <span v-if="configured" class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            <span v-if="configured" class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-200">
                                @lang('admin::insurance.chatwoot.active_badge')
                            </span>
                            <span v-else class="text-[10px] font-semibold text-amber-600 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded-full border border-amber-200">
                                @lang('admin::insurance.chatwoot.not_configured_badge')
                            </span>

                            <!-- TCPA Badge -->
                            <span v-if="hasTcpaConsent" class="text-[10px] font-semibold text-emerald-700 bg-emerald-100 dark:bg-emerald-950/70 px-2 py-0.5 rounded-full border border-emerald-300" :title="'Consentimiento: ' + (tcpaConsentType || 'Verificado')">
                                🛡️ @lang('admin::insurance.chatwoot.tcpa_verified_badge')
                            </span>
                            <span v-else class="text-[10px] font-semibold text-rose-700 bg-rose-100 dark:bg-rose-950/70 px-2 py-0.5 rounded-full border border-rose-300 animate-pulse">
                                🚫 @lang('admin::insurance.chatwoot.tcpa_missing_badge')
                            </span>
                        </div>
                        <div class="text-[11px] text-gray-500">
                            @lang('admin::insurance.chatwoot.subtitle')
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="secondary-button text-xs py-1 px-2.5 flex items-center gap-1"
                        @click="fetchMessages"
                        :disabled="isLoading"
                        title="@lang('admin::insurance.chatwoot.btn_refresh')"
                    >
                        <span class="icon-refresh text-xs" :class="{'animate-spin': isLoading}"></span>
                        @lang('admin::insurance.chatwoot.btn_refresh')
                    </button>

                    <a
                        v-if="conversationUrl"
                        :href="conversationUrl"
                        target="_blank"
                        class="primary-button text-xs py-1 px-2.5 flex items-center gap-1 text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        <span>↗️</span>
                        @lang('admin::insurance.chatwoot.btn_open_chatwoot')
                    </a>
                </div>
            </div>

            <!-- Not Configured Alert -->
            <div v-if="!configured && !isLoading" class="p-3 bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200 text-xs text-amber-800 dark:text-amber-200 flex items-start gap-2">
                <span class="text-base">⚠️</span>
                <div>
                    @lang('admin::insurance.chatwoot.not_configured_warning')
                </div>
            </div>

            <!-- TCPA Warning & Action Banner -->
            <div v-if="!hasTcpaConsent && !isLoading" class="p-3 bg-rose-50 dark:bg-rose-950/30 border-b border-rose-200 dark:border-rose-900 text-xs text-rose-800 dark:text-rose-200 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-base">🛑</span>
                    <div>
                        <span class="font-bold">@lang('admin::insurance.chatwoot.tcpa_missing_badge'):</span>
                        @lang('admin::insurance.chatwoot.tcpa_warning_text')
                    </div>
                </div>
                <button
                    type="button"
                    @click="showTcpaModal = true"
                    class="primary-button text-xs py-1.5 px-3 whitespace-nowrap bg-rose-600 hover:bg-rose-700 text-white font-medium flex items-center gap-1 shadow-sm shrink-0"
                >
                    <span>📝</span> @lang('admin::insurance.chatwoot.btn_record_tcpa')
                </button>
            </div>

            <!-- Chat Message Bubbles Area -->
            <div class="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50/50 dark:bg-gray-950/40" ref="messageList">
                <div v-if="isLoading" class="flex flex-col items-center justify-center h-full text-gray-400 text-xs gap-2">
                    <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                    <span>@lang('admin::insurance.chatwoot.loading')</span>
                </div>

                <div v-else-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center text-xs text-gray-400 p-6 space-y-2">
                    <span class="text-3xl">📭</span>
                    <div class="font-medium text-gray-600 dark:text-gray-300">@lang('admin::insurance.chatwoot.no_messages')</div>
                    <p class="max-w-xs text-[11px]">@lang('admin::insurance.chatwoot.empty_thread_sub')</p>
                </div>

                <!-- Messages -->
                <div
                    v-else
                    v-for="msg in messages"
                    :key="msg.id"
                    class="flex flex-col"
                    :class="isOutgoing(msg) ? 'items-end' : 'items-start'"
                >
                    <div
                        class="max-w-[75%] rounded-2xl px-4 py-2.5 shadow-sm text-xs"
                        :class="isOutgoing(msg) ? 'bg-indigo-600 text-white rounded-br-none' : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-bl-none'"
                    >
                        <div class="font-semibold text-[10px] mb-0.5 opacity-80" v-if="msg.sender">
                            @{{ msg.sender.name || (isOutgoing(msg) ? '@lang('admin::insurance.chatwoot.agent')' : '@lang('admin::insurance.chatwoot.client')') }}
                        </div>
                        <div class="whitespace-pre-wrap leading-relaxed">@{{ msg.content }}</div>
                        <div class="text-[9px] mt-1 text-right opacity-70">
                            @{{ formatTime(msg.created_at) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input Box -->
            <form @submit.prevent="sendMessage" class="p-3 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 flex items-center gap-2">
                <input
                    type="text"
                    v-model="newMessage"
                    :disabled="isSending || !hasTcpaConsent"
                    :placeholder="hasTcpaConsent ? '@lang('admin::insurance.chatwoot.placeholder_input')' : '⚠️ Debe registrar consentimiento TCPA previo para enviar mensajes...'"
                    class="flex-1 text-xs rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-60 disabled:bg-gray-100 dark:disabled:bg-gray-800"
                />
                <button
                    type="submit"
                    :disabled="isSending || !newMessage.trim() || !hasTcpaConsent"
                    class="primary-button text-xs py-2 px-4 flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
                >
                    <span v-if="isSending" class="animate-spin text-xs">⏳</span>
                    <span v-else>✈️</span>
                    @lang('admin::insurance.chatwoot.btn_send')
                </button>
            </form>

            <!-- Modal TCPA Consent Registration -->
            <div v-if="showTcpaModal" class="absolute inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl max-w-md w-full p-5 shadow-2xl border border-gray-200 dark:border-gray-800 space-y-4">
                    <div class="flex items-center justify-between border-b pb-3 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🛡️</span>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                @lang('admin::insurance.chatwoot.tcpa_modal_title')
                            </h3>
                        </div>
                        <button type="button" @click="showTcpaModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <form @submit.prevent="submitTcpaConsent" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                @lang('admin::insurance.chatwoot.tcpa_consent_type_label') *
                            </label>
                            <select
                                v-model="tcpaForm.consent_type"
                                required
                                class="w-full text-xs rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                            >
                                <option value="web_form_optin">@lang('admin::insurance.chatwoot.tcpa_type_web_form')</option>
                                <option value="inbound_call_verbal">@lang('admin::insurance.chatwoot.tcpa_type_inbound_call')</option>
                                <option value="signed_consent_doc">@lang('admin::insurance.chatwoot.tcpa_type_signed_doc')</option>
                                <option value="sms_optin_keyword">@lang('admin::insurance.chatwoot.tcpa_type_sms_keyword')</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                @lang('admin::insurance.chatwoot.tcpa_proof_label') *
                            </label>
                            <input
                                type="text"
                                v-model="tcpaForm.consent_proof"
                                required
                                placeholder="@lang('admin::insurance.chatwoot.tcpa_proof_placeholder')"
                                class="w-full text-xs rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                            />
                        </div>

                        <div class="p-2.5 rounded bg-amber-50 dark:bg-amber-950/40 border border-amber-200 text-[11px] text-amber-800 dark:text-amber-200">
                            ⚖️ Conforme a 47 U.S.C. § 227 y lineamientos FCC, este registro se almacena en la pista de auditoría inmutable del CRM.
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t dark:border-gray-800">
                            <button
                                type="button"
                                @click="showTcpaModal = false"
                                class="secondary-button text-xs py-1.5 px-3"
                            >
                                @lang('admin::insurance.chatwoot.tcpa_cancel')
                            </button>
                            <button
                                type="submit"
                                :disabled="isSavingTcpa"
                                class="primary-button text-xs py-1.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1"
                            >
                                <span v-if="isSavingTcpa" class="animate-spin text-xs">⏳</span>
                                @lang('admin::insurance.chatwoot.btn_save_tcpa')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-lead-chatwoot-inbox', {
            template: '#v-lead-chatwoot-inbox-template',
            props: ['leadId'],
            data() {
                return {
                    isLoading: false,
                    isSending: false,
                    configured: false,
                    conversationUrl: null,
                    messages: [],
                    newMessage: '',
                    hasTcpaConsent: false,
                    tcpaConsentedAt: null,
                    tcpaConsentType: null,
                    showTcpaModal: false,
                    isSavingTcpa: false,
                    tcpaForm: {
                        consent_type: 'web_form_optin',
                        consent_proof: '',
                    },
                };
            },
            mounted() {
                this.fetchMessages();
            },
            methods: {
                fetchMessages() {
                    this.isLoading = true;
                    this.$axios.get(`/admin/leads/${this.leadId}/chatwoot/conversation`)
                        .then(response => {
                            if (response.data.success) {
                                this.configured = response.data.configured;
                                this.conversationUrl = response.data.conversation_url;
                                this.messages = response.data.messages || [];
                                this.hasTcpaConsent = Boolean(response.data.has_tcpa_consent);
                                this.tcpaConsentedAt = response.data.tcpa_consented_at;
                                this.tcpaConsentType = response.data.tcpa_consent_type;
                                this.$nextTick(() => this.scrollToBottom());
                            } else {
                                this.configured = false;
                                this.hasTcpaConsent = Boolean(response.data.has_tcpa_consent);
                            }
                        })
                        .catch(err => {
                            this.configured = false;
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                },
                sendMessage() {
                    if (!this.newMessage.trim() || !this.hasTcpaConsent) return;

                    this.isSending = true;
                    const text = this.newMessage;
                    this.$axios.post(`/admin/leads/${this.leadId}/chatwoot/send`, { message: text })
                        .then(response => {
                            this.newMessage = '';
                            this.fetchMessages();
                        })
                        .catch(err => {
                            const msg = err.response?.data?.message || 'Error al enviar mensaje vía Chatwoot.';
                            alert(msg);
                            if (err.response?.data?.tcpa_violation) {
                                this.hasTcpaConsent = false;
                            }
                        })
                        .finally(() => {
                            this.isSending = false;
                        });
                },
                submitTcpaConsent() {
                    if (!this.tcpaForm.consent_proof.trim()) return;

                    this.isSavingTcpa = true;
                    this.$axios.post(`/admin/leads/${this.leadId}/chatwoot/tcpa-consent`, this.tcpaForm)
                        .then(response => {
                            if (response.data.success) {
                                this.hasTcpaConsent = true;
                                this.tcpaConsentType = this.tcpaForm.consent_type;
                                this.showTcpaModal = false;
                                alert(response.data.message || 'Consentimiento TCPA guardado exitosamente.');
                                this.fetchMessages();
                            }
                        })
                        .catch(err => {
                            alert(err.response?.data?.message || 'Error al registrar consentimiento TCPA.');
                        })
                        .finally(() => {
                            this.isSavingTcpa = false;
                        });
                },
                isOutgoing(msg) {
                    return msg.message_type === 'outgoing' || msg.message_type === 1;
                },
                formatTime(timestamp) {
                    if (!timestamp) return '';
                    try {
                        const d = new Date(typeof timestamp === 'number' ? timestamp * 1000 : timestamp);
                        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    } catch (e) {
                        return '';
                    }
                },
                scrollToBottom() {
                    const el = this.$refs.messageList;
                    if (el) {
                        el.scrollTop = el.scrollHeight;
                    }
                }
            }
        });
    </script>
@endpushOnce

<v-lead-chatwoot-inbox :lead-id="{{ $lead->id }}"></v-lead-chatwoot-inbox>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-chatwoot-inbox-template">
        <div class="flex flex-col h-[520px] bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">💬</span>
                    <div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            Inbox Omnicanal (Chatwoot)
                            <span v-if="configured" class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            <span v-if="configured" class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-200">
                                WhatsApp / SMS Activo
                            </span>
                            <span v-else class="text-[10px] font-semibold text-amber-600 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded-full border border-amber-200">
                                Sin Configurar
                            </span>
                        </div>
                        <div class="text-[11px] text-gray-500">
                            Conversación directa con el asegurado sincronizada en tiempo real
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="secondary-button text-xs py-1 px-2.5 flex items-center gap-1"
                        @click="fetchMessages"
                        :disabled="isLoading"
                        title="Actualizar mensajes"
                    >
                        <span class="icon-refresh text-xs" :class="{'animate-spin': isLoading}"></span>
                        Recargar
                    </button>

                    <a
                        v-if="conversationUrl"
                        :href="conversationUrl"
                        target="_blank"
                        class="primary-button text-xs py-1 px-2.5 flex items-center gap-1 text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        <span>↗️</span>
                        Abrir en Chatwoot
                    </a>
                </div>
            </div>

            <!-- Not Configured Alert -->
            <div v-if="!configured && !isLoading" class="p-4 bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200 text-xs text-amber-800 dark:text-amber-200 flex items-start gap-2">
                <span class="text-base">⚠️</span>
                <div>
                    <strong>Chatwoot no está vinculado:</strong> Configura <code>CHATWOOT_API_TOKEN</code> y <code>CHATWOOT_ACCOUNT_ID</code> en tu archivo de entorno para activar la mensajería unificada de WhatsApp y SMS.
                </div>
            </div>

            <!-- Chat Message Bubbles Area -->
            <div class="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50/50 dark:bg-gray-950/40" ref="messageList">
                <div v-if="isLoading" class="flex flex-col items-center justify-center h-full text-gray-400 text-xs gap-2">
                    <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                    <span>Cargando conversación...</span>
                </div>

                <div v-else-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center text-xs text-gray-400 p-6 space-y-2">
                    <span class="text-3xl">📭</span>
                    <div class="font-medium text-gray-600 dark:text-gray-300">No hay mensajes previos en este hilo.</div>
                    <p class="max-w-xs text-[11px]">Escribe un mensaje abajo para iniciar la conversación por WhatsApp / SMS con el cliente.</p>
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
                            @{{ msg.sender.name || (isOutgoing(msg) ? 'Agente' : 'Cliente') }}
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
                    :disabled="isSending"
                    placeholder="Escribe un mensaje de WhatsApp / SMS..."
                    class="flex-1 text-xs rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                />
                <button
                    type="submit"
                    :disabled="isSending || !newMessage.trim()"
                    class="primary-button text-xs py-2 px-4 flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
                >
                    <span v-if="isSending" class="animate-spin text-xs">⏳</span>
                    <span v-else>✈️</span>
                    Enviar
                </button>
            </form>
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
                                this.$nextTick(() => this.scrollToBottom());
                            } else {
                                this.configured = false;
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
                    if (!this.newMessage.trim()) return;

                    this.isSending = true;
                    const text = this.newMessage;
                    this.$axios.post(`/admin/leads/${this.leadId}/chatwoot/send`, { message: text })
                        .then(response => {
                            this.newMessage = '';
                            this.fetchMessages();
                        })
                        .catch(err => {
                            alert(err.response?.data?.message || 'Error al enviar mensaje vía Chatwoot.');
                        })
                        .finally(() => {
                            this.isSending = false;
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

<v-lead-dmi-documents :lead-id="{{ $lead->id }}"></v-lead-dmi-documents>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-dmi-documents-template">
        <div class="flex flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">⏳</span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            @lang('admin::insurance.dmi_documents.title')
                            <span v-if="documents.length" class="text-xs font-normal text-gray-500">
                                (@{{ documents.length }} {{ trans_choice('admin::insurance.dmi_documents.requirements_count', $lead->dmiDocuments()->count()) }})
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500">@lang('admin::insurance.dmi_documents.subtitle')</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                        @click="fetchDocuments"
                        :disabled="isLoading"
                    >
                        <span class="icon-refresh text-sm" :class="{'animate-spin': isLoading}"></span>
                        @lang('admin::insurance.dmi_documents.btn_refresh')
                    </button>

                    <button
                        type="button"
                        class="primary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                        @click="openAddModal"
                    >
                        <span class="icon-add text-sm"></span>
                        @lang('admin::insurance.dmi_documents.btn_add_dmi')
                    </button>
                </div>
            </div>

            <!-- Urgency Counter Pills -->
            <div v-if="summary.total > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="p-3 rounded-xl border bg-rose-50/70 border-rose-200 dark:bg-rose-950/20 dark:border-rose-900 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-rose-700 dark:text-rose-300 uppercase block">@lang('admin::insurance.dmi_documents.filter_critical')</span>
                        <span class="text-xl font-bold text-rose-900 dark:text-rose-200">@{{ summary.critical }}</span>
                    </div>
                    <span class="text-2xl">🚨</span>
                </div>

                <div class="p-3 rounded-xl border bg-amber-50/70 border-amber-200 dark:bg-amber-950/20 dark:border-amber-900 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 uppercase block">@lang('admin::insurance.dmi_documents.filter_warning')</span>
                        <span class="text-xl font-bold text-amber-900 dark:text-amber-200">@{{ summary.warning }}</span>
                    </div>
                    <span class="text-2xl">⚠️</span>
                </div>

                <div class="p-3 rounded-xl border bg-blue-50/70 border-blue-200 dark:bg-blue-950/20 dark:border-blue-900 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-blue-700 dark:text-blue-300 uppercase block">@lang('admin::insurance.dmi_documents.filter_on_time')</span>
                        <span class="text-xl font-bold text-blue-900 dark:text-blue-200">@{{ summary.total - summary.critical - summary.warning - summary.verified - summary.expired }}</span>
                    </div>
                    <span class="text-2xl">📅</span>
                </div>

                <div class="p-3 rounded-xl border bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-900 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 uppercase block">@lang('admin::insurance.dmi_documents.filter_verified')</span>
                        <span class="text-xl font-bold text-emerald-900 dark:text-emerald-200">@{{ summary.verified }}</span>
                    </div>
                    <span class="text-2xl">🏆</span>
                </div>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="isLoading && !documents.length" class="flex flex-col gap-3 py-6">
                <div class="h-14 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg"></div>
                <div class="h-14 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg"></div>
            </div>

            <!-- Empty State -->
            <div v-else-if="!documents.length" class="flex flex-col items-center justify-center py-12 text-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-dashed border-gray-200 dark:border-gray-800">
                <span class="text-4xl mb-2">🎉</span>
                <p class="font-semibold text-sm text-gray-800 dark:text-gray-200">@lang('admin::insurance.dmi_documents.no_inconsistencies')</p>
                <p class="text-xs text-gray-400 max-w-sm mt-1">@lang('admin::insurance.dmi_documents.no_inconsistencies_sub')</p>
                <button
                    type="button"
                    class="primary-button text-xs mt-4"
                    @click="openAddModal"
                >
                    @lang('admin::insurance.dmi_documents.btn_register_dmi')
                </button>
            </div>

            <!-- Documents List -->
            <div v-else class="space-y-3">
                <div
                    v-for="doc in documents"
                    :key="doc.id"
                    :class="cardBorderClass(doc)"
                    class="bg-white dark:bg-gray-900 border rounded-xl p-4 shadow-sm space-y-3 transition-all hover:shadow-md"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span :class="urgencyBadgeClass(doc)" class="text-xs font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">
                                    <span v-if="doc.urgency_level === 'critical'">🚨</span>
                                    <span v-else-if="doc.urgency_level === 'warning'">⚠️</span>
                                    <span v-else-if="doc.urgency_level === 'verified'">🏆</span>
                                    <span v-else>🟢</span>
                                    @{{ urgencyLabel(doc) }}
                                </span>

                                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                    @{{ doc.doc_type_label }}
                                </span>
                            </div>

                            <h4 class="text-sm font-bold text-gray-900 dark:text-white pt-1">
                                @{{ doc.title }}
                            </h4>
                        </div>

                        <!-- Status Selector Dropdown -->
                        <div class="flex items-center gap-2">
                            <select
                                :value="doc.status"
                                @change="updateStatus(doc, $event.target.value)"
                                class="text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="pending_upload">🟡 @lang('admin::insurance.dmi_documents.status_pending_upload')</option>
                                <option value="uploaded_to_marketplace">🔵 @lang('admin::insurance.dmi_documents.status_uploaded')</option>
                                <option value="verified_by_cms">🟢 @lang('admin::insurance.dmi_documents.status_verified')</option>
                                <option value="rejected">🔴 @lang('admin::insurance.dmi_documents.status_rejected')</option>
                            </select>

                            <button
                                type="button"
                                @click="deleteDocument(doc)"
                                class="text-gray-400 hover:text-rose-600 p-1 rounded transition-colors"
                                title="{{ trans('admin::insurance.dmi_documents.delete') }}"
                            >
                                <span class="icon-delete text-base"></span>
                            </button>
                        </div>
                    </div>

                    <!-- 90-Day Visual Countdown Bar -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-[11px] text-gray-500 font-medium">
                            <span>@lang('admin::insurance.dmi_documents.notice_date') <strong>@{{ formatDate(doc.notice_date) }}</strong></span>
                            <span :class="doc.days_remaining <= 15 ? 'text-rose-600 font-bold' : ''">
                                @lang('admin::insurance.dmi_documents.col_deadline'): <strong>@{{ formatDate(doc.deadline_date) }}</strong>
                                (@{{ doc.days_remaining }} {{ trans('admin::insurance.dmi_documents.days_remaining', ['days' => '']) }})
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                            <div
                                :class="progressBarColor(doc)"
                                class="h-2 rounded-full transition-all duration-500"
                                :style="{ width: calculateProgress(doc) + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Actions and File Attachment -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-xs">
                        <div class="flex items-center gap-2">
                            <span v-if="doc.file_path" class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 font-medium">
                                <span>📎</span>
                                <a :href="'/storage/' + doc.file_path" target="_blank" class="hover:underline">
                                    @{{ doc.file_name || '{{ trans('admin::insurance.dmi_documents.view_attachment') }}' }}
                                </a>
                            </span>
                            <span v-else class="text-gray-400 italic">
                                @lang('admin::insurance.dmi_documents.no_attachment')
                            </span>
                        </div>

                        <!-- 1-Click WhatsApp Reminder Button -->
                        <button
                            type="button"
                            @click="sendWhatsAppReminder(doc)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg transition-colors shadow-sm"
                        >
                            <span>📲</span>
                            @lang('admin::insurance.dmi_documents.send_whatsapp_reminder', ['days' => '']) @{{ doc.days_remaining }}d)
                        </button>
                    </div>

                    <!-- Notes if any -->
                    <p v-if="doc.notes" class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-950 p-2 rounded border border-gray-100 dark:border-gray-800">
                        💬 @{{ doc.notes }}
                    </p>
                </div>
            </div>

            <!-- Modal: Add New DMI Requirement -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📄</span> @lang('admin::insurance.dmi_documents.modal_add_title')
                        </h3>
                        <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                    </div>

                    <form @submit.prevent="submitAddDocument" class="space-y-3.5 text-xs">
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.dmi_documents.doc_type_label')</label>
                            <select
                                v-model="form.doc_type"
                                @change="onDocTypeChange"
                                class="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500"
                                required
                            >
                                <option value="income">@lang('admin::insurance.dmi_documents.doc_income')</option>
                                <option value="immigration">@lang('admin::insurance.dmi_documents.doc_immigration')</option>
                                <option value="citizenship">@lang('admin::insurance.dmi_documents.doc_citizenship')</option>
                                <option value="ssn">@lang('admin::insurance.dmi_documents.doc_ssn')</option>
                                <option value="incarceration">@lang('admin::insurance.dmi_documents.doc_incarceration')</option>
                                <option value="other">@lang('admin::insurance.dmi_documents.doc_other')</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.dmi_documents.doc_title_label')</label>
                            <input
                                type="text"
                                v-model="form.title"
                                class="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500"
                                placeholder="{{ trans('admin::insurance.dmi_documents.doc_title_placeholder') }}"
                                required
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.dmi_documents.notice_date_label')</label>
                                <input
                                    type="date"
                                    v-model="form.notice_date"
                                    @change="recalculateDeadline"
                                    class="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.dmi_documents.deadline_date_label')</label>
                                <input
                                    type="date"
                                    v-model="form.deadline_date"
                                    class="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.dmi_documents.file_upload_label')</label>
                            <input
                                type="file"
                                ref="fileInput"
                                class="w-full p-2 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-600"
                                accept=".pdf,.jpg,.jpeg,.png"
                            >
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.dmi_documents.notes_label')</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                class="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="secondary-button text-xs py-2 px-3.5"
                            >
                                @lang('admin::insurance.dmi_documents.cancel')
                            </button>

                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="primary-button text-xs py-2 px-4 flex items-center gap-1.5"
                            >
                                <span v-if="isSubmitting" class="icon-refresh animate-spin"></span>
                                @lang('admin::insurance.dmi_documents.save')
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </script>

    <script type="module">
        app.component('v-lead-dmi-documents', {
            template: '#v-lead-dmi-documents-template',

            props: ['leadId'],

            data() {
                return {
                    isLoading: false,
                    isSubmitting: false,
                    showModal: false,
                    documents: [],
                    summary: {
                        total: 0,
                        critical: 0,
                        warning: 0,
                        verified: 0,
                        expired: 0,
                    },
                    form: {
                        doc_type: 'income',
                        title: '{{ trans('admin::insurance.dmi_documents.doc_income') }}',
                        notice_date: new Date().toISOString().split('T')[0],
                        deadline_date: this.addDays(new Date(), 90),
                        notes: '',
                    }
                };
            },

            mounted() {
                this.fetchDocuments();
            },

            methods: {
                addDays(date, days) {
                    const result = new Date(date);
                    result.setDate(result.getDate() + days);
                    return result.toISOString().split('T')[0];
                },

                recalculateDeadline() {
                    if (this.form.notice_date) {
                        this.form.deadline_date = this.addDays(new Date(this.form.notice_date), 90);
                    }
                },

                onDocTypeChange() {
                    const titles = {
                        income: '{{ trans('admin::insurance.dmi_documents.doc_income') }}',
                        immigration: '{{ trans('admin::insurance.dmi_documents.doc_immigration') }}',
                        citizenship: '{{ trans('admin::insurance.dmi_documents.doc_citizenship') }}',
                        ssn: '{{ trans('admin::insurance.dmi_documents.doc_ssn') }}',
                        incarceration: '{{ trans('admin::insurance.dmi_documents.doc_incarceration') }}',
                        other: '{{ trans('admin::insurance.dmi_documents.doc_other') }}',
                    };
                    this.form.title = titles[this.form.doc_type] || '{{ trans('admin::insurance.dmi_documents.doc_other') }}';
                },

                fetchDocuments() {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.leads.dmi.index', $lead->id) }}")
                        .then(response => {
                            this.isLoading = false;
                            if (response.data.success) {
                                this.documents = response.data.documents;
                                this.summary = response.data.summary;
                            }
                        })
                        .catch(err => {
                            this.isLoading = false;
                            console.error(err);
                        });
                },

                openAddModal() {
                    this.form = {
                        doc_type: 'income',
                        title: '{{ trans('admin::insurance.dmi_documents.doc_income') }}',
                        notice_date: new Date().toISOString().split('T')[0],
                        deadline_date: this.addDays(new Date(), 90),
                        notes: '',
                    };
                    this.showModal = true;
                },

                submitAddDocument() {
                    this.isSubmitting = true;

                    const formData = new FormData();
                    formData.append('doc_type', this.form.doc_type);
                    formData.append('title', this.form.title);
                    formData.append('notice_date', this.form.notice_date);
                    formData.append('deadline_date', this.form.deadline_date);
                    formData.append('notes', this.form.notes || '');

                    if (this.$refs.fileInput && this.$refs.fileInput.files[0]) {
                        formData.append('file', this.$refs.fileInput.files[0]);
                    }

                    this.$axios.post("{{ route('admin.leads.dmi.store', $lead->id) }}", formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    })
                    .then(response => {
                        this.isSubmitting = false;
                        this.showModal = false;
                        this.fetchDocuments();
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message,
                        });
                    })
                    .catch(err => {
                        this.isSubmitting = false;
                        console.error(err);
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: err?.response?.data?.message || 'Error.',
                        });
                    });
                },

                updateStatus(doc, newStatus) {
                    this.$axios.put("{{ route('admin.leads.dmi.update', [$lead->id, 'replaceId']) }}".replace('replaceId', doc.id), {
                        status: newStatus
                    })
                    .then(response => {
                        doc.status = newStatus;
                        this.fetchDocuments();
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message || 'Status updated.',
                        });
                    })
                    .catch(err => {
                        console.error(err);
                    });
                },

                deleteDocument(doc) {
                    if (!confirm('{{ trans('admin::insurance.dmi_documents.delete') }}?')) return;

                    this.$axios.delete("{{ route('admin.leads.dmi.delete', [$lead->id, 'replaceId']) }}".replace('replaceId', doc.id))
                        .then(response => {
                            this.fetchDocuments();
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message,
                            });
                        })
                        .catch(err => {
                            console.error(err);
                        });
                },

                sendWhatsAppReminder(doc) {
                    this.$axios.get("{{ route('admin.leads.dmi.whatsapp', [$lead->id, 'replaceId']) }}".replace('replaceId', doc.id))
                        .then(response => {
                            if (response.data.whatsapp_url) {
                                window.open(response.data.whatsapp_url, '_blank');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                        });
                },

                calculateProgress(doc) {
                    if (!doc.notice_date || !doc.deadline_date) return 100;
                    const start = new Date(doc.notice_date).getTime();
                    const end = new Date(doc.deadline_date).getTime();
                    const now = new Date().getTime();
                    if (now <= start) return 5;
                    if (now >= end) return 100;
                    const elapsed = ((now - start) / (end - start)) * 100;
                    return Math.min(Math.max(elapsed, 5), 100);
                },

                progressBarColor(doc) {
                    if (doc.status === 'verified_by_cms') return 'bg-emerald-500';
                    if (doc.days_remaining <= 15) return 'bg-rose-500';
                    if (doc.days_remaining <= 45) return 'bg-amber-500';
                    return 'bg-blue-500';
                },

                cardBorderClass(doc) {
                    if (doc.status === 'verified_by_cms') return 'border-emerald-200 dark:border-emerald-900/60';
                    if (doc.days_remaining <= 15) return 'border-rose-300 dark:border-rose-900 bg-rose-50/10';
                    if (doc.days_remaining <= 45) return 'border-amber-200 dark:border-amber-900/60';
                    return 'border-gray-200 dark:border-gray-800';
                },

                urgencyBadgeClass(doc) {
                    if (doc.status === 'verified_by_cms') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
                    if (doc.days_remaining < 0) return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
                    if (doc.days_remaining <= 15) return 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 animate-pulse';
                    if (doc.days_remaining <= 45) return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
                    return 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300';
                },

                urgencyLabel(doc) {
                    if (doc.status === 'verified_by_cms') return '{{ trans('admin::insurance.dmi_documents.status_verified') }}';
                    if (doc.days_remaining < 0) return '{{ trans('admin::insurance.sep_validator.sep_expired') }}';
                    if (doc.days_remaining <= 15) return `! ${doc.days_remaining} d`;
                    return `${doc.days_remaining} d`;
                },

                formatDate(dateStr) {
                    if (!dateStr) return 'N/A';
                    return new Date(dateStr).toLocaleDateString('{{ app()->getLocale() }}');
                }
            }
        });
    </script>
@endPushOnce

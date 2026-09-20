<v-lead-enrollment-period></v-lead-enrollment-period>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-lead-enrollment-period-template"
    >
        <div class="p-4 bg-white dark:bg-slate-900 rounded-xl">
            <!-- Loading indicator -->
            <div v-if="isLoading" class="py-8 text-center text-slate-500">
                <span class="inline-block animate-spin text-2xl">⏳</span>
                <p class="text-xs mt-2 font-semibold">@lang('admin::insurance.sep_validator.consulting')</p>
            </div>

            <div v-else class="space-y-5">
                <!-- Federal Period Global Status Banner -->
                <div 
                    class="rounded-xl p-4 border flex items-start gap-3.5 shadow-sm"
                    :class="federalStatus.is_oep ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-800' : 'bg-blue-50 dark:bg-blue-950/30 border-blue-300 dark:border-blue-800'"
                >
                    <span class="text-2xl">@{{ federalStatus.is_oep ? '🎉' : '🛡️' }}</span>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="font-extrabold text-sm" :class="federalStatus.is_oep ? 'text-emerald-900 dark:text-emerald-200' : 'text-blue-900 dark:text-blue-200'">
                                @{{ federalStatus.period_name }}
                            </h4>
                            <span v-if="federalStatus.days_to_deadline !== null" class="text-xs font-bold px-2 py-0.5 rounded-full" :class="federalStatus.is_oep ? 'bg-emerald-200 text-emerald-900' : 'bg-blue-200 text-blue-900'">
                                @{{ federalStatus.days_to_deadline }} {{ trans('admin::insurance.sep_validator.days_to_deadline', ['days' => '']) }}
                            </span>
                        </div>
                        <p class="text-xs mt-1 leading-relaxed" :class="federalStatus.is_oep ? 'text-emerald-800 dark:text-emerald-300' : 'text-blue-800 dark:text-blue-300'">
                            @{{ federalStatus.description }}
                        </p>
                    </div>
                </div>

                <!-- Active SEP Qualification for this Lead -->
                <div v-if="qualification" class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-5 border border-slate-200 dark:border-slate-700">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-200 dark:border-slate-700">
                        <div>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.sep_validator.qualifying_event_registered')</span>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white mt-0.5">
                                @{{ qualification.event_label }}
                            </h3>
                        </div>

                        <!-- Status Badge -->
                        <div>
                            <span 
                                v-if="qualification.is_eligible && !qualification.is_expired"
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200 text-xs font-bold rounded-full border border-emerald-300"
                            >
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                @lang('admin::insurance.sep_validator.sep_valid', ['days' => '']) @{{ qualification.days_remaining }}
                            </span>
                            <span 
                                v-else
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-100 dark:bg-rose-900/50 text-rose-800 dark:text-rose-200 text-xs font-bold rounded-full border border-rose-300"
                            >
                                @lang('admin::insurance.sep_validator.sep_expired')
                            </span>
                        </div>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-5 text-xs">
                        <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-700">
                            <div class="text-slate-500 font-medium">@lang('admin::insurance.sep_validator.event_date')</div>
                            <div class="font-bold text-slate-900 dark:text-white text-sm mt-0.5">
                                @{{ qualification.event_date }}
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-700">
                            <div class="text-slate-500 font-medium">@lang('admin::insurance.sep_validator.sep_deadline')</div>
                            <div class="font-bold text-slate-900 dark:text-white text-sm mt-0.5">
                                @{{ qualification.sep_deadline }}
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-700">
                            <div class="text-slate-500 font-medium">@lang('admin::insurance.sep_validator.effective_date')</div>
                            <div class="font-bold text-sky-600 dark:text-sky-400 text-sm mt-0.5">
                                @{{ qualification.effective_date || '{{ trans('admin::insurance.sep_validator.immediate') }}' }}
                            </div>
                        </div>
                    </div>

                    <!-- CMS Verification Document Checklist -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                                <span>📋</span> @lang('admin::insurance.sep_validator.doc_checklist')
                            </h5>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">
                                @{{ (qualification.verified_documents || []).length }} / @{{ (qualification.required_documents || []).length }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            <label 
                                v-for="(doc, idx) in qualification.required_documents" 
                                :key="idx"
                                class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer text-xs"
                            >
                                <input 
                                    type="checkbox" 
                                    :checked="(qualification.verified_documents || []).includes(doc)"
                                    @change="toggleDoc(doc, $event.target.checked)"
                                    class="mt-0.5 rounded text-sky-600 focus:ring-sky-500"
                                >
                                <span 
                                    :class="(qualification.verified_documents || []).includes(doc) ? 'line-through text-slate-400 font-normal' : 'text-slate-700 dark:text-slate-200 font-medium'"
                                >
                                    @{{ doc }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button 
                            type="button" 
                            @click="isEditing = !isEditing"
                            class="text-xs text-sky-600 hover:text-sky-700 font-bold"
                        >
                            @{{ isEditing ? '{{ trans('admin::insurance.sep_validator.cancel_edit') }}' : '{{ trans('admin::insurance.sep_validator.modify_qle') }}' }}
                        </button>
                    </div>
                </div>

                <!-- Form to Register / Modify Qualifying Event -->
                <div v-if="!qualification || isEditing" class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-5 border border-slate-200 dark:border-slate-700">
                    <h4 class="font-extrabold text-sm text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                        <span>🗓️</span> @lang('admin::insurance.sep_validator.form_title')
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                @lang('admin::insurance.sep_validator.col_qle_event')
                            </label>
                            <select 
                                v-model="form.event_type" 
                                class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500"
                            >
                                <option value="" disabled>@lang('admin::insurance.sep_validator.select_event')</option>
                                <option v-for="(def, key) in eventDefinitions" :key="key" :value="key">
                                    @{{ def.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                @lang('admin::insurance.sep_validator.col_event_date')
                            </label>
                            <input 
                                type="date" 
                                v-model="form.event_date" 
                                class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500"
                            >
                        </div>
                    </div>

                    <!-- Dynamic Required Documents Preview -->
                    <div v-if="form.event_type && eventDefinitions[form.event_type]" class="bg-sky-50 dark:bg-sky-950/30 border border-sky-200 dark:border-sky-800 rounded-lg p-3 mb-4 text-xs text-sky-900 dark:text-sky-200">
                        <div class="font-bold mb-1">@lang('admin::insurance.sep_validator.marketplace_docs_preview')</div>
                        <ul class="list-disc pl-4 space-y-0.5">
                            <li v-for="(d, i) in eventDefinitions[form.event_type].documents" :key="i">
                                @{{ d }}
                            </li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            @lang('admin::insurance.sep_validator.agent_notes')
                        </label>
                        <textarea 
                            v-model="form.notes" 
                            rows="2" 
                            placeholder="{{ trans('admin::insurance.sep_validator.notes_placeholder') }}" 
                            class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button 
                            type="button" 
                            @click="saveQualification()"
                            class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm"
                        >
                            @lang('admin::insurance.sep_validator.btn_validate_register')
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-lead-enrollment-period', {
            template: '#v-lead-enrollment-period-template',

            data() {
                return {
                    isLoading: true,
                    isEditing: false,
                    federalStatus: {},
                    qualification: null,
                    eventDefinitions: {},
                    form: {
                        event_type: 'loss_of_coverage',
                        event_date: new Date().toISOString().split('T')[0],
                        notes: '',
                    },
                }
            },

            mounted() {
                this.loadData();
            },

            methods: {
                loadData() {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.leads.enrollment.get', $lead->id) }}")
                        .then(response => {
                            this.isLoading = false;
                            this.federalStatus = response.data.federal_status || {};
                            this.qualification = response.data.qualification || null;
                            this.eventDefinitions = response.data.event_definitions || {};

                            if (this.qualification) {
                                this.form.event_type = this.qualification.event_type;
                                this.form.event_date = this.qualification.event_date;
                                this.form.notes = this.qualification.notes || '';
                            }
                        })
                        .catch(err => {
                            this.isLoading = false;
                            console.error('Error loading enrollment period data:', err);
                        });
                },

                saveQualification() {
                    if (! this.form.event_type || ! this.form.event_date) {
                        alert('{{ trans('admin::insurance.sep_validator.prompt_incomplete') }}');
                        return;
                    }

                    this.$axios.post("{{ route('admin.leads.enrollment.save', $lead->id) }}", this.form)
                        .then(response => {
                            if (response.data.success) {
                                this.qualification = response.data.qualification;
                                this.isEditing = false;
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                            }
                        })
                        .catch(err => {
                            alert(err?.response?.data?.message || 'Error.');
                        });
                },

                toggleDoc(documentName, verified) {
                    this.$axios.post("{{ route('admin.leads.enrollment.toggle_document', $lead->id) }}", {
                        document: documentName,
                        verified: verified,
                    })
                    .then(response => {
                        if (response.data.success) {
                            this.qualification = response.data.qualification;
                            if (response.data.all_verified) {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: '{{ trans('admin::insurance.sep_validator.all_verified_flash') }}',
                                });
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Error updating document checklist:', err);
                    });
                }
            }
        });
    </script>
@endPushOnce

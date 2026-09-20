<v-lead-household :lead-id="{{ $lead->id }}"></v-lead-household>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-household-template">
        <div class="flex flex-col gap-4 p-4">
            <!-- Header with Title and Add Button -->
            <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <span class="icon-user text-xl text-brandColor"></span>
                    <h3 class="text-base font-semibold dark:text-white">
                        @lang('admin::insurance.household.title')
                        <span class="ml-1 text-xs font-normal text-gray-500" v-if="members.length">
                            (@{{ members.length }} @{{ members.length === 1 ? labels.memberSingle : labels.memberPlural }})
                        </span>
                    </h3>
                </div>

                <button
                    type="button"
                    class="secondary-button flex items-center gap-1 text-xs py-1.5 px-3"
                    @click="openAddModal"
                >
                    <span class="icon-add text-md"></span>
                    @lang('admin::insurance.household.add_btn')
                </button>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="isLoading" class="flex flex-col gap-2 py-4">
                <div class="h-10 bg-gray-100 dark:bg-gray-800 animate-pulse rounded"></div>
                <div class="h-10 bg-gray-100 dark:bg-gray-800 animate-pulse rounded"></div>
            </div>

            <!-- Empty State -->
            <div v-else-if="! members.length" class="flex flex-col items-center justify-center py-8 text-center text-gray-500 dark:text-gray-400">
                <span class="icon-user text-4xl mb-2 text-gray-300 dark:text-gray-600"></span>
                <p class="font-medium text-sm">@lang('admin::insurance.household.empty_title')</p>
                <p class="text-xs text-gray-400 max-w-sm mt-1">@lang('admin::insurance.household.empty_description')</p>
                <button
                    type="button"
                    class="primary-button text-xs mt-4"
                    @click="openAddModal"
                >
                    @lang('admin::insurance.household.add_btn')
                </button>
            </div>

            <!-- Members Table -->
            <div v-else class="overflow-x-auto rounded border border-gray-200 dark:border-gray-800">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-950 text-gray-600 dark:text-gray-300 font-medium text-xs uppercase border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="py-2.5 px-3">@lang('admin::insurance.household.name')</th>
                            <th class="py-2.5 px-3">@lang('admin::insurance.household.relationship')</th>
                            <th class="py-2.5 px-3">@lang('admin::insurance.household.dob_age')</th>
                            <th class="py-2.5 px-3">@lang('admin::insurance.household.gender')</th>
                            <th class="py-2.5 px-3">@lang('admin::insurance.household.ssn_itin')</th>
                            <th class="py-2.5 px-3">@lang('admin::insurance.household.coverage_status')</th>
                            <th class="py-2.5 px-3 text-right">@lang('admin::insurance.household.actions')</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        <tr v-for="member in members" :key="member.id" class="hover:bg-gray-50 dark:hover:bg-gray-950 transition">
                            <td class="py-2.5 px-3 font-semibold dark:text-white">
                                @{{ member.name }}
                                <span v-if="member.tobacco_user" class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800">
                                    @lang('admin::insurance.household.tobacco')
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-gray-700 dark:text-gray-300">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                    @{{ getRelationshipLabel(member.relationship) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-gray-700 dark:text-gray-300">
                                <span v-if="member.date_of_birth">
                                    @{{ member.date_of_birth }}
                                    <span v-if="member.age !== null" class="text-xs text-gray-500">(@{{ member.age }} @{{ labels.yearsShort }})</span>
                                </span>
                                <span v-else class="text-gray-400">--</span>
                            </td>
                            <td class="py-2.5 px-3 text-gray-700 dark:text-gray-300">
                                @{{ getGenderLabel(member.gender) }}
                            </td>
                            <td class="py-2.5 px-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                @{{ member.ssn_itin || '--' }}
                            </td>
                            <td class="py-2.5 px-3">
                                <span
                                    v-if="member.is_applying_coverage"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                >
                                    @lang('admin::insurance.household.applying_yes')
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                                >
                                    @lang('admin::insurance.household.applying_no')
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="text-gray-500 hover:text-brandColor transition"
                                        :title="labels.edit"
                                        @click="openEditModal(member)"
                                    >
                                        <span class="icon-edit text-lg"></span>
                                    </button>
                                    <button
                                        type="button"
                                        class="text-gray-500 hover:text-red-600 transition"
                                        :title="labels.delete"
                                        @click="deleteMember(member.id)"
                                    >
                                        <span class="icon-delete text-lg"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Member Add / Edit Modal -->
            <x-admin::modal ref="memberModal" position="center">
                <x-slot:header>
                    <h3 class="text-base font-semibold dark:text-white">
                        @{{ isEditing ? labels.editModalTitle : labels.addModalTitle }}
                    </h3>
                </x-slot>

                <x-slot:content>
                    <form @submit.prevent="saveMember" class="flex flex-col gap-3">
                        <!-- Name -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-700 dark:text-gray-300 required">
                                @lang('admin::insurance.household.form.name')
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                required
                                class="rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                            />
                        </div>

                        <!-- Relationship & Gender Row -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300 required">
                                    @lang('admin::insurance.household.form.relationship')
                                </label>
                                <select
                                    v-model="form.relationship"
                                    required
                                    class="rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                                >
                                    <option value="spouse">@lang('admin::insurance.household.relationships.spouse')</option>
                                    <option value="child">@lang('admin::insurance.household.relationships.child')</option>
                                    <option value="parent">@lang('admin::insurance.household.relationships.parent')</option>
                                    <option value="dependent">@lang('admin::insurance.household.relationships.dependent')</option>
                                    <option value="other">@lang('admin::insurance.household.relationships.other')</option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    @lang('admin::insurance.household.form.gender')
                                </label>
                                <select
                                    v-model="form.gender"
                                    class="rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                                >
                                    <option value="">--</option>
                                    <option value="male">@lang('admin::insurance.household.genders.male')</option>
                                    <option value="female">@lang('admin::insurance.household.genders.female')</option>
                                    <option value="other">@lang('admin::insurance.household.genders.other')</option>
                                </select>
                            </div>
                        </div>

                        <!-- DOB & SSN Row -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    @lang('admin::insurance.household.form.dob')
                                </label>
                                <input
                                    type="date"
                                    v-model="form.date_of_birth"
                                    class="rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                                />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    @lang('admin::insurance.household.form.ssn_itin')
                                </label>
                                <input
                                    type="text"
                                    v-model="form.ssn_itin"
                                    class="rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                                    placeholder="XXX-XX-XXXX"
                                />
                            </div>
                        </div>

                        <!-- Immigration Status -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                @lang('admin::insurance.household.form.immigration_status')
                            </label>
                            <select
                                v-model="form.immigration_status"
                                class="rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                            >
                                <option value="">--</option>
                                <option value="us_citizen">@lang('admin::insurance.household.immigration.us_citizen')</option>
                                <option value="permanent_resident">@lang('admin::insurance.household.immigration.permanent_resident')</option>
                                <option value="work_authorization">@lang('admin::insurance.household.immigration.work_authorization')</option>
                                <option value="asylee">@lang('admin::insurance.household.immigration.asylee')</option>
                                <option value="visa_holder">@lang('admin::insurance.household.immigration.visa_holder')</option>
                                <option value="other">@lang('admin::insurance.household.immigration.other')</option>
                            </select>
                        </div>

                        <!-- Checkboxes: Applying for coverage & Tobacco -->
                        <div class="flex items-center gap-6 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    v-model="form.is_applying_coverage"
                                    class="rounded border-gray-300 text-brandColor focus:ring-brandColor"
                                />
                                @lang('admin::insurance.household.form.is_applying_coverage')
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    v-model="form.tobacco_user"
                                    class="rounded border-gray-300 text-brandColor focus:ring-brandColor"
                                />
                                @lang('admin::insurance.household.form.tobacco_user')
                            </label>
                        </div>

                        <!-- Notes -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                @lang('admin::insurance.household.form.notes')
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                class="rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                            ></textarea>
                        </div>
                    </form>
                </x-slot>

                <x-slot:footer>
                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="secondary-button text-xs"
                            @click="$refs.memberModal.close()"
                        >
                            @lang('admin::insurance.household.cancel')
                        </button>
                        <button
                            type="button"
                            class="primary-button text-xs"
                            :disabled="isSaving"
                            @click="saveMember"
                        >
                            @{{ isSaving ? labels.saving : (isEditing ? labels.updateBtn : labels.saveBtn) }}
                        </button>
                    </div>
                </x-slot>
            </x-admin::modal>
        </div>
    </script>

    <script type="module">
        app.component('v-lead-household', {
            template: '#v-lead-household-template',

            props: {
                leadId: {
                    type: Number,
                    required: true,
                },
            },

            data() {
                return {
                    members: [],
                    isLoading: true,
                    isSaving: false,
                    isEditing: false,
                    editingId: null,
                    labels: {
                        edit: @json(trans('admin::insurance.household.edit')),
                        delete: @json(trans('admin::insurance.household.delete')),
                        addModalTitle: @json(trans('admin::insurance.household.add_modal_title')),
                        editModalTitle: @json(trans('admin::insurance.household.edit_modal_title')),
                        saveBtn: @json(trans('admin::insurance.household.save_btn')),
                        updateBtn: @json(trans('admin::insurance.household.update_btn')),
                        cancel: @json(trans('admin::insurance.household.cancel')),
                        confirmDelete: @json(trans('admin::insurance.household.confirm_delete')),
                        memberSingle: @json(trans('admin::insurance.household.member_single')),
                        memberPlural: @json(trans('admin::insurance.household.member_plural')),
                        yearsShort: @json(trans('admin::insurance.household.years_short')),
                        saving: @json(trans('admin::insurance.household.saving')),
                        errorSave: @json(trans('admin::insurance.household.error_save')),
                        errorDelete: @json(trans('admin::insurance.household.error_delete')),
                    },
                    form: {
                        name: '',
                        relationship: 'spouse',
                        date_of_birth: '',
                        gender: '',
                        ssn_itin: '',
                        immigration_status: '',
                        is_applying_coverage: true,
                        tobacco_user: false,
                        notes: '',
                    },
                    relationshipLabels: {
                        spouse: @json(trans('admin::insurance.household.relationships.spouse')),
                        child: @json(trans('admin::insurance.household.relationships.child')),
                        parent: @json(trans('admin::insurance.household.relationships.parent')),
                        dependent: @json(trans('admin::insurance.household.relationships.dependent')),
                        other: @json(trans('admin::insurance.household.relationships.other')),
                    },
                    genderLabels: {
                        male: @json(trans('admin::insurance.household.genders.male')),
                        female: @json(trans('admin::insurance.household.genders.female')),
                        other: @json(trans('admin::insurance.household.genders.other')),
                    },
                };
            },

            mounted() {
                this.fetchMembers();
            },

            methods: {
                getRelationshipLabel(rel) {
                    return this.relationshipLabels[rel] || rel;
                },

                getGenderLabel(gender) {
                    if (! gender) return '--';
                    return this.genderLabels[gender] || gender;
                },

                fetchMembers() {
                    this.isLoading = true;
                    this.$axios.get(`/admin/leads/${this.leadId}/household-members`)
                        .then(response => {
                            this.members = response.data.data || [];
                            this.isLoading = false;
                        })
                        .catch(error => {
                            console.error(error);
                            this.isLoading = false;
                        });
                },

                resetForm() {
                    this.form = {
                        name: '',
                        relationship: 'spouse',
                        date_of_birth: '',
                        gender: '',
                        ssn_itin: '',
                        immigration_status: '',
                        is_applying_coverage: true,
                        tobacco_user: false,
                        notes: '',
                    };
                    this.isEditing = false;
                    this.editingId = null;
                },

                openAddModal() {
                    this.resetForm();
                    this.$refs.memberModal.open();
                },

                openEditModal(member) {
                    this.isEditing = true;
                    this.editingId = member.id;
                    this.form = {
                        name: member.name,
                        relationship: member.relationship,
                        date_of_birth: member.date_of_birth ? member.date_of_birth.substring(0, 10) : '',
                        gender: member.gender || '',
                        ssn_itin: member.ssn_itin || '',
                        immigration_status: member.immigration_status || '',
                        is_applying_coverage: Boolean(member.is_applying_coverage),
                        tobacco_user: Boolean(member.tobacco_user),
                        notes: member.notes || '',
                    };
                    this.$refs.memberModal.open();
                },

                saveMember() {
                    if (! this.form.name) return;

                    this.isSaving = true;
                    const url = this.isEditing
                        ? `/admin/leads/${this.leadId}/household-members/${this.editingId}`
                        : `/admin/leads/${this.leadId}/household-members`;

                    const method = this.isEditing ? 'put' : 'post';

                    this.$axios[method](url, this.form)
                        .then(response => {
                            this.isSaving = false;
                            this.$refs.memberModal.close();
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message
                            });
                            this.fetchMembers();
                        })
                        .catch(error => {
                            this.isSaving = false;
                            const msg = error.response?.data?.message || this.labels.errorSave;
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: msg
                            });
                        });
                },

                deleteMember(id) {
                    if (! confirm(this.labels.confirmDelete)) {
                        return;
                    }

                    this.$axios.delete(`/admin/leads/${this.leadId}/household-members/${id}`)
                        .then(response => {
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message
                            });
                            this.fetchMembers();
                        })
                        .catch(error => {
                            const msg = error.response?.data?.message || this.labels.errorDelete;
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: msg
                            });
                        });
                },
            },
        });
    </script>
@endpushOnce
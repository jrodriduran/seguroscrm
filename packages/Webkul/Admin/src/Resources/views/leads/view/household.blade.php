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

            <!-- FPL Eligibility & ACA Subsidy Card -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 font-bold text-xs">
                            %
                        </span>
                        <div>
                            <h4 class="text-sm font-semibold dark:text-white flex items-center gap-2">
                                @lang('admin::insurance.household.fpl_title')
                                <span class="text-xs px-2 py-0.5 rounded font-mono font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300" v-if="fplCalc">
                                    Año @{{ fplCalc.tax_year }} • @{{ fplCalc.state_code }}
                                </span>
                            </h4>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span v-if="fplCalc && fplCalc.is_zero_premium_eligible" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300">
                            <i class="fal fa-check-circle"></i> @lang('admin::insurance.household.fpl_zero_premium_badge')
                        </span>

                        <button
                            type="button"
                            class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                            @click="openFplModal"
                        >
                            <i class="fal fa-calculator"></i>
                            @lang('admin::insurance.household.fpl_edit_btn')
                        </button>
                    </div>
                </div>

                <!-- FPL Metrics Grid -->
                <div v-if="fplCalc" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                    <!-- FPL % & CSR Tier -->
                    <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800">
                        <div class="text-[11px] font-medium text-gray-500 uppercase">@lang('admin::insurance.household.fpl_badge') / CSR</div>
                        <div class="mt-1 flex items-baseline gap-1.5 flex-wrap">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">@{{ fplCalc.fpl_percentage }}%</span>
                            <span class="text-xs font-semibold px-1.5 py-0.5 rounded border" :class="getCsrBadgeClass(fplCalc.fpl_category)">
                                @{{ fplCalc.csr_tier }}
                            </span>
                        </div>
                        <div class="text-[11px] text-gray-400 mt-0.5 truncate" :title="fplCalc.csr_description">@{{ fplCalc.csr_description }}</div>
                    </div>

                    <!-- Annual Income & HH Size -->
                    <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800">
                        <div class="text-[11px] font-medium text-gray-500 uppercase">@lang('admin::insurance.household.fpl_projected_income')</div>
                        <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                            $@{{ Number(fplCalc.projected_annual_income).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}
                        </div>
                        <div class="text-[11px] text-gray-400 mt-0.5">
                            @{{ fplCalc.household_size }} @{{ fplCalc.household_size === 1 ? labels.memberSingle : labels.memberPlural }} (Umbral: $@{{ Number(fplCalc.fpl_guideline_threshold).toLocaleString('en-US') }})
                        </div>
                    </div>

                    <!-- Estimated APTC Monthly Subsidy -->
                    <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800">
                        <div class="text-[11px] font-medium text-gray-500 uppercase">@lang('admin::insurance.household.fpl_aptc_monthly')</div>
                        <div class="mt-1 text-lg font-bold text-emerald-600 dark:text-emerald-400">
                            $@{{ Number(fplCalc.estimated_monthly_aptc).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            <span class="text-xs font-normal text-gray-500">/ mes</span>
                        </div>
                        <div class="text-[11px] text-gray-400 mt-0.5">
                            Aporte máx: $@{{ Number(fplCalc.max_monthly_contribution).toFixed(2) }}/mes (@{{ fplCalc.applicable_percentage }}%)
                        </div>
                    </div>

                    <!-- Net Benchmark Premium -->
                    <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800">
                        <div class="text-[11px] font-medium text-gray-500 uppercase">@lang('admin::insurance.household.fpl_net_premium')</div>
                        <div class="mt-1 text-lg font-bold" :class="fplCalc.estimated_net_premium <= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white'">
                            $@{{ Number(fplCalc.estimated_net_premium).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            <span class="text-xs font-normal text-gray-500">/ mes</span>
                        </div>
                        <div class="text-[11px] text-gray-400 mt-0.5">
                            Benchmark Silver: $@{{ Number(fplCalc.estimated_benchmark_premium).toFixed(2) }}/mes
                        </div>
                    </div>
                </div>
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
                                <div class="inline-flex items-center gap-1.5">
                                    <span>@{{ member.ssn_itin || '--' }}</span>
                                    <button
                                        v-if="member.can_reveal_ssn && !member.is_revealed"
                                        type="button"
                                        @click="revealSsn(member)"
                                        class="text-gray-400 hover:text-blue-600 transition p-0.5 rounded cursor-pointer"
                                        title="@lang('admin::insurance.household.reveal_pii')"
                                    >
                                        <i class="fal fa-eye text-xs"></i>
                                    </button>
                                    <span v-if="member.is_revealed" class="text-[10px] text-emerald-600 font-sans font-bold">@lang('admin::insurance.household.revealed_badge')</span>
                                </div>
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

            <!-- FPL Calculator & Income Modal -->
            <x-admin::modal ref="fplModal" position="center">
                <x-slot:header>
                    <h3 class="text-base font-semibold dark:text-white flex items-center gap-2">
                        <i class="fal fa-calculator text-brandColor"></i>
                        @lang('admin::insurance.household.fpl_modal_title')
                    </h3>
                </x-slot>

                <x-slot:content>
                    <form @submit.prevent="saveFpl" class="flex flex-col gap-4">
                        <!-- Projected Annual Income (MAGI) -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-700 dark:text-gray-300 required">
                                @lang('admin::insurance.household.fpl_projected_income') ($ USD)
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-400 font-bold">$</span>
                                <input
                                    type="number"
                                    step="100"
                                    min="0"
                                    v-model.number="fplForm.projected_annual_income"
                                    @input="onFplInputChange"
                                    required
                                    placeholder="35000"
                                    class="w-full pl-7 rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm font-semibold dark:bg-gray-950 dark:text-white"
                                />
                            </div>
                            <span class="text-[11px] text-gray-400">
                                @lang('admin::insurance.household.fpl_benchmark_note')
                            </span>
                        </div>

                        <!-- Household Size & Quick Sync -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300 required">
                                    @lang('admin::insurance.household.fpl_household_size')
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <input
                                        type="number"
                                        min="1"
                                        max="20"
                                        v-model.number="fplForm.household_size"
                                        @input="onFplInputChange"
                                        required
                                        class="w-full rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                                    />
                                    <button
                                        type="button"
                                        class="secondary-button text-[11px] py-1 px-2 whitespace-nowrap"
                                        @click="syncHouseholdCount"
                                        title="Auto-completar según titular + dependientes registrados"
                                    >
                                        Auto (@{{ members.length + 1 }})
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    @lang('admin::insurance.household.fpl_tax_year')
                                </label>
                                <select
                                    v-model.number="fplForm.tax_year"
                                    @change="onFplInputChange"
                                    class="rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                                >
                                    <option :value="2026">2026 (Guías HHS Vigentes)</option>
                                    <option :value="2025">2025</option>
                                    <option :value="2024">2024</option>
                                </select>
                            </div>
                        </div>

                        <!-- State Code & Notes -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    @lang('admin::insurance.household.fpl_state')
                                </label>
                                <input
                                    type="text"
                                    maxlength="2"
                                    v-model="fplForm.state_code"
                                    @input="onFplInputChange"
                                    placeholder="FL"
                                    class="uppercase rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                                />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Notas Adicionales
                                </label>
                                <input
                                    type="text"
                                    v-model="fplForm.notes"
                                    placeholder="Ej. W2 + 1099 Proyectado"
                                    class="rounded border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-sm dark:bg-gray-950 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Live Preview Box -->
                        <div v-if="fplPreview" class="p-3 rounded-lg border border-dashed border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Resultado Proyectado:</span>
                                <span class="text-xs font-bold px-2 py-0.5 rounded border" :class="getCsrBadgeClass(fplPreview.fpl_category)">
                                    @{{ fplPreview.fpl_percentage }}% FPL • @{{ fplPreview.csr_tier }}
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-xs pt-1">
                                <div>
                                    <span class="text-gray-400 block text-[10px]">Umbral HHS:</span>
                                    <span class="font-bold text-gray-800 dark:text-gray-200">$@{{ Number(fplPreview.fpl_guideline_threshold).toLocaleString('en-US') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px]">Subsidio APTC:</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">$@{{ Number(fplPreview.estimated_monthly_aptc).toFixed(2) }}/mes</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px]">Prima Neta Estimada:</span>
                                    <span class="font-bold" :class="fplPreview.estimated_net_premium <= 0 ? 'text-emerald-600 font-extrabold' : 'text-gray-800 dark:text-gray-200'">
                                        $@{{ Number(fplPreview.estimated_net_premium).toFixed(2) }}/mes
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>
                </x-slot>

                <x-slot:footer>
                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="secondary-button text-xs"
                            @click="$refs.fplModal.close()"
                        >
                            @lang('admin::insurance.household.cancel')
                        </button>
                        <button
                            type="button"
                            class="primary-button text-xs"
                            :disabled="isSavingFpl"
                            @click="saveFpl"
                        >
                            @{{ isSavingFpl ? labels.saving : labels.saveBtn }}
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
                    fplCalc: null,
                    fplPreview: null,
                    isSavingFpl: false,
                    fplDebounceTimer: null,
                    fplForm: {
                        projected_annual_income: 0,
                        household_size: 1,
                        tax_year: 2026,
                        state_code: 'FL',
                        notes: '',
                    },
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
                        fplSavedSuccess: @json(trans('admin::insurance.household.fpl_saved_success')),
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
                this.fetchFpl();
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

                revealSsn(member) {
                    this.$axios.get(`/admin/leads/${this.leadId}/household-members/${member.id}/reveal-pii`)
                        .then(response => {
                            if (response.data.success) {
                                member.ssn_itin = response.data.full_ssn;
                                member.is_revealed = true;
                            }
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || 'Error al consultar SSN/PII.'
                            });
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
                            this.fetchFpl();
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
                            this.fetchFpl();
                        })
                        .catch(error => {
                            const msg = error.response?.data?.message || this.labels.errorDelete;
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: msg
                            });
                        });
                },

                fetchFpl() {
                    this.$axios.get(`/admin/leads/${this.leadId}/household-members/fpl-eligibility`)
                        .then(response => {
                            if (response.data.success) {
                                this.fplCalc = response.data.calculation;
                                if (response.data.tax_household) {
                                    this.fplForm.projected_annual_income = response.data.tax_household.projected_annual_income;
                                    this.fplForm.household_size = response.data.tax_household.household_size;
                                    this.fplForm.tax_year = response.data.tax_household.tax_year;
                                    this.fplForm.state_code = response.data.tax_household.state_code;
                                    this.fplForm.notes = response.data.tax_household.notes || '';
                                } else if (this.fplCalc) {
                                    this.fplForm.projected_annual_income = this.fplCalc.projected_annual_income;
                                    this.fplForm.household_size = this.fplCalc.household_size;
                                    this.fplForm.tax_year = this.fplCalc.tax_year;
                                    this.fplForm.state_code = this.fplCalc.state_code;
                                }
                            }
                        })
                        .catch(error => {
                            console.error(error);
                        });
                },

                openFplModal() {
                    if (! this.fplForm.household_size || this.fplForm.household_size <= 1) {
                        this.fplForm.household_size = Math.max(1, this.members.length + 1);
                    }
                    this.previewFpl();
                    this.$refs.fplModal.open();
                },

                syncHouseholdCount() {
                    this.fplForm.household_size = Math.max(1, this.members.length + 1);
                    this.previewFpl();
                },

                onFplInputChange() {
                    clearTimeout(this.fplDebounceTimer);
                    this.fplDebounceTimer = setTimeout(() => {
                        this.previewFpl();
                    }, 300);
                },

                previewFpl() {
                    if (! this.fplForm.household_size) return;
                    this.$axios.post(`/admin/leads/${this.leadId}/household-members/preview-fpl`, this.fplForm)
                        .then(response => {
                            if (response.data.success) {
                                this.fplPreview = response.data.calculation;
                            }
                        })
                        .catch(error => console.error(error));
                },

                saveFpl() {
                    this.isSavingFpl = true;
                    this.$axios.post(`/admin/leads/${this.leadId}/household-members/fpl-eligibility`, this.fplForm)
                        .then(response => {
                            this.isSavingFpl = false;
                            this.$refs.fplModal.close();
                            this.fplCalc = response.data.calculation;
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message || this.labels.fplSavedSuccess
                            });
                        })
                        .catch(error => {
                            this.isSavingFpl = false;
                            const msg = error.response?.data?.message || 'Error al guardar elegibilidad FPL';
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: msg
                            });
                        });
                },

                getCsrBadgeClass(category) {
                    switch (category) {
                        case 'silver_94':
                            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-300';
                        case 'silver_87':
                            return 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-300';
                        case 'silver_73':
                            return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border-indigo-300';
                        case 'medicaid_gap':
                            return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-300';
                        default:
                            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border-gray-300';
                    }
                },
            },
        });
    </script>
@endpushOnce
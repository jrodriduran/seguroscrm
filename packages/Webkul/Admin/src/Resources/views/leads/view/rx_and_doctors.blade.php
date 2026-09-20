<v-lead-rx-network :lead-id="{{ $lead->id }}"></v-lead-rx-network>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-rx-network-template">
        <div class="flex flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800 gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">💊</span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            @lang('admin::insurance.rx_and_doctors.title')
                            <span v-if="metrics" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                @{{ metrics.total_drugs }} @lang('admin::insurance.rx_and_doctors.kpi_total_drugs') | @{{ metrics.total_doctors }} @lang('admin::insurance.rx_and_doctors.doc_table_title')
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500">@lang('admin::insurance.rx_and_doctors.subtitle')</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                        @click="fetchData"
                        :disabled="isLoading"
                    >
                        <span class="icon-refresh text-sm" :class="{'animate-spin': isLoading}"></span>
                        @lang('admin::insurance.rx_and_doctors.btn_refresh')
                    </button>

                    <a
                        :href="'{{ route('admin.insurance.leads.rx_network.pdf', $lead->id) }}'"
                        target="_blank"
                        class="secondary-button text-xs py-1.5 px-3 flex items-center gap-1.5 text-blue-600 hover:text-blue-700"
                        v-if="medications.length > 0 || doctors.length > 0"
                    >
                        <span>📥</span>
                        @lang('admin::insurance.rx_and_doctors.btn_download_pdf')
                    </a>

                    <button
                        type="button"
                        class="primary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                        @click="openMedModal"
                    >
                        <span>➕</span>
                        @lang('admin::insurance.rx_and_doctors.btn_add_med')
                    </button>

                    <button
                        type="button"
                        class="primary-button text-xs py-1.5 px-3 flex items-center gap-1.5"
                        @click="openDocModal"
                    >
                        <span>🩺</span>
                        @lang('admin::insurance.rx_and_doctors.btn_add_doc')
                    </button>
                </div>
            </div>

            <!-- Metrics Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3" v-if="metrics">
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="text-xs text-gray-500">@lang('admin::insurance.rx_and_doctors.kpi_total_drugs')</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white mt-0.5">
                        @{{ metrics.total_drugs }}
                    </div>
                    <div class="text-xs text-amber-600 mt-1" v-if="metrics.specialty_drugs_count > 0">
                        ⚠️ @{{ metrics.specialty_drugs_count }} @lang('admin::insurance.rx_and_doctors.kpi_specialty_drugs')
                    </div>
                    <div class="text-xs text-gray-400 mt-1" v-else>@lang('admin::insurance.rx_and_doctors.kpi_standard_drugs')</div>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="text-xs text-gray-500">@lang('admin::insurance.rx_and_doctors.kpi_copay_monthly')</div>
                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400 mt-0.5">
                        $@{{ Number(metrics.total_copay_30d).toFixed(2) }} / @lang('admin::insurance.policies.period_month')
                    </div>
                    <div class="text-xs text-emerald-600 mt-1">
                        @lang('admin::insurance.rx_and_doctors.kpi_copay_mail') $@{{ Number(metrics.total_copay_90d_mail).toFixed(2) }}
                    </div>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="text-xs text-gray-500">@lang('admin::insurance.rx_and_doctors.kpi_pcp')</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white mt-0.5 truncate">
                        @{{ metrics.has_pcp ? metrics.pcp_name : '@lang('admin::insurance.ai_snapshot.pcp_pending')' }}
                    </div>
                    <div class="text-xs text-emerald-600 mt-1" v-if="metrics.has_pcp">
                        ✅ @lang('admin::insurance.rx_and_doctors.kpi_pcp_assigned')
                    </div>
                    <div class="text-xs text-amber-600 mt-1" v-else>
                        ⚠️ @lang('admin::insurance.rx_and_doctors.kpi_pcp_required')
                    </div>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="text-xs text-gray-500">@lang('admin::insurance.rx_and_doctors.kpi_restrictions')</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white mt-0.5">
                        @{{ metrics.prior_auth_count }} @lang('admin::insurance.rx_and_doctors.with_prior_auth')
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        @lang('admin::insurance.rx_and_doctors.pa_step_therapy')
                    </div>
                </div>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="isLoading" class="space-y-3 py-4">
                <div class="h-10 bg-gray-100 dark:bg-gray-800 animate-pulse rounded"></div>
                <div class="h-20 bg-gray-100 dark:bg-gray-800 animate-pulse rounded"></div>
            </div>

            <div v-else class="space-y-6">
                <!-- 1. Prescription Medications List -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                            <span>💊</span> @lang('admin::insurance.rx_and_doctors.med_table_title')
                        </h4>
                        <span class="text-xs text-gray-500">@{{ medications.length }} @lang('admin::insurance.rx_and_doctors.registered')</span>
                    </div>

                    <div v-if="medications.length === 0" class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-dashed border-gray-300 dark:border-gray-700 text-center text-xs text-gray-500">
                        @lang('admin::insurance.rx_and_doctors.med_table_empty')
                    </div>

                    <div v-else class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg">
                        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-400 font-semibold border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="p-2.5">@lang('admin::insurance.rx_and_doctors.col_drug_name')</th>
                                    <th class="p-2.5">@lang('admin::insurance.rx_and_doctors.col_dosage')</th>
                                    <th class="p-2.5">@lang('admin::insurance.rx_and_doctors.col_tier')</th>
                                    <th class="p-2.5">@lang('admin::insurance.rx_and_doctors.col_restrictions')</th>
                                    <th class="p-2.5 text-right">@lang('admin::insurance.rx_and_doctors.form_copay_30d')</th>
                                    <th class="p-2.5 text-right">@lang('admin::insurance.rx_and_doctors.form_copay_90d_mail')</th>
                                    <th class="p-2.5 text-center">@lang('admin::insurance.rx_and_doctors.col_action')</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-for="med in medications" :key="med.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                    <td class="p-2.5 font-medium text-gray-900 dark:text-white">
                                        @{{ med.medication_name }}
                                        <div v-if="med.notes" class="text-[10px] text-gray-400 font-normal">@{{ med.notes }}</div>
                                    </td>
                                    <td class="p-2.5">
                                        @{{ med.dosage }} <span class="text-gray-400">• @{{ med.frequency }}</span>
                                    </td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300">
                                            @{{ med.drug_tier }}
                                        </span>
                                    </td>
                                    <td class="p-2.5">
                                        <div class="flex items-center gap-1">
                                            <span v-if="med.requires_prior_authorization" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-amber-100 text-amber-800" title="@lang('admin::insurance.rx_and_doctors.form_pa')">PA</span>
                                            <span v-if="med.requires_step_therapy" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-blue-100 text-blue-800" title="@lang('admin::insurance.rx_and_doctors.form_st')">ST</span>
                                            <span v-if="med.has_quantity_limit" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-rose-100 text-rose-800" title="@lang('admin::insurance.rx_and_doctors.form_ql')">QL</span>
                                            <span v-if="!med.requires_prior_authorization && !med.requires_step_therapy && !med.has_quantity_limit" class="text-[10px] text-emerald-600">@lang('admin::insurance.rx_and_doctors.badge_unrestricted')</span>
                                        </div>
                                    </td>
                                    <td class="p-2.5 text-right font-medium text-gray-900 dark:text-white">
                                        $@{{ Number(med.estimated_copay_30d).toFixed(2) }}
                                    </td>
                                    <td class="p-2.5 text-right font-medium text-emerald-600">
                                        $@{{ Number(med.estimated_copay_90d_mail).toFixed(2) }}
                                    </td>
                                    <td class="p-2.5 text-center">
                                        <button
                                            type="button"
                                            class="text-red-500 hover:text-red-700 text-xs px-2 py-1"
                                            @click="deleteMedication(med.id)"
                                            title="@lang('admin::insurance.rx_and_doctors.delete_med')"
                                        >
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Doctors & Medical Providers List -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                            <span>🩺</span> @lang('admin::insurance.rx_and_doctors.doc_table_title')
                        </h4>
                        <span class="text-xs text-gray-500">@{{ doctors.length }} @lang('admin::insurance.rx_and_doctors.registered')</span>
                    </div>

                    <div v-if="doctors.length === 0" class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-dashed border-gray-300 dark:border-gray-700 text-center text-xs text-gray-500">
                        @lang('admin::insurance.rx_and_doctors.doc_table_empty')
                    </div>

                    <div v-else class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg">
                        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-400 font-semibold border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="p-2.5">@lang('admin::insurance.rx_and_doctors.col_doc_name')</th>
                                    <th class="p-2.5">@lang('admin::insurance.rx_and_doctors.col_clinic')</th>
                                    <th class="p-2.5">@lang('admin::insurance.rx_and_doctors.col_npi')</th>
                                    <th class="p-2.5">@lang('admin::insurance.rx_and_doctors.col_network_status')</th>
                                    <th class="p-2.5 text-center">@lang('admin::insurance.rx_and_doctors.col_action')</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-for="doc in doctors" :key="doc.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                    <td class="p-2.5 font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-1.5">
                                            <span>@{{ doc.doctor_name }}</span>
                                            <span v-if="doc.is_primary_physician" class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-emerald-100 text-emerald-800">
                                                PCP
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-gray-500 font-normal">@{{ doc.specialty }}</div>
                                    </td>
                                    <td class="p-2.5">
                                        @{{ doc.clinic_or_hospital || '@lang('admin::insurance.rx_and_doctors.private_practice')' }}
                                        <div v-if="doc.address_city_state" class="text-[10px] text-gray-400">@{{ doc.address_city_state }}</div>
                                    </td>
                                    <td class="p-2.5">
                                        <div class="font-mono text-[11px]">NPI: @{{ doc.npi_number || 'N/A' }}</div>
                                        <div v-if="doc.phone" class="text-[10px] text-gray-500">@{{ doc.phone }}</div>
                                    </td>
                                    <td class="p-2.5">
                                        <div class="flex flex-wrap gap-1" v-if="doc.carrier_network_status">
                                            <span
                                                v-for="(status, carrier) in doc.carrier_network_status"
                                                :key="carrier"
                                                :class="status.toLowerCase().includes('in') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300'"
                                                class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                                            >
                                                @{{ carrier }}: @{{ status }}
                                            </span>
                                        </div>
                                        <span v-else class="text-gray-400 text-[10px]">@lang('admin::insurance.rx_and_doctors.to_verify')</span>
                                    </td>
                                    <td class="p-2.5 text-center">
                                        <button
                                            type="button"
                                            class="text-red-500 hover:text-red-700 text-xs px-2 py-1"
                                            @click="deleteDoctor(doc.id)"
                                            title="@lang('admin::insurance.rx_and_doctors.delete_doc')"
                                        >
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal: Agregar Medicamento -->
            <div v-if="showMedModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl max-w-md w-full p-5 shadow-2xl border border-gray-200 dark:border-gray-800 space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>💊</span> @lang('admin::insurance.rx_and_doctors.modal_med_title')
                        </h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600 text-xl font-bold" @click="showMedModal = false">&times;</button>
                    </div>

                    <form @submit.prevent="saveMedication" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_drug_name')</label>
                            <input
                                type="text"
                                v-model="medForm.medication_name"
                                required
                                placeholder="Ej. Atorvastatin, Eliquis, Metformin"
                                class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_dosage')</label>
                                <input
                                    type="text"
                                    v-model="medForm.dosage"
                                    placeholder="Ej. 20mg, 500mg"
                                    class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_frequency')</label>
                                <input
                                    type="text"
                                    v-model="medForm.frequency"
                                    placeholder="Ej. 1 / día"
                                    class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_tier')</label>
                                <select v-model="medForm.drug_tier" required class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700">
                                    <option value="Tier 1: Preferred Generic">@lang('admin::insurance.rx_and_doctors.tier_1')</option>
                                    <option value="Tier 2: Generic">@lang('admin::insurance.rx_and_doctors.tier_2')</option>
                                    <option value="Tier 3: Preferred Brand">@lang('admin::insurance.rx_and_doctors.tier_3')</option>
                                    <option value="Tier 4: Non-Preferred">@lang('admin::insurance.rx_and_doctors.tier_4')</option>
                                    <option value="Tier 5: Specialty">@lang('admin::insurance.rx_and_doctors.tier_5')</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_qty_30d')</label>
                                <input
                                    type="number"
                                    v-model="medForm.quantity_per_30_days"
                                    min="1"
                                    class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_copay_30d')</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="medForm.estimated_copay_30d"
                                    placeholder="0.00"
                                    class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_copay_90d_mail')</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="medForm.estimated_copay_90d_mail"
                                    placeholder="0.00"
                                    class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>
                        </div>

                        <div class="space-y-1.5 pt-1">
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                <input type="checkbox" v-model="medForm.requires_prior_authorization" class="rounded text-blue-600">
                                <span>@lang('admin::insurance.rx_and_doctors.form_pa')</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                <input type="checkbox" v-model="medForm.requires_step_therapy" class="rounded text-blue-600">
                                <span>@lang('admin::insurance.rx_and_doctors.form_st')</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                <input type="checkbox" v-model="medForm.has_quantity_limit" class="rounded text-blue-600">
                                <span>@lang('admin::insurance.rx_and_doctors.form_ql')</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-800">
                            <button type="button" class="secondary-button text-xs py-1.5 px-3" @click="showMedModal = false">@lang('admin::insurance.rx_and_doctors.cancel')</button>
                            <button type="submit" class="primary-button text-xs py-1.5 px-3" :disabled="isSaving">@lang('admin::insurance.rx_and_doctors.save_med')</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: Agregar Médico -->
            <div v-if="showDocModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl max-w-md w-full p-5 shadow-2xl border border-gray-200 dark:border-gray-800 space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>🩺</span> @lang('admin::insurance.rx_and_doctors.modal_doc_title')
                        </h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600 text-xl font-bold" @click="showDocModal = false">&times;</button>
                    </div>

                    <form @submit.prevent="saveDoctor" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_doc_name')</label>
                            <input
                                type="text"
                                v-model="docForm.doctor_name"
                                required
                                placeholder="Ej. Dr. Roberto Gómez, MD"
                                class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_specialty')</label>
                                <input
                                    type="text"
                                    v-model="docForm.specialty"
                                    placeholder="Ej. Medicina Primaria, Cardiología"
                                    class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_npi')</label>
                                <input
                                    type="text"
                                    v-model="docForm.npi_number"
                                    maxlength="15"
                                    placeholder="1234567890"
                                    class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_clinic')</label>
                                <input
                                    type="text"
                                    v-model="docForm.clinic_or_hospital"
                                    placeholder="Ej. Baptist Health, Memorial"
                                    class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_city_state')</label>
                                <input
                                    type="text"
                                    v-model="docForm.address_city_state"
                                    placeholder="Miami, FL"
                                    class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('admin::insurance.rx_and_doctors.form_phone')</label>
                            <input
                                type="text"
                                v-model="docForm.phone"
                                placeholder="(305) 555-0199"
                                class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                            />
                        </div>

                        <div class="pt-1">
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                <input type="checkbox" v-model="docForm.is_primary_physician" class="rounded text-blue-600">
                                <span class="font-semibold text-emerald-700">@lang('admin::insurance.rx_and_doctors.form_pcp_designation')</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-800">
                            <button type="button" class="secondary-button text-xs py-1.5 px-3" @click="showDocModal = false">@lang('admin::insurance.rx_and_doctors.cancel')</button>
                            <button type="submit" class="primary-button text-xs py-1.5 px-3" :disabled="isSaving">@lang('admin::insurance.rx_and_doctors.save_doc')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-lead-rx-network', {
            template: '#v-lead-rx-network-template',
            props: ['leadId'],
            data() {
                return {
                    isLoading: false,
                    isSaving: false,
                    medications: [],
                    doctors: [],
                    metrics: null,
                    showMedModal: false,
                    showDocModal: false,
                    labels: {
                        confirmDeleteMed: @json(trans('admin::insurance.rx_and_doctors.confirm_delete_med')),
                        confirmDeleteDoc: @json(trans('admin::insurance.rx_and_doctors.confirm_delete_doc')),
                        errorSaveMed: @json(trans('admin::insurance.rx_and_doctors.error_save_med')),
                        errorDeleteMed: @json(trans('admin::insurance.rx_and_doctors.error_delete_med')),
                        errorSaveDoc: @json(trans('admin::insurance.rx_and_doctors.error_save_doc')),
                        errorDeleteDoc: @json(trans('admin::insurance.rx_and_doctors.error_delete_doc')),
                    },
                    medForm: {
                        medication_name: '',
                        dosage: '',
                        frequency: '',
                        quantity_per_30_days: 30,
                        drug_tier: 'Tier 1: Preferred Generic',
                        requires_prior_authorization: false,
                        requires_step_therapy: false,
                        has_quantity_limit: false,
                        estimated_copay_30d: 0,
                        estimated_copay_90d_mail: 0,
                    },
                    docForm: {
                        doctor_name: '',
                        specialty: 'Primary Care Physician (PCP)',
                        npi_number: '',
                        clinic_or_hospital: '',
                        address_city_state: '',
                        phone: '',
                        is_primary_physician: false,
                    },
                };
            },
            mounted() {
                this.fetchData();
            },
            methods: {
                fetchData() {
                    this.isLoading = true;
                    this.$axios.get(`/admin/insurance/leads/${this.leadId}/rx-network/summary`)
                        .then(response => {
                            if (response.data.success) {
                                this.medications = response.data.medications || [];
                                this.doctors = response.data.doctors || [];
                                this.metrics = response.data.metrics;
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching Rx & Network data:', err);
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                },
                openMedModal() {
                    this.medForm = {
                        medication_name: '',
                        dosage: '',
                        frequency: '',
                        quantity_per_30_days: 30,
                        drug_tier: 'Tier 1: Preferred Generic',
                        requires_prior_authorization: false,
                        requires_step_therapy: false,
                        has_quantity_limit: false,
                        estimated_copay_30d: 0,
                        estimated_copay_90d_mail: 0,
                    };
                    this.showMedModal = true;
                },
                openDocModal() {
                    this.docForm = {
                        doctor_name: '',
                        specialty: 'Primary Care Physician (PCP)',
                        npi_number: '',
                        clinic_or_hospital: '',
                        address_city_state: '',
                        phone: '',
                        is_primary_physician: false,
                    };
                    this.showDocModal = true;
                },
                saveMedication() {
                    this.isSaving = true;
                    this.$axios.post(`/admin/insurance/leads/${this.leadId}/rx-medications`, this.medForm)
                        .then(res => {
                            this.showMedModal = false;
                            this.fetchData();
                        })
                        .catch(err => alert(this.labels.errorSaveMed))
                        .finally(() => this.isSaving = false);
                },
                deleteMedication(id) {
                    if (!confirm(this.labels.confirmDeleteMed)) return;
                    this.$axios.delete(`/admin/insurance/leads/${this.leadId}/rx-medications/${id}`)
                        .then(() => this.fetchData())
                        .catch(err => alert(this.labels.errorDeleteMed));
                },
                saveDoctor() {
                    this.isSaving = true;
                    this.$axios.post(`/admin/insurance/leads/${this.leadId}/doctor-networks`, this.docForm)
                        .then(res => {
                            this.showDocModal = false;
                            this.fetchData();
                        })
                        .catch(err => alert(this.labels.errorSaveDoc))
                        .finally(() => this.isSaving = false);
                },
                deleteDoctor(id) {
                    if (!confirm(this.labels.confirmDeleteDoc)) return;
                    this.$axios.delete(`/admin/insurance/leads/${this.leadId}/doctor-networks/${id}`)
                        .then(() => this.fetchData())
                        .catch(err => alert(this.labels.errorDeleteDoc));
                },
            }
        });
    </script>
@endpushOnce

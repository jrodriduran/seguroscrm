<x-admin::layouts>
    <x-slot:title>
        @lang('admin::insurance.hierarchy.title_page')
    </x-slot>

    <v-agency-hierarchy></v-agency-hierarchy>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-agency-hierarchy-template">
            <div class="content-wrapper p-6 space-y-6">
                
                <!-- Header -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🏛️</span>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                                @lang('admin::insurance.hierarchy.title_page')
                            </h1>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            @lang('admin::insurance.hierarchy.subtitle')
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <a
                            :href="'{{ route('admin.hierarchy.export') }}?period=' + currentPeriod"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold transition-colors"
                        >
                            <span>📥</span>
                            <span>@lang('admin::insurance.hierarchy.btn_export')</span>
                        </a>

                        <button
                            type="button"
                            @click="openConfigModal()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm"
                        >
                            <span>➕</span>
                            <span>@lang('admin::insurance.hierarchy.btn_add_subagency')</span>
                        </button>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.hierarchy.kpi_overrides') (@{{ currentPeriod }})</span>
                        <div class="text-3xl font-extrabold text-sky-600 dark:text-sky-400 mt-2">
                            $@{{ formatMoney(totalOverrides) }}
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            @lang('admin::insurance.hierarchy.col_monthly_overrides')
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.hierarchy.kpi_subagencies')</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">
                            @{{ totalSubAgencies }}
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            @lang('admin::insurance.hierarchy.tier_ga')
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">@lang('admin::insurance.hierarchy.kpi_producers')</span>
                        <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">
                            @{{ totalProducers }}
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            @lang('admin::insurance.hierarchy.tier_producer')
                        </p>
                    </div>
                </div>

                <!-- Main View Tabs -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
                    
                    <div class="flex items-center gap-4 border-b border-slate-200 dark:border-slate-800 pb-3 mb-5">
                        <button
                            type="button"
                            @click="activeTab = 'tree'"
                            :class="activeTab === 'tree' ? 'text-sky-600 font-extrabold border-b-2 border-sky-600 pb-3 -mb-3.5' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                            class="text-xs transition-colors flex items-center gap-1.5"
                        >
                            <span>🌳</span> @lang('admin::insurance.hierarchy.org_tree')
                        </button>

                        <button
                            type="button"
                            @click="activeTab = 'ledger'; loadLedger()"
                            :class="activeTab === 'ledger' ? 'text-sky-600 font-extrabold border-b-2 border-sky-600 pb-3 -mb-3.5' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                            class="text-xs transition-colors flex items-center gap-1.5"
                        >
                            <span>📑</span> @lang('admin::insurance.hierarchy.btn_export_statement')
                        </button>
                    </div>

                    <!-- TAB 1: TREE VIEW -->
                    <div v-if="activeTab === 'tree'" class="space-y-4">
                        <div v-if="!tree.length" class="text-center py-8 text-slate-500 text-xs">
                            No hay estructura de agentes configurada todavía. Use el botón "Configurar Upline" para comenzar.
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="node in tree" :key="node.user_id" class="border border-slate-200 dark:border-slate-800 rounded-xl p-4 bg-slate-50/50 dark:bg-slate-800/40">
                                
                                <!-- Root Node -->
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-sky-600 text-white flex items-center justify-center font-bold text-sm">
                                            @{{ node.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-extrabold text-sm text-slate-900 dark:text-white">@{{ node.name }}</h4>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-sky-100 text-sky-800 border border-sky-200">
                                                    @{{ node.tier_label }}
                                                </span>
                                                <span v-if="node.sub_agency_name" class="text-[11px] font-bold text-slate-500">
                                                    (@{{ node.sub_agency_name }})
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-0.5">
                                                NPN: @{{ node.npn_number || 'N/A' }} | Email: @{{ node.email }} | Override: $@{{ formatMoney(node.override_pmpm) }}/PMPM
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Production Stats -->
                                    <div class="flex items-center gap-4 text-xs">
                                        <div class="text-right">
                                            <div class="text-[10px] text-slate-400 font-bold uppercase">Producción Personal</div>
                                            <div class="font-extrabold text-slate-800 dark:text-slate-200">
                                                @{{ node.personal_policies_count }} pólizas (@{{ node.personal_lives_count }} vidas)
                                            </div>
                                        </div>

                                        <div class="text-right border-l border-slate-200 dark:border-slate-700 pl-4">
                                            <div class="text-[10px] text-slate-400 font-bold uppercase">Equipo Downline</div>
                                            <div class="font-extrabold text-emerald-600">
                                                @{{ node.downline_policies_count }} pólizas (@{{ node.downline_lives_count }} vidas)
                                            </div>
                                        </div>

                                        <div class="text-right border-l border-slate-200 dark:border-slate-700 pl-4">
                                            <div class="text-[10px] text-slate-400 font-bold uppercase">Overrides Mes</div>
                                            <div class="font-extrabold text-sky-600 text-sm">
                                                $@{{ formatMoney(node.monthly_overrides_earned) }}
                                            </div>
                                        </div>

                                        <button 
                                            type="button" 
                                            @click="editUserHierarchy(node)" 
                                            class="px-2.5 py-1 text-slate-600 hover:text-slate-900 border border-slate-300 dark:border-slate-700 rounded text-xs font-bold"
                                        >
                                            Editar
                                        </button>
                                    </div>
                                </div>

                                <!-- Child Downlines Level 1 -->
                                <div v-if="node.children && node.children.length" class="mt-3.5 pl-6 border-l-2 border-sky-400/40 space-y-2.5">
                                    <div v-for="child in node.children" :key="child.user_id" class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2.5">
                                            <span class="text-sm">↳</span>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-xs text-slate-900 dark:text-white">@{{ child.name }}</span>
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                                        @{{ child.tier_label }}
                                                    </span>
                                                    <span v-if="child.sub_agency_name" class="text-[10px] text-slate-500">
                                                        (@{{ child.sub_agency_name }})
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-slate-400">
                                                    NPN: @{{ child.npn_number || 'N/A' }} | PMPM Override pagado al upline: $@{{ formatMoney(child.override_pmpm) }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3 text-xs">
                                            <div class="text-right">
                                                <span class="text-slate-500 text-[11px]">Pólizas:</span>
                                                <strong class="text-slate-800 dark:text-slate-200 ml-1">@{{ child.personal_policies_count }}</strong>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-slate-500 text-[11px]">Vidas:</span>
                                                <strong class="text-emerald-600 ml-1">@{{ child.personal_lives_count }}</strong>
                                            </div>

                                            <button 
                                                type="button" 
                                                @click="editUserHierarchy(child)" 
                                                class="px-2 py-0.5 text-[11px] text-slate-500 hover:text-slate-800 font-semibold"
                                            >
                                                Editar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: OVERRIDES LEDGER -->
                    <div v-if="activeTab === 'ledger'">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700">
                                        <th class="p-3">Mes Período</th>
                                        <th class="p-3">Upline Beneficiario (GA / MGA)</th>
                                        <th class="p-3">Nivel Override</th>
                                        <th class="p-3">Agente Productor (Downline)</th>
                                        <th class="p-3">N° Póliza & Aseguradora</th>
                                        <th class="p-3 text-center">Vidas</th>
                                        <th class="p-3 text-right">Tarifa PMPM</th>
                                        <th class="p-3 text-right">Monto Override ($)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-if="!distributions.length">
                                        <td colspan="8" class="p-6 text-center text-slate-500">
                                            No hay registros de sobrecomisiones (overrides) en este período.
                                        </td>
                                    </tr>
                                    <tr v-else v-for="dist in distributions" :key="dist.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                        <td class="p-3 font-semibold text-slate-700 dark:text-slate-300">@{{ dist.period_month }}</td>
                                        <td class="p-3 font-extrabold text-slate-900 dark:text-white">
                                            @{{ dist.beneficiary_user ? dist.beneficiary_user.name : 'N/A' }}
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-50 dark:bg-sky-950 text-sky-700 dark:text-sky-300 border border-sky-200">
                                                @{{ dist.tier_name }}
                                            </span>
                                        </td>
                                        <td class="p-3 font-medium text-slate-700 dark:text-slate-300">
                                            @{{ dist.writing_agent ? dist.writing_agent.name : 'N/A' }}
                                        </td>
                                        <td class="p-3">
                                            <div class="font-bold text-slate-900 dark:text-white">@{{ dist.policy ? dist.policy.policy_number : 'N/A' }}</div>
                                            <div class="text-[10px] text-sky-600">@{{ dist.policy ? dist.policy.carrier_name : '' }}</div>
                                        </td>
                                        <td class="p-3 text-center font-bold">@{{ dist.members_count }}</td>
                                        <td class="p-3 text-right font-semibold text-slate-600 dark:text-slate-300">$@{{ formatMoney(dist.rate_per_member) }}</td>
                                        <td class="p-3 text-right font-extrabold text-emerald-600 dark:text-emerald-400 text-sm">
                                            $@{{ formatMoney(dist.override_amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- CONFIG MODAL -->
                <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">
                            Configuración de Jerarquía & Overrides
                        </h3>
                        <p class="text-xs text-slate-500 mb-4">
                            Establezca el líder upline y la sobrecomisión PMPM devengada por su producción.
                        </p>

                        <div class="space-y-3.5 text-xs">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Agente o Productor *</label>
                                <select 
                                    v-model="form.user_id" 
                                    class="w-full border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                >
                                    <option value="" disabled>Seleccione agente...</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">@{{ u.name }} (@{{ u.email }})</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nivel Jerárquico *</label>
                                    <select 
                                        v-model="form.agency_tier" 
                                        class="w-full border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                    >
                                        <option v-for="(lbl, key) in tierLabels" :key="key" :value="key">@{{ lbl }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Upline Directo (GA / Manager)</label>
                                    <select 
                                        v-model="form.parent_user_id" 
                                        class="w-full border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                    >
                                        <option :value="null">Ninguno (Agencia Máster / Directo)</option>
                                        <option v-for="u in users" v-if="u.id !== form.user_id" :key="u.id" :value="u.id">
                                            @{{ u.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Sub-Agencia / Sucursal</label>
                                    <input 
                                        type="text" 
                                        v-model="form.sub_agency_name" 
                                        placeholder="Ej. Miami Health Team" 
                                        class="w-full border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                    >
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">NPN (National Producer Number)</label>
                                    <input 
                                        type="text" 
                                        v-model="form.npn_number" 
                                        placeholder="Ej. 19845210" 
                                        class="w-full border border-slate-300 dark:border-slate-700 rounded-lg p-2.5 bg-white dark:bg-slate-900 dark:text-white"
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 bg-sky-50 dark:bg-sky-950/30 p-3 rounded-xl border border-sky-200 dark:border-sky-800">
                                <div>
                                    <label class="block font-bold text-sky-900 dark:text-sky-200 mb-1">Override al Upline ($ PMPM)</label>
                                    <input 
                                        type="number" 
                                        step="0.50" 
                                        v-model="form.override_pmpm" 
                                        placeholder="Ej. 3.00" 
                                        class="w-full border border-sky-300 dark:border-sky-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white font-bold"
                                    >
                                    <span class="text-[10px] text-sky-700 dark:text-sky-300">Monto fijo por miembro mensual</span>
                                </div>

                                <div>
                                    <label class="block font-bold text-sky-900 dark:text-sky-200 mb-1">Contrato Split Agente (%)</label>
                                    <input 
                                        type="number" 
                                        step="1" 
                                        v-model="form.contract_level_percentage" 
                                        placeholder="70" 
                                        class="w-full border border-sky-300 dark:border-sky-700 rounded-lg p-2 bg-white dark:bg-slate-900 dark:text-white font-bold"
                                    >
                                    <span class="text-[10px] text-sky-700 dark:text-sky-300">% Comisión base directa</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex gap-2 justify-end">
                            <button 
                                type="button" 
                                @click="showModal = false" 
                                class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs"
                            >
                                Cancelar
                            </button>
                            <button 
                                type="button" 
                                @click="saveHierarchy()" 
                                class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-lg text-xs transition-colors"
                            >
                                Guardar Jerarquía
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </script>

        <script type="module">
            app.component('v-agency-hierarchy', {
                template: '#v-agency-hierarchy-template',

                data() {
                    return {
                        activeTab: 'tree',
                        currentPeriod: '{{ $currentMonth }}',
                        totalOverrides: {{ $totalOverrides }},
                        totalSubAgencies: {{ $totalSubAgencies }},
                        totalProducers: {{ $totalProducers }},
                        tree: @json($tree),
                        users: @json($users),
                        tierLabels: @json($tierLabels),
                        distributions: [],
                        showModal: false,
                        form: {
                            user_id: '',
                            parent_user_id: null,
                            agency_tier: 'producer',
                            sub_agency_name: '',
                            npn_number: '',
                            override_pmpm: 3.00,
                            contract_level_percentage: 70.0,
                        },
                    }
                },

                methods: {
                    formatMoney(val) {
                        return (parseFloat(val) || 0).toFixed(2);
                    },

                    openConfigModal() {
                        this.form = {
                            user_id: '',
                            parent_user_id: null,
                            agency_tier: 'producer',
                            sub_agency_name: '',
                            npn_number: '',
                            override_pmpm: 3.00,
                            contract_level_percentage: 70.0,
                        };
                        this.showModal = true;
                    },

                    editUserHierarchy(node) {
                        this.form = {
                            user_id: node.user_id,
                            parent_user_id: null,
                            agency_tier: node.agency_tier || 'producer',
                            sub_agency_name: node.sub_agency_name || '',
                            npn_number: node.npn_number || '',
                            override_pmpm: node.override_pmpm || 0.0,
                            contract_level_percentage: node.contract_level_percentage || 70.0,
                        };
                        this.showModal = true;
                    },

                    saveHierarchy() {
                        if (! this.form.user_id) {
                            alert('Seleccione un agente.');
                            return;
                        }

                        this.$axios.post("{{ route('admin.hierarchy.save') }}", this.form)
                            .then(res => {
                                if (res.data.success) {
                                    this.showModal = false;
                                    this.$emitter.emit('add-flash', {
                                        type: 'success',
                                        message: res.data.message,
                                    });
                                    setTimeout(() => window.location.reload(), 1000);
                                }
                            })
                            .catch(err => {
                                alert(err?.response?.data?.message || 'Error al guardar la jerarquía');
                            });
                    },

                    loadLedger() {
                        this.$axios.get("{{ route('admin.hierarchy.ledger') }}", {
                            params: { period: this.currentPeriod }
                        })
                        .then(res => {
                            if (res.data.success) {
                                this.distributions = (res.data.distributions && res.data.distributions.data) ? res.data.distributions.data : [];
                            }
                        });
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>

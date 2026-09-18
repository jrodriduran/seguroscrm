{!! view_render_event('admin.leads.view.quotes.before', ['lead' => $lead]) !!}

<v-lead-quotes></v-lead-quotes>

{!! view_render_event('admin.leads.view.quotes.after', ['lead' => $lead]) !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-lead-quotes-template"
    >
        @if (bouncer()->hasPermission('quotes'))
            <div class="p-3">
                
                <div v-if="quotes.length">
                    {!! view_render_event('admin.leads.view.quotes.table.before', ['lead' => $lead]) !!}
                    
                    <!-- Action Bar with Compare Button -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                Opciones Cotizadas (@{{ quotes.length }})
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="quotes.length >= 2"
                                type="button"
                                @click="openCompareModal()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm"
                            >
                                <span>⚖️</span>
                                <span>Comparar Planes Side-by-Side</span>
                            </button>

                            <a
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold"
                                href="{{ route('admin.quotes.create', $lead->id) }}?from=lead"
                            >
                                <i class="icon-add text-sm"></i>
                                @lang('admin::app.leads.view.quotes.add-btn')
                            </a>
                        </div>
                    </div>

                    <x-admin::table>
                        {!! view_render_event('admin.leads.view.quotes.table.table_head.before', ['lead' => $lead]) !!}

                        <x-admin::table.thead>
                            <x-admin::table.thead.tr>
                                <x-admin::table.th class="!px-2 w-1/10">
                                    @lang('admin::app.leads.view.quotes.id')
                                </x-admin::table.th>

                                <x-admin::table.th class="!px-2">
                                    Aseguradora / Plan
                                </x-admin::table.th>

                                <x-admin::table.th class="!px-2">
                                    Nivel
                                </x-admin::table.th>

                                <x-admin::table.th class="!px-2">
                                    Prima Bruta
                                </x-admin::table.th>

                                <x-admin::table.th class="!px-2">
                                    Subsidio APTC
                                </x-admin::table.th>

                                <x-admin::table.th class="!px-2 font-bold text-sky-700">
                                    Pago Cliente
                                </x-admin::table.th>

                                <x-admin::table.th class="!px-2">
                                    Deducible
                                </x-admin::table.th>

                                <x-admin::table.th class="!px-2">
                                    Estado
                                </x-admin::table.th>

                                <x-admin::table.th class="actions"></x-admin::table.th>
                            </x-admin::table.thead.tr>
                        </x-admin::table.thead>

                        {!! view_render_event('admin.leads.view.quotes.table.table_head.after', ['lead' => $lead]) !!}

                        {!! view_render_event('admin.leads.view.quotes.table.table_body.before', ['lead' => $lead]) !!}

                        <x-admin::table.tbody>
                            <x-admin::table.tbody.tr v-for="quote in quotes" class="border-b">
                                <x-admin::table.td class="text-wrap !px-2">#@{{ quote.id }}</x-admin::table.td>

                                <x-admin::table.td class="text-wrap !px-2">
                                    <div class="font-semibold text-slate-900 dark:text-white">
                                        @{{ quote.carrier_name || 'Salud' }} - @{{ quote.plan_name || quote.subject }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        Red: @{{ (quote.network_type || 'HMO').toUpperCase() }}
                                    </div>
                                </x-admin::table.td>

                                <x-admin::table.td class="!px-2">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200">
                                        @{{ quote.metal_tier || 'Silver' }}
                                    </span>
                                </x-admin::table.td>

                                <x-admin::table.td class="!px-2 text-slate-500 line-through">
                                    $@{{ formatMoney(quote.gross_premium || quote.sub_total) }}
                                </x-admin::table.td>

                                <x-admin::table.td class="!px-2 text-emerald-600 font-semibold">
                                    -$@{{ formatMoney(quote.aptc_subsidy || quote.discount_amount) }}
                                </x-admin::table.td>

                                <x-admin::table.td class="!px-2 font-extrabold text-sky-700 dark:text-sky-400 text-sm">
                                    $@{{ formatMoney(quote.net_premium || quote.grand_total) }}/mo
                                </x-admin::table.td>

                                <x-admin::table.td class="!px-2">
                                    $@{{ formatMoney(quote.deductible || 0) }}
                                </x-admin::table.td>

                                <x-admin::table.td class="!px-2">
                                    <span 
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800': quote.quote_status === 'bound',
                                            'bg-blue-100 text-blue-800': quote.quote_status === 'accepted',
                                            'bg-amber-100 text-amber-800': quote.quote_status === 'presented',
                                            'bg-slate-100 text-slate-700': !quote.quote_status || quote.quote_status === 'draft',
                                        }"
                                    >
                                        @{{ quote.quote_status === 'bound' ? 'Emitida' : (quote.quote_status === 'accepted' ? 'Aceptada' : (quote.quote_status || 'Borrador')) }}
                                    </span>
                                </x-admin::table.td>

                                <x-admin::table.td class="!px-2">
                                    {!! view_render_event('admin.leads.view.quotes.table.table_body.dropdown.before', ['lead' => $lead]) !!}

                                    <x-admin::dropdown position="bottom-right">
                                        <x-slot:toggle>
                                            <i class="icon-more cursor-pointer text-2xl"></i>
                                        </x-slot>

                                        <x-slot:menu class="!min-w-40">
                                            @if (bouncer()->hasPermission('quotes.mail'))
                                                <x-admin::dropdown.menu.item>
                                                    <a href="javascript:void(0)" @click="sendMail(quote.id)">
                                                        <div class="flex items-center gap-2">
                                                            <span class="icon-mail text-2xl"></span>
                                                            @lang('admin::app.leads.view.quotes.mail')
                                                        </div>
                                                    </a>
                                                </x-admin::dropdown.menu.item>
                                            @endif

                                            @if (bouncer()->hasPermission('quotes.edit'))
                                                <x-admin::dropdown.menu.item>
                                                    <a :href="'{{ route('admin.quotes.edit') }}/' + quote.id + '?from=lead&lead_id={{ $lead->id }}'">
                                                        <div class="flex items-center gap-2">
                                                            <span class="icon-edit text-2xl"></span>
                                                            @lang('admin::app.leads.view.quotes.edit')
                                                        </div>
                                                    </a>
                                                </x-admin::dropdown.menu.item>
                                            @endif

                                            <x-admin::dropdown.menu.item>
                                                <a :href="'{{ route('admin.quotes.whatsapp', ['id' => 'xxx']) }}'.replace('xxx', quote.id)" target="_blank">
                                                    <div class="flex items-center gap-2 text-emerald-600 font-semibold">
                                                        <span class="text-xl">📱</span>
                                                        WhatsApp Proposal
                                                    </div>
                                                </a>
                                            </x-admin::dropdown.menu.item>

                                            <x-admin::dropdown.menu.item v-if="quote.quote_status !== 'bound'">
                                                <a href="javascript:void(0)" @click="convertToPolicy(quote.id)">
                                                    <div class="flex items-center gap-2 text-blue-600 font-semibold">
                                                        <span class="icon-tick text-2xl"></span>
                                                        Emitir Póliza (Ganar)
                                                    </div>
                                                </a>
                                            </x-admin::dropdown.menu.item>

                                            <x-admin::dropdown.menu.item>
                                                <a :href="'{{ route('admin.quotes.print') }}/' + quote.id" target="_blank">
                                                    <div class="flex items-center gap-2">
                                                        <span class="icon-download text-2xl"></span>
                                                        @lang('admin::app.leads.view.quotes.download')
                                                    </div>
                                                </a>
                                            </x-admin::dropdown.menu.item>

                                            @if (bouncer()->hasPermission('quotes.delete'))
                                                <x-admin::dropdown.menu.item @click="removeQuote(quote)">
                                                    <div class="flex items-center gap-2">
                                                        <span class="icon-delete text-2xl"></span>
                                                        @lang('admin::app.leads.view.quotes.delete')
                                                    </div>
                                                </x-admin::dropdown.menu.item>
                                            @endif
                                        </x-slot>
                                    </x-admin::dropdown>

                                    {!! view_render_event('admin.leads.view.quotes.table.table_body.dropdown.after', ['lead' => $lead]) !!}
                                </x-admin::table.td>
                            </x-admin::table.tbody.tr>
                        </x-admin::table.tbody>

                        {!! view_render_event('admin.leads.view.quotes.table.table_body.after', ['lead' => $lead]) !!}
                    </x-admin::table>
                    
                    {!! view_render_event('admin.leads.view.quotes.table.after', ['lead' => $lead]) !!}
                </div>

                <div v-else>
                    <div class="grid justify-center justify-items-center gap-3.5 py-12">
                        <img
                            class="dark:mix-blend-exclusion dark:invert"
                            src="{{ vite()->asset('images/empty-placeholders/quotes.svg') }}"
                        >

                        <div class="flex flex-col items-center gap-2">
                            <p class="text-xl font-semibold dark:text-white">
                                @lang('admin::app.leads.view.quotes.empty-title')
                            </p>

                            <p class="text-gray-400">
                                @lang('admin::app.leads.view.quotes.empty-info')
                            </p>
                        </div>

                        <a
                            class="secondary-button"
                            href="{{ route('admin.quotes.create', $lead->id) }}?from=lead"
                        >
                            @lang('admin::app.leads.view.quotes.add-btn')
                        </a>
                    </div>
                </div>

                <!-- SIDE-BY-SIDE PLAN COMPARISON MODAL -->
                <div v-if="showCompareModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-5xl w-full p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>⚖️</span> Comparativa Side-by-Side de Planes ACA
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Presentación comparativa para {{ $lead->person?->name ?: $lead->title }}
                                </p>
                            </div>

                            <button type="button" @click="showCompareModal = false" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">
                                &times;
                            </button>
                        </div>

                        <!-- Top Action Bar inside Modal -->
                        <div v-if="activeProposal" class="my-4 p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl flex flex-wrap items-center justify-between gap-2 border border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Enlace del Portal del Cliente:</span>
                                <input 
                                    type="text" 
                                    readonly 
                                    :value="activeProposal.public_url" 
                                    class="text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 w-64 select-all"
                                >
                                <button 
                                    type="button" 
                                    @click="copyPortalUrl(activeProposal.public_url)"
                                    class="px-2.5 py-1 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 text-slate-700 dark:text-slate-200 text-xs font-bold rounded"
                                >
                                    @{{ copied ? '¡Copiado!' : 'Copiar' }}
                                </button>
                            </div>

                            <div class="flex items-center gap-2">
                                <a 
                                    :href="activeProposal.whatsapp_url" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold"
                                >
                                    <span>📱</span> WhatsApp
                                </a>

                                <a 
                                    :href="activeProposal.pdf_url" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-bold"
                                >
                                    <span>📄</span> Descargar PDF
                                </a>
                            </div>
                        </div>

                        <!-- Side-by-Side Comparison Matrix -->
                        <div class="overflow-x-auto mt-4">
                            <table class="w-full border-collapse text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700">
                                        <th class="p-3 w-1/4">Característica</th>
                                        <th v-for="(q, idx) in comparedQuotes" class="p-3 text-center border-l border-slate-200 dark:border-slate-700">
                                            <div class="text-[11px] text-sky-600 uppercase font-bold">@{{ q.carrier_name || 'Aseguradora' }}</div>
                                            <div class="font-extrabold text-sm text-slate-900 dark:text-white mt-0.5">@{{ q.plan_name || q.subject }}</div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <tr class="bg-emerald-50/70 dark:bg-emerald-950/20 font-bold">
                                        <td class="p-3 text-emerald-900 dark:text-emerald-300 text-sm">PAGO CLIENTE / MES</td>
                                        <td v-for="q in comparedQuotes" class="p-3 text-center border-l border-slate-200 dark:border-slate-700 text-emerald-700 dark:text-emerald-400 text-base font-extrabold">
                                            $@{{ formatMoney(q.net_premium || q.grand_total) }} / mes
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-semibold text-slate-600 dark:text-slate-400">Nivel de Metal & Red</td>
                                        <td v-for="q in comparedQuotes" class="p-3 text-center border-l border-slate-200 dark:border-slate-700">
                                            <span class="inline-block px-2 py-0.5 rounded font-bold uppercase bg-slate-100 dark:bg-slate-700">
                                                @{{ (q.metal_tier || 'Silver').toUpperCase() }}
                                            </span>
                                            <span class="ml-1 text-slate-500 font-semibold">@{{ (q.network_type || 'HMO').toUpperCase() }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-semibold text-slate-600 dark:text-slate-400">Subsidio APTC Federal</td>
                                        <td v-for="q in comparedQuotes" class="p-3 text-center border-l border-slate-200 dark:border-slate-700 text-emerald-600 font-semibold">
                                            -$@{{ formatMoney(q.aptc_subsidy || q.discount_amount) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-semibold text-slate-600 dark:text-slate-400">Deducible Anual</td>
                                        <td v-for="q in comparedQuotes" class="p-3 text-center border-l border-slate-200 dark:border-slate-700 font-bold">
                                            $@{{ formatMoney(q.deductible || 0) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-semibold text-slate-600 dark:text-slate-400">Gasto Máximo de Bolsillo (MOOP)</td>
                                        <td v-for="q in comparedQuotes" class="p-3 text-center border-l border-slate-200 dark:border-slate-700">
                                            $@{{ formatMoney(q.out_of_pocket_max || 0) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-semibold text-slate-600 dark:text-slate-400">Médico Primario (PCP)</td>
                                        <td v-for="q in comparedQuotes" class="p-3 text-center border-l border-slate-200 dark:border-slate-700 font-bold text-sky-700 dark:text-sky-400">
                                            $@{{ formatMoney(q.copay_primary_care || 0) }} Copago
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-semibold text-slate-600 dark:text-slate-400">Médico Especialista</td>
                                        <td v-for="q in comparedQuotes" class="p-3 text-center border-l border-slate-200 dark:border-slate-700">
                                            $@{{ formatMoney(q.copay_specialist || 0) }} Copago
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-semibold text-slate-600 dark:text-slate-400">Medicamentos Genéricos</td>
                                        <td v-for="q in comparedQuotes" class="p-3 text-center border-l border-slate-200 dark:border-slate-700">
                                            $@{{ formatMoney(q.copay_generic_drugs || 0) }} Copago
                                        </td>
                                    </tr>
                                    <tr class="bg-slate-50 dark:bg-slate-800/40">
                                        <td class="p-3 font-semibold text-slate-600 dark:text-slate-400">Acción Directa</td>
                                        <td v-for="q in comparedQuotes" class="p-3 text-center border-l border-slate-200 dark:border-slate-700">
                                            <button 
                                                type="button" 
                                                @click="convertToPolicy(q.id)"
                                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold"
                                            >
                                                Emitir Póliza
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="button" @click="showCompareModal = false" class="secondary-button">
                                Cerrar Comparador
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        @endif
    </script>


    <script type="module">
        app.component('v-lead-quotes', {
            template: '#v-lead-quotes-template',

            props: ['data'],

            data: function () {
                return {
                    quotes: @json($lead->quotes()->with(['person', 'user'])->get()),
                    showCompareModal: false,
                    activeProposal: null,
                    comparedQuotes: [],
                    copied: false,
                }
            },

            methods: {
                formatMoney(val) {
                    const num = parseFloat(val) || 0;
                    return num.toFixed(2);
                },

                openCompareModal() {
                    this.$axios.post("{{ route('admin.quotes.proposals.generate', $lead->id) }}")
                        .then(response => {
                            if (response.data.success) {
                                this.activeProposal = response.data.proposal;
                                this.activeProposal.public_url = response.data.public_url;
                                this.activeProposal.pdf_url = response.data.pdf_url;
                                this.activeProposal.whatsapp_url = response.data.whatsapp_url;
                                this.comparedQuotes = response.data.quotes;
                                this.showCompareModal = true;
                            } else {
                                alert(response.data.message || 'Error al generar la comparativa');
                            }
                        })
                        .catch(err => {
                            alert(err?.response?.data?.message || 'Error al conectar con el servidor');
                        });
                },

                copyPortalUrl(url) {
                    navigator.clipboard.writeText(url).then(() => {
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    });
                },

                removeQuote(quote) {
                    this.$emitter.emit('open-confirm-modal', {
                        agree: () => {
                            this.isLoading = true;

                            this.$axios.delete("{{ route('admin.leads.quotes.delete') }}/" + quote.id)
                                .then(response => {
                                    this.isLoading = false;

                                    const index = this.quotes.indexOf(quote);

                                    if (index !== -1) {
                                        this.quotes.splice(index, 1);
                                    }

                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                                })
                                .catch(error => {
                                    this.isLoading = false;

                                    this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                                });
                        }
                    });
                },

                sendMail(quoteId) {
                    this.$axios.post("{{ route('admin.leads.quotes.mail', ['quote_id' => 'replaceId']) }}".replace('replaceId', quoteId))
                        .then((response) => {
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message,
                            });
                        })
                        .catch((error) => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error?.response?.data?.message || 'Unable to send quote email.',
                            });
                        });
                },

                convertToPolicy(quoteId) {
                    if (! confirm('¿Deseas emitir la póliza y marcar este caso de salud como Ganado?')) {
                        return;
                    }

                    this.isLoading = true;

                    this.$axios.post("{{ route('admin.quotes.convert_to_policy', ['id' => 'xxx']) }}".replace('xxx', quoteId))
                        .then(response => {
                            this.isLoading = false;
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data?.message || '¡Póliza emitida con éxito!',
                            });
                            setTimeout(() => window.location.reload(), 1000);
                        })
                        .catch(error => {
                            this.isLoading = false;
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error?.response?.data?.message || 'Error al emitir la póliza.',
                            });
                        });
                }
            },
        });
    </script>
@endPushOnce

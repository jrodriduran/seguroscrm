<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.quotes.create.title')
    </x-slot>

    {!! view_render_event('admin.contacts.quotes.create.form_controls.before') !!}

    <x-admin::form
        :action="route('admin.quotes.store').'?'.http_build_query(array_merge(
            request()->route()->parameters(),
            request()->all()
        ))"
    >
        <div class="flex flex-col gap-4">
            <div class="scroll-reactive-sticky scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs
                        name="quotes.create"
                    />

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.quotes.create.title')
                    </div>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <!-- Save button for person -->
                    <div class="flex items-center gap-x-2.5">
                        {!! view_render_event('admin.contacts.quotes.create.save_button.before') !!}

                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.quotes.create.save-btn')
                        </button>

                        {!! view_render_event('admin.contacts.quotes.create.save_button.after') !!}
                    </div>
                </div>
            </div>

            <v-quote :errors="errors">
                <x-admin::shimmer.quotes />
            </v-quote>
        </div>
    </x-admin::form>

    {!! view_render_event('admin.contacts.quotes.create.form_controls.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-quote-template"
        >
            <div class="box-shadow flex flex-col gap-4 rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="flex w-full gap-2 border-b border-gray-300 dark:border-gray-800">
                    {!! view_render_event('admin.contacts.quotes.create.tabs.before') !!}

                    <template
                        v-for="tab in tabs"
                        :key="tab.id"
                    >
                        <a
                            :href="'#' + tab.id"
                            :class="[
                                'inline-block px-3 py-2.5 border-b-2  text-sm font-medium ',
                                activeTab === tab.id
                                ? 'text-brandColor border-brandColor dark:brandColor dark:brandColor'
                                : 'text-gray-600 dark:text-gray-300  border-transparent hover:text-gray-800 hover:border-gray-400 dark:hover:border-gray-400  dark:hover:text-white'
                            ]"
                            @click="scrollToSection(tab.id)"
                            :text="tab.label"
                        ></a>
                    </template>

                    {!! view_render_event('admin.contacts.quotes.create.tabs.after') !!}
                </div>

                <div class="flex flex-col gap-4 px-4 py-2">
                    {!! view_render_event('admin.contacts.quotes.create.quote_information.before') !!}

                    <!-- Health Insurance Plan & Subsidy Card (ACA / Obamacare) -->
                    <div
                        id="health-plan-info"
                        class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50/60 via-white to-indigo-50/40 p-5 shadow-sm dark:border-blue-900/40 dark:from-gray-900 dark:via-gray-900 dark:to-blue-950/20"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-blue-100 pb-3.5 mb-4 dark:border-gray-800">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-lg text-white shadow-md shadow-blue-500/20">
                                    🩺
                                </span>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                        Plan de Salud & Subsidio ACA (Obamacare)
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Selecciona la compañía médica, nivel de metal y cálculo instantáneo de subsidio APTC
                                    </p>
                                </div>
                            </div>

                            <!-- Net Premium Live Pill -->
                            <div class="flex items-center gap-2.5 rounded-xl border border-blue-200 bg-white px-4 py-2 shadow-sm dark:border-blue-800 dark:bg-gray-800">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pago Neto Cliente:</span>
                                <span class="text-xl font-extrabold text-blue-600 dark:text-blue-400">
                                    $@{{ calculatedNetPremium }}<span class="text-xs font-normal text-gray-500">/mes</span>
                                </span>
                            </div>
                        </div>

                        <!-- Top row: Carrier, Plan, Tier -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Aseguradora (Carrier) <span class="text-red-500">*</span>
                                </label>
                                <select
                                    name="carrier_name"
                                    v-model="carrierName"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    required
                                >
                                    <option value="">Seleccionar Aseguradora...</option>
                                    <option value="Florida Blue">Florida Blue (BCBS)</option>
                                    <option value="Ambetter">Ambetter (Centene)</option>
                                    <option value="Oscar Health">Oscar Health</option>
                                    <option value="Molina Healthcare">Molina Healthcare</option>
                                    <option value="UnitedHealthcare">UnitedHealthcare (UHC)</option>
                                    <option value="Aetna CVS Health">Aetna CVS Health</option>
                                    <option value="Humana">Humana</option>
                                    <option value="Cigna Healthcare">Cigna Healthcare</option>
                                    <option value="Devoted Health">Devoted Health</option>
                                    <option value="Delta Dental">Delta Dental</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Nombre del Plan Médico
                                </label>
                                <input
                                    type="text"
                                    name="plan_name"
                                    v-model="planName"
                                    placeholder="Ej. Clear Silver Standard CSR (Silver 94)"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Nivel de Cobertura (Metal Tier)
                                </label>
                                <select
                                    name="metal_tier"
                                    v-model="metalTier"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                >
                                    <option value="silver">Silver (Plata - CSR Recomendado)</option>
                                    <option value="bronze">Bronze (Bronce)</option>
                                    <option value="gold">Gold (Oro)</option>
                                    <option value="platinum">Platinum (Platino)</option>
                                    <option value="catastrophic">Catastrophic (Catastrófico)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Financial Calculation Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 rounded-xl bg-white/80 dark:bg-gray-900/60 border border-blue-100 dark:border-gray-800">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Prima Mensual Bruta ($)
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="gross_premium"
                                    v-model="grossPremium"
                                    placeholder="450.00"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                                <span class="text-[10px] text-gray-400">Tarifa completa sin subsidio</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-emerald-700 dark:text-emerald-400 mb-1">
                                    Subsidio Federal APTC ($)
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="aptc_subsidy"
                                    v-model="aptcSubsidy"
                                    placeholder="420.00"
                                    class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm font-bold text-emerald-600 focus:border-emerald-500 focus:outline-none dark:border-emerald-700 dark:bg-gray-900 dark:text-emerald-400"
                                />
                                <span class="text-[10px] text-emerald-600/80">Crédito fiscal IRS mensual</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-blue-700 dark:text-blue-400 mb-1">
                                    Pago Mensual Cliente ($)
                                </label>
                                <input
                                    type="hidden"
                                    name="net_premium"
                                    :value="calculatedNetPremium"
                                />
                                <input
                                    type="text"
                                    :value="'$' + calculatedNetPremium + ' / mes'"
                                    readonly
                                    class="w-full rounded-lg border border-blue-300 bg-blue-50/80 px-3 py-2 text-sm font-black text-blue-700 focus:outline-none dark:border-blue-700 dark:bg-gray-800 dark:text-blue-300"
                                />
                                <span class="text-[10px] text-blue-600/80 font-medium">Auto-calculado (Bruta - Subsidio)</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Estado de la Cotización
                                </label>
                                <select
                                    name="quote_status"
                                    v-model="quoteStatus"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                >
                                    <option value="draft">Borrador</option>
                                    <option value="presented">Presentada al Cliente</option>
                                    <option value="accepted">Aceptada por Cliente</option>
                                    <option value="bound">Emitida (Póliza Activa)</option>
                                    <option value="rejected">Rechazada</option>
                                </select>
                            </div>
                        </div>

                        <!-- Medical Copays & Deductible Row -->
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-xs">
                            <div>
                                <label class="block font-semibold text-gray-600 dark:text-gray-300 mb-1">Red (Network)</label>
                                <select
                                    name="network_type"
                                    v-model="networkType"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs focus:border-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                >
                                    <option value="EPO">EPO</option>
                                    <option value="HMO">HMO</option>
                                    <option value="PPO">PPO</option>
                                    <option value="POS">POS</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-600 dark:text-gray-300 mb-1">Deducible ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="deductible"
                                    v-model="deductible"
                                    placeholder="0.00"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-600 dark:text-gray-300 mb-1">Máx. Bolsillo ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="out_of_pocket_max"
                                    v-model="outOfPocketMax"
                                    placeholder="1500.00"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-600 dark:text-gray-300 mb-1">Copago Médico ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="copay_primary_care"
                                    v-model="copayPrimaryCare"
                                    placeholder="0.00"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-600 dark:text-gray-300 mb-1">Copago Especialista ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="copay_specialist"
                                    v-model="copaySpecialist"
                                    placeholder="15.00"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-600 dark:text-gray-300 mb-1">Copago Rx Gen ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="copay_generic_drugs"
                                    v-model="copayGenericDrugs"
                                    placeholder="3.00"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Quote information -->
                    <div
                        id="quote-info"
                        class="flex flex-col gap-4"
                    >
                        <div class="flex flex-col gap-1">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.quotes.create.quote-info')
                            </p>

                            <p class="text-sm text-gray-600 dark:text-white">
                                @lang('admin::app.quotes.create.quote-info-info')
                            </p>
                        </div>

                        {!! view_render_event('admin.contacts.quotes.create.attribute.form_controls.before') !!}

                        <div class="w-1/2 max-md:w-full">
                            <x-admin::attributes
                                :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                    'entity_type' => 'quotes',
                                    ['code', 'IN', ['subject']],
                                ])"

                                :custom-validations="[
                                    'expired_at' => [
                                        'required',
                                        'date_format:yyyy-MM-dd',
                                        'after:' .  \Carbon\Carbon::yesterday()->format('Y-m-d')
                                    ],
                                ]"
                            />

                            <x-admin::attributes
                                :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                        'entity_type' => 'quotes',
                                        ['code', 'IN', ['description']],
                                    ])"
                                :custom-validations="[
                                    'expired_at' => [
                                        'required',
                                        'date_format:yyyy-MM-dd',
                                        'after:' .  \Carbon\Carbon::yesterday()->format('Y-m-d')
                                    ],
                                ]"
                            />

                            <div class="flex gap-4">
                                <x-admin::attributes
                                    :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                        'entity_type' => 'quotes',
                                        ['code', 'IN', ['expired_at', 'user_id']],
                                    ])->sortBy('sort_order')"
                                    :custom-validations="[
                                        'expired_at' => [
                                            'required',
                                            'date_format:yyyy-MM-dd',
                                            'after:' .  \Carbon\Carbon::yesterday()->format('Y-m-d')
                                        ],
                                    ]"
                                    :entity="$quote"
                                />
                            </div>

                            <div class="flex gap-4">
                                <x-admin::attributes
                                    :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                        'entity_type' => 'quotes',
                                        ['code', 'IN', ['person_id']],
                                    ])->sortBy('sort_order')"
                                    :custom-validations="[
                                        'expired_at' => [
                                            'required',
                                            'date_format:yyyy-MM-dd',
                                            'after:' .  \Carbon\Carbon::yesterday()->format('Y-m-d')
                                        ],
                                    ]"
                                    :entity="$quote"
                                />

                                <x-admin::attributes.edit.lookup />

                                <x-admin::form.control-group class="w-full">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.quotes.create.link-to-lead')
                                    </x-admin::form.control-group.label>

                                    <v-lookup-component
                                        :key="leadEntity.id"
                                        :attribute="{'code': 'lead_id', 'name': 'Lead', 'lookup_type': 'leads'}"
                                        :value="leadEntity"
                                        can-add-new="true"
                                        @lookup-added="setLeadEntity"
                                        @lookup-removed="setLeadEntity"
                                    ></v-lookup-component>
                                </x-admin::form.control-group>
                            </div>

                            <!-- Custom Attributes -->
                            <x-admin::attributes
                                :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                    'entity_type' => 'quotes',
                                    'is_user_defined' => 1,
                                ])->sortBy('sort_order')"
                                :custom-validations="[
                                    'expired_at' => [
                                        'required',
                                        'date_format:yyyy-MM-dd',
                                        'after:' .  \Carbon\Carbon::yesterday()->format('Y-m-d')
                                    ],
                                ]"
                                :entity="$quote"
                            />
                        </div>

                        {!! view_render_event('admin.contacts.quotes.create.attribute.form_controls.after') !!}
                    </div>

                    {!! view_render_event('admin.contacts.quotes.create.quote_information.after') !!}

                    {!! view_render_event('admin.contacts.quotes.create.address_information.before') !!}

                    <!-- Address information -->
                    <div
                        id="address-info"
                        class="flex flex-col gap-4"
                        >
                        <div class="flex flex-col gap-1">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.quotes.create.address-info')
                            </p>

                            <p class="text-sm text-gray-600 dark:text-white">@lang('admin::app.quotes.create.address-info-info')</p>
                        </div>

                        <div class="w-1/2 max-md:w-full">
                            {!! view_render_event('admin.contacts.quotes.create.address_information.attributes.before') !!}

                            <!-- Billing Address -->
                            <x-admin::attributes
                                :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                    'entity_type' => 'quotes',
                                    ['code', 'IN', ['billing_address']],
                                ])"
                                :custom-validations="[
                                    'billing_address' => [
                                        'max:100',
                                    ],
                                ]"
                                :entity="$quote"
                            />

                            <!-- Shipping Address Same As Billing Address -->
                            <x-admin::form.control-group class="!mb-4">
                                <x-admin::form.control-group.label class="!text-sm">
                                    @lang('admin::app.quotes.create.same-as-billing')
                                </x-admin::form.control-group.label>

                                <input
                                    type="hidden"
                                    name="shipping_address_same_as_billing"
                                    :value="0"
                                />

                                <x-admin::form.control-group.control
                                    type="switch"
                                    name="shipping_address_same_as_billing"
                                    value="1"
                                    :label="trans('admin::app.quotes.create.same-as-billing')"
                                    :checked="(bool) old('shipping_address_same_as_billing')"
                                    @change="sameAsBilling = $event.target.checked"
                                />
                            </x-admin::form.control-group>

                            <!-- Shipping Address -->
                            <template v-if="! sameAsBilling">
                                <x-admin::attributes
                                    :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                                        'entity_type' => 'quotes',
                                        ['code', 'IN', ['shipping_address']],
                                    ])"
                                    :custom-validations="[
                                        'shipping_address' => [
                                            'max:100',
                                        ],
                                    ]"
                                    :entity="$quote"
                                />
                            </template>

                            {!! view_render_event('admin.contacts.quotes.create.address_information.attributes.after') !!}
                        </div>
                    </div>

                    {!! view_render_event('admin.contacts.quotes.create.address_information.after') !!}

                    {!! view_render_event('admin.contacts.quotes.create.quote_items.before') !!}

                    <!-- Quote Item Information -->
                    <div
                        id="quote-items"
                        class="flex flex-col gap-4"
                        >
                        <div class="flex flex-col gap-1">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.quotes.create.quote-items')
                            </p>

                            <p class="text-sm text-gray-600 dark:text-white">
                                @lang('admin::app.quotes.create.quote-item-info')
                            </p>
                        </div>

                        <!-- Quote Item List Vue Component -->
                        <v-quote-item-list
                            :errors="errors"
                            :lead-entity="leadEntity"
                        ></v-quote-item-list>
                    </div>

                    {!! view_render_event('admin.contacts.quotes.create.quote_items.after') !!}
                </div>
            </div>
        </script>

        <script
            type="text/x-template"
            id="v-quote-item-list-template"
        >
            <div class="flex flex-col gap-4">
                <div class="block w-full">
                    {!! view_render_event('admin.contacts.quotes.create.table.after') !!}

                    <!-- Table -->
                    <x-admin::table>
                        <!-- Table Head -->
                        <x-admin::table.thead>
                            <x-admin::table.thead.tr>
                                <x-admin::table.th >
                                    @lang('admin::app.quotes.create.product-name')
                                </x-admin::table.th>

                                <x-admin::table.th class="text-center">
                                    @lang('admin::app.quotes.create.quantity')
                                </x-admin::table.th>

                                <x-admin::table.th class="text-center">
                                    @lang('admin::app.quotes.create.price')
                                </x-admin::table.th>

                                <x-admin::table.th class="text-center">
                                    @lang('admin::app.quotes.create.amount')
                                </x-admin::table.th>

                                <x-admin::table.th class="text-center">
                                    @lang('admin::app.quotes.create.discount')
                                </x-admin::table.th>

                                <x-admin::table.th class="text-center">
                                    @lang('admin::app.quotes.create.tax')
                                </x-admin::table.th>

                                <x-admin::table.th class="text-center">
                                    @lang('admin::app.quotes.create.total')
                                </x-admin::table.th>

                                <x-admin::table.th
                                    v-if="products.length > 1"
                                    class="!px-2 ltr:text-right rtl:text-left"
                                >
                                    @lang('admin::app.quotes.create.action')
                                </x-admin::table.th>
                            </x-admin::table.thead.tr>
                        </x-admin::table.thead>

                        <!-- Table Body -->
                        <x-admin::table.tbody>
                            <!-- Quote Item Vue component -->
                            <template
                                v-for='(product, index) in products'
                                :key="index"
                            >
                                <v-quote-item
                                    :product="product"
                                    :index="index"
                                    :errors="errors"
                                    @onRemoveProduct="removeProduct($event)"
                                ></v-quote-item>
                            </template>
                        </x-admin::table.tbody>
                    </x-admin::table>

                    {!! view_render_event('admin.contacts.quotes.create.table.before') !!}
                </div>

                <!-- Add New Quote Item -->
                <span
                    class="text-md flex max-w-max cursor-pointer items-center gap-2 text-brandColor"
                    @click="addProduct"
                >
                    @lang('admin::app.quotes.create.add-item')
                </span>

                <div class="flex justify-end">
                    <div class="grid w-[348px] gap-4 rounded-lg bg-gray-100 p-4 text-sm dark:bg-gray-950 dark:text-white">
                        <div class="flex w-full justify-between gap-x-5">
                            @lang('admin::app.quotes.create.sub-total', ['symbol' => core()->currencySymbol(config('app.currency'))])

                            <input
                                type="hidden"
                                name="sub_total"
                                class="control"
                                :value="subTotal"
                                readonly
                            >

                            <p>@{{ subTotal }}</p>
                        </div>

                        <div class="flex w-full justify-between gap-x-5">
                            @lang('admin::app.quotes.create.total-discount', ['symbol' => core()->currencySymbol(config('app.currency'))])

                            <input
                                type="hidden"
                                name="discount_amount"
                                :value="discountAmount"
                            >

                            <p>@{{ discountAmount }}</p>
                        </div>

                        <div class="flex w-full justify-between gap-x-5">
                            @lang('admin::app.quotes.create.total-tax', ['symbol' => core()->currencySymbol(config('app.currency'))])

                            <input
                                type="hidden"
                                name="tax_amount"
                                :value="taxAmount"
                            >

                            <p>@{{ taxAmount }}</p>
                        </div>

                        <div class="flex w-full justify-between gap-x-5">
                            @lang('admin::app.quotes.create.total-adjustment', ['symbol' => core()->currencySymbol(config('app.currency'))])

                            <x-admin::form.control-group.control
                                type="inline"
                                ::name="`adjustment_amount`"
                                ::value="adjustmentAmount"
                                rules="required|decimal:4"
                                ::errors="errors"
                                :label="trans('admin::app.quotes.create.adjustment-amount')"
                                :placeholder="trans('admin::app.quotes.create.adjustment-amount')"
                                @on-change="handleAdjustmentAmountChange"
                            />
                        </div>

                        <div class="flex w-full justify-between gap-x-5">
                            @lang('admin::app.quotes.create.grand-total', ['symbol' => core()->currencySymbol(config('app.currency'))])

                            <input
                                type="hidden"
                                name="grand_total"
                                :value="grandTotal"
                            >

                            <p>@{{ grandTotal }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </script>

        <script
            type="text/x-template"
            id="v-quote-item-template"
            >
            <x-admin::table.thead.tr>
                <!-- Quote Product Name -->
                <x-admin::table.td>
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::lookup
                            ::src="src"
                            ::name="`${inputName}[product_id]`"
                            :preload="true"
                            ::value="{ id: product.product_id, name: product.name }"
                            :placeholder="trans('admin::app.quotes.create.search-products')"
                            @on-selected="(product) => addProduct(product)"
                            rules="required"
                            :label="trans('admin::app.quotes.create.product-name')"
                            ::class="errors[`${inputName}[product_id]`] ? 'border !border-red-600 hover:border-red-600' : ''"
                        />

                        <x-admin::form.control-group.error name="items.item_0.product_id"/>
                        <x-admin::form.control-group.error name="items[item_0][product_id]"/>
                    </x-admin::form.control-group>
                </x-admin::table.td>

                <!-- Quantity -->
                <x-admin::table.td class="!px-2 ltr:text-right rtl:text-left">
                    <x-admin::form.control-group class="!mb-0">

                        <x-admin::form.control-group.control
                            type="inline"
                            ::name="`${inputName}[quantity]`"
                            ::value="product.quantity"
                            rules="required|numeric|min:1"
                            ::errors="errors"
                            :label="trans('admin::app.quotes.create.quantity')"
                            :placeholder="trans('admin::app.quotes.create.quantity')"
                            @on-change="(event) => product.quantity = event.value"
                            position="center"
                        />
                        <x-admin::form.control-group.error name="items.item_0.quantity"/>
                    </x-admin::form.control-group>
                </x-admin::table.td>

                <!-- Price -->
                <x-admin::table.td class="!px-2 ltr:text-right rtl:text-left">
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.control
                            type="inline"
                            ::name="`${inputName}[price]`"
                            ::value="product.price"
                            rules="required|decimal:4"
                            ::errors="errors"
                            :label="trans('admin::app.quotes.create.price')"
                            :placeholder="trans('admin::app.quotes.create.price')"
                            @on-change="(event) => product.price = event.value"
                            position="center"
                            ::value-label="$admin.formatPrice(product.price)"
                        />
                        <x-admin::form.control-group.error name="items.item_0.price"/>
                    </x-admin::form.control-group>
                </x-admin::table.td>

                <!-- Total -->
                <x-admin::table.td class="!px-2 ltr:text-right rtl:text-left">
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.control
                            type="inline"
                            ::name="`${inputName}[total]`"
                            ::value="product.price * product.quantity"
                            rules="required|decimal:4"
                            ::errors="errors"
                            :label="trans('admin::app.quotes.create.total')"
                            :placeholder="trans('admin::app.quotes.create.total')"
                            :allowEdit="false"
                            position="center"
                            ::value-label="$admin.formatPrice(product.price * product.quantity)"
                        />
                        <x-admin::form.control-group.error name="items.item_0.total"/>
                    </x-admin::form.control-group>
                </x-admin::table.td>

                <!-- Discount Amount -->
                <x-admin::table.td class="!px-2 ltr:text-right rtl:text-left">
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.control
                            type="inline"
                            ::name="`${inputName}[discount_amount]`"
                            ::value="product.discount_amount"
                            rules="required|decimal:4"
                            ::errors="errors"
                            :label="trans('admin::app.quotes.create.discount-amount')"
                            :placeholder="trans('admin::app.quotes.create.discount-amount')"
                            @on-change="(event) => product.discount_amount = event.value"
                            position="center"
                            ::value-label="$admin.formatPrice(product.discount_amount)"
                        />
                        <x-admin::form.control-group.error name="items.item_0.discount_amount"/>
                    </x-admin::form.control-group>
                </x-admin::table.td>

                <!-- Tax Amount -->
                <x-admin::table.td class="!px-2 ltr:text-right rtl:text-left">
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.control
                            type="inline"
                            ::name="`${inputName}[tax_amount]`"
                            ::value="product.tax_amount"
                            rules="required|decimal:4"
                            ::errors="errors"
                            :label="trans('admin::app.quotes.create.tax-amount')"
                            :placeholder="trans('admin::app.quotes.create.tax-amount')"
                            @on-change="(event) => product.tax_amount = event.value"
                            position="center"
                            ::value-label="$admin.formatPrice(product.tax_amount)"
                        />
                        <x-admin::form.control-group.error name="items.item_0.tax_amount"/>
                    </x-admin::form.control-group>
                </x-admin::table.td>

                <!-- Total with Discount -->
                <x-admin::table.td class="!px-2 ltr:text-right rtl:text-left">
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.control
                            type="inline"
                            ::name="`${inputName}[final_total]`"
                            ::errors="errors"
                            ::value="parseFloat(product.price * product.quantity) + parseFloat(product.tax_amount) - parseFloat(product.discount_amount)"
                            :allowEdit="false"
                            position="center"
                            ::value-label="$admin.formatPrice(parseFloat(product.price * product.quantity) + parseFloat(product.tax_amount) - parseFloat(product.discount_amount))"
                        />
                        <x-admin::form.control-group.error name="items.item_0.final_total"/>
                    </x-admin::form.control-group>
                </x-admin::table.td>

                <!-- Action -->
                <x-admin::table.td
                    v-if="$parent.products.length > 1"
                    class="!px-2 ltr:text-right rtl:text-left"
                    >
                    <x-admin::form.control-group class="!mb-0">
                        <i
                            @click="removeProduct"
                            class="icon-delete cursor-pointer text-2xl"
                        ></i>
                    </x-admin::form.control-group>
                </x-admin::table.td>
            </x-admin::table.thead.tr>
        </script>

        <script type="module">
            app.component('v-quote', {
                template: '#v-quote-template',

                props: ['errors'],

                data() {
                    return {
                        activeTab: 'health-plan-info',

                        tabs: [
                            { id: 'health-plan-info', label: "🩺 Plan de Salud & Subsidio" },
                            { id: 'quote-info', label: "@lang('admin::app.quotes.create.quote-info')" },
                            { id: 'address-info', label: "@lang('admin::app.quotes.create.address-info')" },
                            { id: 'quote-items', label: "@lang('admin::app.quotes.create.quote-items')" }
                        ],

                        leadEntity: @json($lookUpEntityData ?? []),

                        sameAsBilling: {{ old('shipping_address_same_as_billing') ? 'true' : 'false' }},

                        carrierName: @json(old('carrier_name', '')),
                        planName: @json(old('plan_name', '')),
                        metalTier: @json(old('metal_tier', 'silver')),
                        networkType: @json(old('network_type', 'EPO')),
                        grossPremium: @json(old('gross_premium', '')),
                        aptcSubsidy: @json(old('aptc_subsidy', '')),
                        quoteStatus: @json(old('quote_status', 'draft')),
                        deductible: @json(old('deductible', '')),
                        outOfPocketMax: @json(old('out_of_pocket_max', '')),
                        copayPrimaryCare: @json(old('copay_primary_care', '')),
                        copaySpecialist: @json(old('copay_specialist', '')),
                        copayGenericDrugs: @json(old('copay_generic_drugs', '')),
                    };
                },

                computed: {
                    calculatedNetPremium() {
                        const gross = parseFloat(this.grossPremium) || 0;
                        const subsidy = parseFloat(this.aptcSubsidy) || 0;
                        return Math.max(0, gross - subsidy).toFixed(2);
                    },
                },

                methods: {
                    /**
                     * Scroll to the section.
                     *
                     * @param {String} tabId
                     *
                     * @returns {void}
                     */
                    scrollToSection(tabId) {
                        const section = document.getElementById(tabId);

                        if (section) {
                            section.scrollIntoView({ behavior: 'smooth' });
                        }
                    },

                    setLeadEntity($event) {
                        this.leadEntity = $event ?? { id: '', name: '' };
                    },
                },
            });

            app.component('v-quote-item-list', {
                template: '#v-quote-item-list-template',

                props: ['data', 'errors', 'leadEntity'],

                data() {
                    return {
                        adjustmentAmount: '0.0000',

                        products: @json($leadProducts ?? []),
                    }
                },

                created() {
                    if(this.products.length <= 0) {
                        this.addProduct();
                    }
                },

                watch: {
                    'leadEntity.id': function(newLeadId, oldLeadId) {
                        if (newLeadId === oldLeadId) {
                            return;
                        }

                        if (! newLeadId) {
                            this.products = [];

                            return;
                        }

                        this.fetchLeadProducts(newLeadId);
                    },
                },

                computed: {
                    /**
                     * Calculate the sub total of the products.
                     *
                     * @returns {Number}
                     */
                    subTotal() {
                        const total = this.products.reduce((carry, product) => {
                            return carry + this.getProductBaseTotal(product);
                        }, 0);

                        return this.formatDecimal(total);
                    },

                    /**
                     * Calculate the total discount amount of the products.
                     *
                     * @returns {Number}
                     */
                    discountAmount() {
                        const total = this.products.reduce((carry, product) => {
                            return carry + this.parseDecimal(product.discount_amount);
                        }, 0);

                        return this.formatDecimal(total);
                    },

                    /**
                     * Calculate the total tax amount of the products.
                     *
                     * @returns {Number}
                     */
                    taxAmount() {
                        const total = this.products.reduce((carry, product) => {
                            return carry + this.parseDecimal(product.tax_amount);
                        }, 0);

                        return this.formatDecimal(total);
                    },

                    /**
                     * Calculate the grand total of the products.
                     *
                     * @returns {Number}
                     */
                    grandTotal() {
                        const itemsTotal = this.products.reduce((carry, product) => {
                            return carry
                                + this.getProductBaseTotal(product)
                                + this.parseDecimal(product.tax_amount)
                                - this.parseDecimal(product.discount_amount);
                        }, 0);

                        return this.formatDecimal(itemsTotal + this.parseDecimal(this.adjustmentAmount));
                    },
                },

                methods: {
                    /**
                     * Parse decimal-like values safely.
                     *
                     * @param {Number|String|null} value
                     *
                     * @returns {Number}
                     */
                    parseDecimal(value) {
                        const parsedValue = Number.parseFloat(value);

                        return Number.isFinite(parsedValue) ? parsedValue : 0;
                    },

                    /**
                     * Format numeric values as fixed decimals.
                     *
                     * @param {Number|String|null} value
                     *
                     * @returns {String}
                     */
                    formatDecimal(value) {
                        return this.parseDecimal(value).toFixed(4);
                    },

                    /**
                     * Calculate product line subtotal.
                     *
                     * @param {Object} product
                     *
                     * @returns {Number}
                     */
                    getProductBaseTotal(product) {
                        return this.parseDecimal(product.price) * this.parseDecimal(product.quantity);
                    },

                    /**
                     * Keep adjustment amount stored as a fixed decimal string.
                     *
                     * @param {Object} event
                     *
                     * @returns {void}
                     */
                    handleAdjustmentAmountChange(event) {
                        this.adjustmentAmount = this.formatDecimal(event.value);
                    },

                    /**
                     * Fetch and replace items with selected lead products.
                     *
                     * @param {Number|String} leadId
                     *
                     * @returns {void}
                     */
                    fetchLeadProducts(leadId) {
                        this.$axios
                            .get("{{ route('admin.quotes.lead_products', '__LEAD_ID__') }}".replace('__LEAD_ID__', leadId))
                            .then((response) => {
                                const leadProducts = response.data?.data ?? [];

                                this.products = leadProducts;

                                this.$emitter.emit('add-flash', {
                                    type: leadProducts.length ? 'success' : 'info',
                                    message: leadProducts.length
                                        ? 'Lead products assigned to quote. See items section.'
                                        : 'No products found for selected lead.',
                                });
                            })
                            .catch((error) => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error?.response?.data?.message || 'Unable to fetch lead products.',
                                });
                            });
                    },

                    /**
                     * Add a new product.
                     *
                     * @returns {void}
                     */
                    addProduct() {
                        this.products.push({
                            id: null,
                            product_id: null,
                            name: '',
                            quantity: 0,
                            total: '0.0000',
                            price: '0.0000',
                            discount_amount: '0.0000',
                            tax_amount: '0.0000',
                        });
                    },

                    /**
                     * Remove the product.
                     *
                     * @param {Object} product
                     */
                    removeProduct(product) {
                        this.$emitter.emit('open-confirm-modal', {
                            agree: () => {
                                if (this.products.length === 1) {
                                    this.products = [{
                                        id: null,
                                        product_id: null,
                                        name: '',
                                        quantity: null,
                                        total: 0,
                                        price: null,
                                        discount_amount: null,
                                        tax_amount: null,
                                    }];
                                } else {
                                    const index = this.products.indexOf(product);

                                    if (index !== -1) {
                                        this.products.splice(index, 1);
                                    }
                                }
                            },
                        });
                    },
                },
            });

            app.component('v-quote-item', {
                template: '#v-quote-item-template',

                props: ['index', 'product', 'errors'],

                data() {
                    return {
                        products: [],
                    }
                },

                computed: {
                    /**
                     * Get the input name.
                     *
                     * @returns {String}
                     */
                    inputName() {
                        if (this.product.id) {
                            return "items[" + this.product.id + "]";
                        }

                        return "items[item_" + this.index + "]";
                    },

                    /**
                     * Get the source URL.
                     *
                     * @returns {String}
                     */
                    src() {
                        return "{{ route('admin.products.search') }}";
                    },
                },

                methods: {
                    /**
                     * Add the product.
                     *
                     * @param {Object} result
                     *
                     * @return {void}
                     */
                    addProduct(result) {
                        this.product.product_id = result.id ?? null;
                        this.product.name = result.name ?? '';
                        this.product.price = result.price ?? 0;
                        this.product.quantity = result.quantity ?? 1;
                        this.product.discount_amount = 0;
                        this.product.tax_amount = 0;
                    },

                    /**
                     * Remove the product.
                     *
                     * @return {void}
                     */
                    removeProduct() {
                        this.$emit('onRemoveProduct', this.product);
                    },
                },
            });        
        </script>
    @endPushOnce

    @pushOnce('styles')
        <style>
            html {
                scroll-behavior: smooth;
            }
        </style>
    @endPushOnce
</x-admin::layouts>

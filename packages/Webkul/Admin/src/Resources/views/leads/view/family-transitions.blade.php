@if (bouncer()->hasPermission('leads.family_transitions'))
    <v-family-transitions
        endpoint="{{ route('admin.leads.family_transitions.index', $lead->id) }}"
        members-url="{{ route('admin.leads.household.index', $lead->id) }}"
        lead-url="{{ url(config('app.admin_path').'/leads/view') }}"
    ></v-family-transitions>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-family-transitions-template">
            <section class="p-4 border-t dark:border-gray-800">
                <h3 class="text-base font-semibold">@{{ labels.title }}</h3>
                <p class="text-sm text-gray-500 my-2">@{{ labels.explanation }}</p>
                <p class="text-sm text-gray-500 my-2">@{{ labels['tax-note'] }}</p>
                <p v-if="error" role="alert" class="text-red-600 my-2">@{{ error }}</p>
                <button type="button" class="secondary-button" @click="refresh" :disabled="busy">@{{ labels.refresh }}</button>

                <form @submit.prevent="plan" class="grid gap-3 my-4">
                    <label>@{{ labels.member }}
                        <select v-model="form.member_id" required class="w-full border rounded p-2 dark:bg-gray-900">
                            <option value="">—</option>
                            <option v-for="member in members" :key="member.id" :value="member.id">@{{ member.name }}</option>
                        </select>
                    </label>
                    <label>@{{ labels.reason }}
                        <select v-model="form.reason" required class="w-full border rounded p-2 dark:bg-gray-900">
                            <option value="">—</option>
                            <option v-for="reason in reasons" :key="reason" :value="reason">@{{ labels[reason] }}</option>
                        </select>
                    </label>
                    <label>@{{ labels['plan-type'] }}
                        <select v-model="form.plan_type" required class="w-full border rounded p-2 dark:bg-gray-900">
                            <option value="">—</option>
                            <option v-for="type in ['marketplace', 'employer', 'other']" :key="type" :value="type">@{{ labels[type] }}</option>
                        </select>
                    </label>
                    <label>@{{ labels['end-date'] }}
                        <input type="date" v-model="form.coverage_end_date" required class="w-full border rounded p-2 dark:bg-gray-900">
                    </label>
                    <label>@{{ labels['review-on'] }}
                        <input type="date" v-model="form.review_on" :max="form.coverage_end_date" required class="w-full border rounded p-2 dark:bg-gray-900">
                    </label>
                    <label>@{{ labels.note }}
                        <textarea v-model="form.note" required maxlength="2000" class="w-full border rounded p-2 dark:bg-gray-900"></textarea>
                    </label>
                    <button type="submit" class="primary-button" :disabled="busy">@{{ labels.plan }}</button>
                </form>

                <p v-if="!items.length" class="text-sm text-gray-500">@{{ labels.empty }}</p>
                <article v-for="item in items" :key="item.id" class="border rounded p-3 my-3 dark:border-gray-800">
                    <strong>@{{ memberName(item.household_member_id) }} · @{{ labels[item.status] }}</strong>
                    <p>@{{ labels[item.reason] }} · @{{ labels[item.plan_type] }} · @{{ labels['end-date'] }}: @{{ item.coverage_end_date }}</p>
                    <p>@{{ labels['review-on'] }}: @{{ item.review_on }}</p>
                    <p class="whitespace-pre-wrap">@{{ item.note }}</p>
                    <p v-if="item.confirmation_reference">@{{ labels.reference }}: @{{ item.confirmation_reference }}</p>
                    <form v-if="item.status === 'planned'" @submit.prevent="resolve(item, 'confirmed')" class="grid gap-2 my-2">
                        <label>@{{ labels.reference }}
                            <input v-model="references[item.id]" required maxlength="255" class="w-full border rounded p-2 dark:bg-gray-900">
                        </label>
                        <p class="text-sm text-gray-500">@{{ labels['confirm-note'] }}</p>
                        <button type="submit" class="secondary-button" :disabled="busy">@{{ labels.confirm }}</button>
                        <button type="button" class="secondary-button" :disabled="busy || !references[item.id]?.trim()" @click="resolve(item, 'cancelled')">@{{ labels.cancel }}</button>
                    </form>
                    <a v-if="item.individual_lead_id" :href="`${leadUrl}/${item.individual_lead_id}`" class="text-brandColor">@{{ labels['open-individual'] }}</a>
                    @if (bouncer()->hasPermission('leads.create'))
                        <button v-else-if="item.status !== 'cancelled' && item.reason !== 'death'" type="button" class="secondary-button" :disabled="busy" @click="individual(item)">@{{ labels.individual }}</button>
                    @endif
                </article>
            </section>
        </script>
        <script type="module">
            app.component('v-family-transitions', {
                template: '#v-family-transitions-template',
                props: ['endpoint', 'membersUrl', 'leadUrl'],
                data() {
                    return {
                        labels: @json(trans('admin::app.family-workflow')),
                        reasons: ['age_limit', 'own_coverage', 'household_change', 'move', 'death', 'other'],
                        members: [], items: [], references: {}, busy: false, error: '',
                        form: { member_id: '', reason: '', plan_type: '', coverage_end_date: '', review_on: '', note: '' },
                    };
                },
                mounted() { this.refresh(); },
                methods: {
                    memberName(id) { return this.members.find(member => member.id === id)?.name || `#${id}`; },
                    async refresh() {
                        this.busy = true;
                        this.error = '';
                        try {
                            const [members, items] = await Promise.all([this.$axios.get(this.membersUrl), this.$axios.get(this.endpoint)]);
                            this.members = members.data.data;
                            this.items = items.data.data;
                        } catch (error) { this.error = error.response?.data?.message || this.labels.error; }
                        finally { this.busy = false; }
                    },
                    async write(method, suffix, data) {
                        this.busy = true;
                        this.error = '';
                        try {
                            await this.$axios[method](this.endpoint + suffix, data);
                            await this.refresh();
                            return true;
                        } catch (error) { this.error = error.response?.data?.message || this.labels.error; return false; }
                        finally { this.busy = false; }
                    },
                    async plan() {
                        if (await this.write('post', '', this.form)) {
                            this.form = { member_id: '', reason: '', plan_type: '', coverage_end_date: '', review_on: '', note: '' };
                        }
                    },
                    resolve(item, status) { return this.write('put', `/${item.id}`, { status, confirmation_reference: this.references[item.id] }); },
                    individual(item) { return this.write('post', `/${item.id}/individual`, {}); },
                },
            });
        </script>
    @endpushOnce
@endif

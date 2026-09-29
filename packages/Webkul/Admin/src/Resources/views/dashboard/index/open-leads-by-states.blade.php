{!! view_render_event('admin.dashboard.index.open_leads_by_states.before') !!}

<!-- Pipeline funnel: open cases per stage (in funnel order) with their health -->
<v-dashboard-open-leads-by-states>
    <x-admin::shimmer.dashboard.index.open-leads-by-states />
</v-dashboard-open-leads-by-states>

{!! view_render_event('admin.dashboard.index.open_leads_by_states.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-open-leads--by-states-template"
    >
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.open-leads-by-states />
        </template>

        <template v-else>
            <div class="nx-funnel">
                <!-- Header -->
                <div class="nx-funnel-head">
                    <div class="min-w-0">
                        <p class="nx-funnel-title">@lang('admin::app.dashboard.index.open-leads-by-states.title')</p>
                        <p class="nx-funnel-sub">@{{ report.pipeline }} · @lang('teamwork::app.funnel.now')</p>
                    </div>

                    <a :href="report.kanban_url" class="nx-funnel-link">@lang('teamwork::app.funnel.open-pipeline') →</a>
                </div>

                <!-- Totals -->
                <div class="nx-funnel-totals">
                    <div>
                        <span class="nx-funnel-big">@{{ report.totals.open }}</span>
                        <span class="nx-funnel-label">@lang('teamwork::app.funnel.open')</span>
                    </div>

                    <a :href="report.center_url" class="nx-funnel-alert" :class="{ 'is-quiet': ! report.totals.overdue }">
                        <span class="nx-funnel-big">@{{ report.totals.overdue }}</span>
                        <span class="nx-funnel-label">@lang('teamwork::app.funnel.late')</span>
                    </a>

                    <div v-if="report.totals.value !== null">
                        <span class="nx-funnel-big">@{{ money(report.totals.value) }}</span>
                        <span class="nx-funnel-label">@lang('teamwork::app.funnel.value')</span>
                    </div>
                </div>

                <!-- Stages -->
                <div class="nx-funnel-stages" v-if="report.stages.length">
                    <a
                        v-for="(stage, index) in report.stages"
                        :key="stage.id"
                        :href="report.kanban_url"
                        class="nx-stage"
                        :style="{ '--stage-c': color(index) }"
                    >
                        <span class="nx-stage-step">@{{ index + 1 }}</span>

                        <span class="nx-stage-body">
                            <span class="nx-stage-top">
                                <span class="nx-stage-name">@{{ stage.name }}</span>

                                <span class="nx-stage-count">
                                    @{{ stage.total }}
                                    <small>@{{ share(stage.total) }}%</small>
                                </span>
                            </span>

                            <span class="nx-stage-track">
                                <span class="nx-stage-bar" :style="{ width: width(stage.total) + '%' }"></span>
                            </span>

                            <span class="nx-stage-meta">
                                <span v-if="stage.overdue" class="nx-chip is-late">@{{ stage.overdue }} @lang('teamwork::app.states.overdue')</span>
                                <span v-if="stage.warning" class="nx-chip is-warn">@{{ stage.warning }} @lang('teamwork::app.states.warning')</span>
                                <span v-if="! stage.overdue && ! stage.warning && stage.total" class="nx-chip is-ok">@lang('teamwork::app.states.ok')</span>
                                <span v-if="stage.oldest" class="nx-stage-oldest">@lang('teamwork::app.funnel.oldest') @{{ stage.oldest }}</span>
                                <span v-if="stage.value !== null && stage.value" class="nx-stage-oldest">@{{ money(stage.value) }}</span>
                            </span>
                        </span>
                    </a>
                </div>

                <div v-else class="nx-funnel-empty">
                    @lang('admin::app.dashboard.index.open-leads-by-states.empty-title')
                </div>

                <!-- Period outcome -->
                <div class="nx-funnel-foot">
                    <span class="nx-chip is-ok">@lang('teamwork::app.funnel.won') @{{ report.totals.won }}</span>
                    <span class="nx-chip is-late">@lang('teamwork::app.funnel.lost') @{{ report.totals.lost }}</span>
                    <span v-if="report.totals.win_rate !== null" class="nx-funnel-rate">
                        @lang('teamwork::app.funnel.win-rate') <strong>@{{ report.totals.win_rate }}%</strong>
                    </span>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-open-leads-by-states', {
            template: '#v-dashboard-open-leads--by-states-template',

            data() {
                return {
                    report: null,
                    isLoading: true,
                    palette: ['#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f59e0b', '#10b981', '#0ea5e9'],
                };
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.dashboard.pipeline_health') }}", { params: Object.assign({}, filters) })
                        .then(response => {
                            this.report = response.data;
                            this.isLoading = false;
                        })
                        .catch(() => {});
                },

                max() {
                    return Math.max(1, ...this.report.stages.map(stage => stage.total));
                },

                width(total) {
                    return total ? Math.max(6, Math.round(total * 100 / this.max())) : 0;
                },

                share(total) {
                    return this.report.totals.open ? Math.round(total * 100 / this.report.totals.open) : 0;
                },

                color(index) {
                    return this.palette[index % this.palette.length];
                },

                money(value) {
                    return this.report.currency + Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
                },
            },
        });
    </script>
@endPushOnce

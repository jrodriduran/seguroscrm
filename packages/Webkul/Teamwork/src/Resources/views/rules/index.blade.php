<x-admin::layouts>
    <x-slot:title>
        @lang('teamwork::app.rules.title')
    </x-slot>

    @php
        $defaultWarning = core()->getConfigData('general.teamwork.rules.lead_warning_hours') ?: 8;
        $defaultOverdue = core()->getConfigData('general.teamwork.rules.lead_overdue_hours') ?: 16;
    @endphp

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.teamwork.rules" />

                <div class="text-xl font-bold dark:text-white">@lang('teamwork::app.rules.title')</div>
            </div>

            <a href="{{ route('admin.configuration.index', 'general/teamwork') }}" class="tw-btn">@lang('teamwork::app.configuration.title')</a>
        </div>

        <div class="tw-card tw-info p-4 text-sm text-gray-700 dark:text-gray-300">
            <p>@lang('teamwork::app.rules.info')</p>
            <p class="mt-2 tw-meta" style="white-space: normal;">@lang('teamwork::app.rules.defaults', ['warning' => $defaultWarning, 'overdue' => $defaultOverdue, 'day' => rtrim(rtrim(number_format($hoursPerDay, 1), '0'), '.')])</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Existing rules --}}
            <section class="tw-card tw-warning lg:col-span-2">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('teamwork::app.rules.title')</span>
                    <span class="tw-count">{{ $rules->count() }}</span>
                </div>

                @if ($rules->count())
                    <div class="tw-row tw-row-head" style="grid-template-columns: minmax(0, 2fr) 120px 120px 100px auto;">
                        <span>@lang('teamwork::app.rules.scope')</span>
                        <span>@lang('teamwork::app.rules.warning')</span>
                        <span>@lang('teamwork::app.rules.overdue')</span>
                        <span></span>
                        <span></span>
                    </div>

                    @foreach ($rules as $rule)
                        <div class="tw-row" style="grid-template-columns: minmax(0, 2fr) 120px 120px 100px auto; {{ $rule->is_active ? '' : 'opacity: 0.55;' }}">
                            <span class="tw-title" style="white-space: normal;">
                                @if ($rule->stage)
                                    {{ $rule->pipeline?->name }} › {{ $rule->stage->name }}
                                @elseif ($rule->pipeline)
                                    @lang('teamwork::app.rules.pipeline-all-stages', ['pipeline' => $rule->pipeline->name])
                                @else
                                    @lang('teamwork::app.rules.all')
                                @endif
                            </span>

                            <span class="tw-warning"><span class="tw-idle">{{ $rule->warning_hours }} h</span></span>
                            <span class="tw-overdue"><span class="tw-idle">{{ $rule->overdue_hours }} h</span></span>

                            <span class="{{ $rule->is_active ? 'tw-ok' : 'tw-warning' }}"><span class="tw-pill">{{ $rule->is_active ? trans('teamwork::app.rules.active') : trans('teamwork::app.rules.inactive') }}</span></span>

                            <div class="tw-actions">
                                <form method="POST" action="{{ route('admin.settings.teamwork.rules.toggle', $rule->id) }}">
                                    @csrf
                                    <button type="submit" class="tw-btn">{{ $rule->is_active ? trans('teamwork::app.rules.pause') : trans('teamwork::app.rules.resume') }}</button>
                                </form>

                                <form method="POST" action="{{ route('admin.settings.teamwork.rules.delete', $rule->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tw-btn tw-overdue">@lang('teamwork::app.rules.delete')</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="tw-empty">@lang('teamwork::app.rules.empty')</p>
                @endif
            </section>

            {{-- Add / update --}}
            <section class="tw-card tw-info">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('teamwork::app.rules.add')</span>
                </div>

                <form method="POST" action="{{ route('admin.settings.teamwork.rules.store') }}" class="flex flex-col gap-3 p-4">
                    @csrf

                    <div>
                        <label class="tw-label" for="tw-rule-scope">@lang('teamwork::app.rules.scope')</label>

                        {{-- One select: "pipeline:ID" or "stage:ID"; split into the two fields on submit. --}}
                        <select id="tw-rule-scope" class="tw-input" onchange="var v = this.value.split(':'); this.form.lead_pipeline_id.value = v[0] === 'pipeline' ? v[1] : ''; this.form.lead_pipeline_stage_id.value = v[0] === 'stage' ? v[1] : '';">
                            <option value="all">@lang('teamwork::app.rules.all')</option>

                            @foreach ($pipelines as $pipeline)
                                <optgroup label="{{ $pipeline->name }}">
                                    <option value="pipeline:{{ $pipeline->id }}">@lang('teamwork::app.rules.pipeline-all-stages', ['pipeline' => $pipeline->name])</option>

                                    @foreach ($pipeline->stages as $stage)
                                        <option value="stage:{{ $stage->id }}">› {{ $stage->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>

                        <input type="hidden" name="lead_pipeline_id" value="">
                        <input type="hidden" name="lead_pipeline_stage_id" value="">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="tw-label" for="tw-rule-warning">@lang('teamwork::app.rules.warning')</label>
                            <input id="tw-rule-warning" type="number" name="warning_hours" min="1" max="2000" required class="tw-input" value="{{ old('warning_hours', $defaultWarning) }}">
                        </div>

                        <div>
                            <label class="tw-label" for="tw-rule-overdue">@lang('teamwork::app.rules.overdue')</label>
                            <input id="tw-rule-overdue" type="number" name="overdue_hours" min="1" max="2000" required class="tw-input" value="{{ old('overdue_hours', $defaultOverdue) }}">
                        </div>
                    </div>

                    @if ($errors->any())
                        <p class="text-xs text-red-600">{{ $errors->first() }}</p>
                    @endif

                    <button type="submit" class="tw-btn tw-btn-primary justify-center">@lang('teamwork::app.rules.save')</button>
                </form>
            </section>
        </div>
    </div>
</x-admin::layouts>

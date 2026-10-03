<x-admin::layouts>
    <x-slot:title>
        @lang('teamwork::app.playbook.title')
    </x-slot>

    @php
        $twGateColors = ['off' => 'tw-info', 'warn' => 'tw-warning', 'block' => 'tw-overdue'];
    @endphp

    <div class="flex flex-col gap-4" v-pre>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="settings.teamwork.playbook" />
                <div class="text-xl font-bold dark:text-white">@lang('teamwork::app.playbook.title')</div>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($pipelines as $pipeline)
                    <a href="{{ route('admin.settings.teamwork.playbook.index', ['pipeline' => $pipeline->id]) }}" class="tw-btn {{ $pipeline->id === $pipelineId ? 'tw-btn-primary' : '' }}">{{ $pipeline->name }}</a>
                @endforeach
            </div>
        </div>

        <div class="tw-card tw-info p-4 text-sm text-gray-700 dark:text-gray-300">
            <p>@lang('teamwork::app.playbook.info')</p>
        </div>

        @foreach ($stages as $stage)
            @php
                $playbook = $playbooks[$stage->id];
                $rows = ($milestones[$stage->id] ?? collect())->values();
                $isClosed = in_array($stage->code, ['won', 'lost'], true);
            @endphp

            <section class="tw-card {{ $twGateColors[$playbook->gate] ?? 'tw-info' }}" id="stage-{{ $stage->id }}">
                <div class="tw-card-head">
                    <span class="tw-card-title">{{ $loop->iteration }}. {{ $stageName($stage) }}</span>
                    <span class="flex items-center gap-2">
                        @if (! $isClosed)
                            <span class="tw-meta">@lang('teamwork::app.playbook.milestones-count', ['count' => $rows->count()])</span>
                        @endif
                        <span class="{{ $twGateColors[$playbook->gate] ?? 'tw-info' }}"><span class="tw-pill">@lang('teamwork::app.playbook.gates.'.$playbook->gate)</span></span>
                    </span>
                </div>

                <form method="POST" action="{{ route('admin.settings.teamwork.playbook.update', $stage->id) }}" class="grid gap-4 p-4 lg:grid-cols-2">
                    @csrf

                    {{-- Milestones and gate --}}
                    <div class="flex flex-col gap-3">
                        @unless ($isClosed)
                            <div>
                                <label class="tw-label">@lang('teamwork::app.playbook.gate')</label>
                                <select name="gate" class="tw-input">
                                    @foreach (\Webkul\Teamwork\Models\StagePlaybook::GATES as $gate)
                                        <option value="{{ $gate }}" @selected($playbook->gate === $gate)>@lang('teamwork::app.playbook.gates.'.$gate) — @lang('teamwork::app.playbook.gates-info.'.$gate)</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <span class="tw-label">@lang('teamwork::app.playbook.milestones')</span>

                                <div class="flex flex-col gap-2" data-tw-list>
                                    @foreach ($rows->push(null) as $index => $milestone)
                                        <div class="tw-milestone-row" data-tw-row>
                                            <input type="hidden" name="milestones[{{ $index }}][id]" value="{{ $milestone?->id }}">
                                            <input type="text" name="milestones[{{ $index }}][name]" maxlength="190" class="tw-input" placeholder="@lang('teamwork::app.playbook.milestone-placeholder')" value="{{ $milestone?->name }}">

                                            <select name="milestones[{{ $index }}][check]" class="tw-input" title="@lang('teamwork::app.playbook.check')">
                                                <option value="">✋ @lang('teamwork::app.playbook.manual')</option>
                                                @foreach ($checks as $check)
                                                    <option value="{{ $check }}" @selected($milestone?->check === $check)>⚡ @lang('teamwork::app.playbook.checks.'.$check)</option>
                                                @endforeach
                                            </select>

                                            <label class="tw-meta flex items-center gap-1 whitespace-nowrap">
                                                <input type="checkbox" name="milestones[{{ $index }}][is_required]" value="1" @checked($milestone ? $milestone->is_required : true)>
                                                @lang('teamwork::app.playbook.required')
                                            </label>

                                            <button type="button" class="tw-btn" data-tw-remove title="@lang('teamwork::app.playbook.remove')">✕</button>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="tw-btn mt-2" data-tw-add>+ @lang('teamwork::app.playbook.add-milestone')</button>
                            </div>
                        @else
                            <input type="hidden" name="gate" value="off">
                            <p class="tw-meta" style="white-space: normal;">@lang('teamwork::app.playbook.closed-stage')</p>
                        @endunless

                        @if ($rule = $overdue[$stage->id] ?? null)
                            <p class="tw-meta" style="white-space: normal;">⏱ @lang('teamwork::app.playbook.overdue-rule', ['warning' => $rule->warning_hours, 'overdue' => $rule->overdue_hours])</p>
                        @elseif (! $isClosed)
                            <a href="{{ route('admin.settings.teamwork.rules.index') }}" class="tw-meta">⏱ @lang('teamwork::app.playbook.no-overdue-rule') →</a>
                        @endif
                    </div>

                    {{-- On arrival --}}
                    <div class="flex flex-col gap-3">
                        <span class="tw-label">@lang('teamwork::app.playbook.on-arrival')</span>

                        <label class="flex items-center gap-2 text-sm dark:text-gray-300">
                            <input type="checkbox" name="entry_task" value="1" @checked($playbook->entry_task)>
                            @lang('teamwork::app.playbook.entry-task')
                        </label>

                        <div class="grid gap-2 md:grid-cols-3">
                            <input type="text" name="entry_task_title" maxlength="180" class="tw-input md:col-span-3" placeholder="@lang('teamwork::app.playbook.entry-task-placeholder')" value="{{ $playbook->entry_task_title }}">

                            <select name="entry_assign" class="tw-input">
                                @foreach (['owner', 'master', 'user'] as $mode)
                                    <option value="{{ $mode }}" @selected($playbook->entry_assign === $mode)>@lang('teamwork::app.playbook.assignees.'.$mode)</option>
                                @endforeach
                            </select>

                            <select name="entry_user_id" class="tw-input">
                                <option value="">—</option>
                                @foreach ($users as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $playbook->entry_user_id === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>

                            <label class="tw-meta flex items-center gap-1">
                                <input type="number" name="entry_task_hours" min="1" max="720" class="tw-input" style="width: 80px;" value="{{ $playbook->entry_task_hours ?: 24 }}">
                                @lang('teamwork::app.playbook.hours')
                            </label>
                        </div>

                        @foreach (['notify_owner', 'notify_master', 'carry_over', 'close_previous'] as $option)
                            <label class="flex items-start gap-2 text-sm dark:text-gray-300">
                                <input type="checkbox" name="{{ $option }}" value="1" class="mt-1" @checked($playbook->{$option})>
                                <span>@lang('teamwork::app.playbook.options.'.$option)</span>
                            </label>
                        @endforeach

                        <button type="submit" class="tw-btn tw-btn-primary self-start">@lang('teamwork::app.playbook.save')</button>
                    </div>
                </form>

                {{-- Client communications (sequences) --}}
                @if (Route::has('admin.settings.communications.stages.store'))
                    <div class="flex flex-col gap-2 border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                        <span class="tw-label">💬 @lang('teamwork::app.playbook.communications')</span>

                        <div class="flex flex-wrap items-center gap-1.5">
                            @forelse ($sequenceRules[$stage->id] ?? [] as $rule)
                                <form method="POST" action="{{ route('admin.settings.communications.stages.delete', $rule->id) }}" class="cm-rule {{ $rule->sequence?->is_active ? '' : 'is-off' }}">
                                    @csrf
                                    @method('DELETE')
                                    <span>{{ $rule->event === 'stage_idle' ? trans('communications::app.stages.after-days', ['days' => $rule->option('days')]) : trans('communications::app.stages.on-enter') }}</span>
                                    <a href="{{ route('admin.communications.sequences.edit', $rule->sequence_id) }}"><strong>{{ $rule->sequence?->name }}</strong></a>
                                    @unless ($rule->sequence?->is_active)
                                        <em>(@lang('communications::app.sequences.is-paused'))</em>
                                    @endunless
                                    <button type="submit" title="@lang('teamwork::app.playbook.remove')">✕</button>
                                </form>
                            @empty
                                <span class="tw-meta">@lang('teamwork::app.playbook.no-communications')</span>
                            @endforelse
                        </div>

                        @if ($sequences->isNotEmpty())
                            <form method="POST" action="{{ route('admin.settings.communications.stages.store') }}" class="flex flex-wrap items-center gap-1.5">
                                @csrf
                                <input type="hidden" name="stage_id" value="{{ $stage->id }}">

                                <select name="moment" class="tw-input" style="width: auto;" onchange="this.form.querySelector('[name=days]').hidden = this.value !== 'stage_idle'">
                                    <option value="stage_entered">@lang('communications::app.stages.on-enter')</option>
                                    <option value="stage_idle">@lang('communications::app.stages.if-stays')</option>
                                </select>

                                <input type="number" name="days" min="1" max="365" value="3" class="tw-input" style="width: 70px;" hidden>

                                <select name="sequence_id" class="tw-input" style="width: auto; max-width: 260px;">
                                    @foreach ($sequences as $sequence)
                                        <option value="{{ $sequence->id }}">{{ $sequence->name }}{{ $sequence->is_active ? '' : ' ('.trans('communications::app.sequences.is-paused').')' }}</option>
                                    @endforeach
                                </select>

                                <button type="submit" class="tw-btn">+ @lang('communications::app.stages.assign')</button>
                            </form>
                        @endif
                    </div>
                @endif
            </section>
        @endforeach
    </div>

    @pushOnce('scripts')
        <script>
            /**
             * Milestone rows: add (clone the last row, emptied) and remove.
             */
            (function () {
                function reindex(list) {
                    list.querySelectorAll('[data-tw-row]').forEach(function (row, index) {
                        row.querySelectorAll('[name]').forEach(function (field) {
                            field.name = field.name.replace(/^milestones\[\d+\]/, 'milestones[' + index + ']');
                        });
                    });
                }

                document.addEventListener('click', function (event) {
                    var add = event.target.closest && event.target.closest('[data-tw-add]');

                    if (add) {
                        var list = add.parentElement.querySelector('[data-tw-list]');
                        var rows = list.querySelectorAll('[data-tw-row]');
                        var clone = rows[rows.length - 1].cloneNode(true);

                        clone.querySelectorAll('input[type=text], input[type=hidden]').forEach(function (f) { f.value = ''; });
                        clone.querySelectorAll('select').forEach(function (f) { f.selectedIndex = 0; });
                        clone.querySelectorAll('input[type=checkbox]').forEach(function (f) { f.checked = true; });
                        list.appendChild(clone);
                        reindex(list);
                        clone.querySelector('input[type=text]').focus();

                        return;
                    }

                    var remove = event.target.closest && event.target.closest('[data-tw-remove]');

                    if (remove) {
                        var row = remove.closest('[data-tw-row]');
                        var parent = row.parentElement;

                        if (parent.querySelectorAll('[data-tw-row]').length > 1) {
                            row.remove();
                        } else {
                            row.querySelectorAll('input[type=text], input[type=hidden]').forEach(function (f) { f.value = ''; });
                        }

                        reindex(parent);
                    }
                });
            })();
        </script>
    @endPushOnce
</x-admin::layouts>

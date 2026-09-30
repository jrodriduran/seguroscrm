<x-admin::layouts>
    <x-slot:title>
        {{ $sequence->exists ? $sequence->name : trans('communications::app.sequences.new') }}
    </x-slot>

    @php
        $cmSteps = old('steps') ? collect(old('steps'))->map(fn ($s) => (object) ($s + ['config' => $s])) : ($sequence->exists ? $sequence->steps : collect([(object) ['type' => 'preferred', 'delay_days' => 0, 'send_hour' => null, 'template_id' => null, 'condition' => 'always', 'config' => []]]));
        $cmEventTriggers = $sequence->exists ? $sequence->triggers->whereNotIn('event', \Webkul\Communications\Models\Trigger::STAGE_EVENTS) : collect();
        $cmStageTriggers = $sequence->exists ? $sequence->triggers->whereIn('event', \Webkul\Communications\Models\Trigger::STAGE_EVENTS) : collect();
        $cmStepTypes = [...\Webkul\Communications\Models\SequenceStep::MESSAGE_TYPES, ...\Webkul\Communications\Models\SequenceStep::ACTION_TYPES];
        $cmOpt = fn ($step, $key, $default = null) => data_get($step->config ?? [], $key, $default);
        $cmStatuses = ['active', 'pending', 'grace_period_1', 'grace_period_2_3', 'cancelled', 'renewed'];
        $cmStatusColors = ['active' => 'tw-ok', 'paused' => 'tw-warning', 'completed' => 'tw-info', 'exited' => 'tw-overdue'];
    @endphp

    <form method="POST" action="{{ $sequence->exists ? route('admin.communications.sequences.update', $sequence->id) : route('admin.communications.sequences.store') }}" class="flex flex-col gap-4" v-pre data-cm-sequence>
        @csrf
        @if ($sequence->exists)
            @method('PUT')
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="communications.sequences" />
                <div class="flex items-center gap-2 text-xl font-bold dark:text-white">
                    {{ $sequence->exists ? $sequence->name : trans('communications::app.sequences.new') }}
                    @if ($sequence->exists)
                        <span class="{{ $sequence->is_active ? 'tw-ok' : 'tw-warning' }}"><span class="tw-pill">{{ $sequence->is_active ? trans('communications::app.sequences.is-active') : trans('communications::app.sequences.is-paused') }}</span></span>
                    @endif
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.communications.sequences.index') }}" class="tw-btn">@lang('communications::app.templates.back')</a>
                @if ($sequence->exists)
                    <button type="submit" form="cm-toggle-sequence" class="tw-btn">{{ $sequence->is_active ? trans('communications::app.sequences.pause') : trans('communications::app.sequences.activate') }}</button>
                @endif
                <button type="submit" class="tw-btn tw-btn-primary">@lang('communications::app.sequences.save')</button>
            </div>
        </div>

        @if ($errors->any())
            <div class="tw-card tw-overdue p-3 text-sm">{{ $errors->first() }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- General --}}
            <section class="tw-card tw-info p-4 lg:col-span-2">
                <label class="tw-label">@lang('communications::app.sequences.name')</label>
                <input type="text" name="name" required maxlength="150" class="tw-input" value="{{ old('name', $sequence->name) }}">

                <label class="tw-label mt-3">@lang('communications::app.sequences.description')</label>
                <textarea name="description" rows="2" maxlength="1000" class="tw-input">{{ old('description', $sequence->description) }}</textarea>

                <div class="mt-3 flex flex-col gap-2 text-sm dark:text-gray-300">
                    @foreach (['pause_on_reply', 'exit_on_stage_change', 'allow_reentry'] as $option)
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="{{ $option }}" value="1" class="mt-1" @checked(old($option, $sequence->{$option}))>
                            <span><strong>@lang('communications::app.sequences.options.'.$option)</strong> <span class="tw-meta" style="white-space: normal;">— @lang('communications::app.sequences.options.'.$option.'-info')</span></span>
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- Triggers --}}
            <section class="tw-card tw-ok p-4">
                <span class="tw-label">@lang('communications::app.sequences.when')</span>

                @foreach ($cmStageTriggers as $trigger)
                    <div class="tw-meta" style="white-space: normal;">🧭 @include('communications::sequences.trigger-label', ['trigger' => $trigger, 'stages' => $stages])</div>
                @endforeach

                <a href="{{ route('admin.settings.communications.stages.index') }}" class="tw-meta" style="white-space: normal;">@lang('communications::app.sequences.stage-link') →</a>

                <div class="mt-3 flex flex-col gap-2" data-cm-list="triggers">
                    @foreach ($cmEventTriggers->isEmpty() ? [null] : $cmEventTriggers as $trigger)
                        <div class="flex flex-col gap-1.5 rounded-lg border border-gray-200 p-2 dark:border-gray-800" data-cm-row>
                            <div class="flex gap-1.5">
                                <select name="triggers[{{ $loop->index }}][event]" class="tw-input" data-cm-event>
                                    <option value="">@lang('communications::app.triggers.none')</option>
                                    @foreach (array_diff(\Webkul\Communications\Models\Trigger::EVENTS, \Webkul\Communications\Models\Trigger::STAGE_EVENTS) as $event)
                                        <option value="{{ $event }}" @selected($trigger?->event === $event)>@lang('communications::app.triggers.events.'.$event)</option>
                                    @endforeach
                                </select>
                                <button type="button" class="tw-btn" data-cm-remove title="@lang('communications::app.sequences.remove')">✕</button>
                            </div>

                            <select name="triggers[{{ $loop->index }}][status]" class="tw-input" data-cm-when="policy_status">
                                @foreach ($cmStatuses as $status)
                                    <option value="{{ $status }}" @selected($trigger?->option('status') === $status)>@lang('communications::app.triggers.statuses.'.$status)</option>
                                @endforeach
                            </select>

                            <select name="triggers[{{ $loop->index }}][outcome]" class="tw-input" data-cm-when="call_outcome">
                                @foreach ($outcomes as $outcome)
                                    <option value="{{ $outcome->code }}" @selected($trigger?->option('outcome') === $outcome->code)>{{ $outcome->label }}</option>
                                @endforeach
                            </select>

                            <label class="tw-meta flex items-center gap-2" data-cm-when="renewal_before">
                                <input type="number" name="triggers[{{ $loop->index }}][days]" min="1" max="365" class="tw-input" style="width: 90px;" value="{{ $trigger?->option('days', 60) ?? 60 }}">
                                @lang('communications::app.triggers.days-before')
                            </label>
                        </div>
                    @endforeach
                </div>

                <button type="button" class="tw-btn mt-2 w-full justify-center" data-cm-add="triggers">+ @lang('communications::app.triggers.add')</button>
            </section>
        </div>

        {{-- Steps --}}
        <section class="tw-card tw-info">
            <div class="tw-card-head">
                <span class="tw-card-title">@lang('communications::app.sequences.steps')</span>
                <span class="tw-meta">@lang('communications::app.sequences.steps-info')</span>
            </div>

            <div class="flex flex-col gap-3 p-4" data-cm-list="steps">
                @foreach ($cmSteps as $step)
                    <div class="cm-step" data-cm-row>
                        <div class="cm-step-num" data-cm-num>{{ $loop->iteration }}</div>

                        <div class="grid flex-1 gap-2 md:grid-cols-6">
                            <div class="md:col-span-2">
                                <label class="tw-meta">@lang('communications::app.sequences.step-type')</label>
                                <select name="steps[{{ $loop->index }}][type]" class="tw-input" data-cm-type>
                                    @foreach ($cmStepTypes as $type)
                                        <option value="{{ $type }}" @selected($step->type === $type)>@lang('communications::app.sequences.types.'.$type)</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="tw-meta">@lang('communications::app.sequences.wait-days')</label>
                                <input type="number" name="steps[{{ $loop->index }}][delay_days]" min="0" max="365" class="tw-input" value="{{ $step->delay_days ?? 0 }}">
                            </div>

                            <div>
                                <label class="tw-meta">@lang('communications::app.sequences.at-hour')</label>
                                <select name="steps[{{ $loop->index }}][send_hour]" class="tw-input">
                                    <option value="">@lang('communications::app.sequences.any-hour')</option>
                                    @foreach (range(7, 20) as $hour)
                                        <option value="{{ $hour }}" @selected((string) ($step->send_hour ?? '') === (string) $hour)>{{ sprintf('%02d:00', $hour) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="tw-meta">@lang('communications::app.sequences.condition')</label>
                                <select name="steps[{{ $loop->index }}][condition]" class="tw-input">
                                    @foreach (\Webkul\Communications\Models\SequenceStep::CONDITIONS as $condition)
                                        <option value="{{ $condition }}" @selected(($step->condition ?? 'always') === $condition)>@lang('communications::app.sequences.conditions.'.$condition)</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Message --}}
                            <div class="md:col-span-6" data-cm-for="preferred email whatsapp sms">
                                <label class="tw-meta">@lang('communications::app.sequences.template')</label>
                                <select name="steps[{{ $loop->index }}][template_id]" class="tw-input">
                                    <option value="">—</option>
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}" @selected((string) ($step->template_id ?? '') === (string) $template->id)>{{ $template->name }} ({{ collect($template->channels())->map(fn ($c) => trans('communications::app.chatwoot.channels.'.$c))->implode(', ') }})</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Task / physical / notice --}}
                            <div class="md:col-span-3" data-cm-for="call_task physical notify">
                                <label class="tw-meta">@lang('communications::app.sequences.task-title')</label>
                                <input type="text" name="steps[{{ $loop->index }}][title]" maxlength="180" class="tw-input" value="{{ $cmOpt($step, 'title') }}" placeholder="@lang('communications::app.sequences.task-title-placeholder')">
                            </div>

                            <div data-cm-for="call_task physical notify">
                                <label class="tw-meta">@lang('communications::app.sequences.assign-to')</label>
                                <select name="steps[{{ $loop->index }}][assign_to]" class="tw-input">
                                    @foreach (['owner', 'master', 'user'] as $mode)
                                        <option value="{{ $mode }}" @selected($cmOpt($step, 'assign_to', 'owner') === $mode)>@lang('communications::app.sequences.assignees.'.$mode)</option>
                                    @endforeach
                                </select>
                            </div>

                            <div data-cm-for="call_task physical notify">
                                <label class="tw-meta">@lang('communications::app.sequences.user')</label>
                                <select name="steps[{{ $loop->index }}][user_id]" class="tw-input">
                                    <option value="">—</option>
                                    @foreach ($users as $id => $name)
                                        <option value="{{ $id }}" @selected((string) $cmOpt($step, 'user_id') === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div data-cm-for="call_task physical notify">
                                <label class="tw-meta">@lang('communications::app.sequences.priority')</label>
                                <select name="steps[{{ $loop->index }}][priority]" class="tw-input">
                                    <option value="normal">@lang('communications::app.sequences.priorities.normal')</option>
                                    <option value="urgent" @selected($cmOpt($step, 'priority') === 'urgent')>@lang('communications::app.sequences.priorities.urgent')</option>
                                </select>
                            </div>

                            <div data-cm-for="call_task physical">
                                <label class="tw-meta">@lang('communications::app.sequences.due-hours')</label>
                                <input type="number" name="steps[{{ $loop->index }}][due_hours]" min="1" max="720" class="tw-input" value="{{ $cmOpt($step, 'due_hours', 24) }}">
                            </div>

                            <div data-cm-for="physical">
                                <label class="tw-meta">@lang('communications::app.sequences.item')</label>
                                <select name="steps[{{ $loop->index }}][item]" class="tw-input">
                                    @foreach (['card', 'gift_card', 'kit', 'other'] as $item)
                                        <option value="{{ $item }}" @selected($cmOpt($step, 'item', 'card') === $item)>@lang('communications::app.sequences.items.'.$item)</option>
                                    @endforeach
                                </select>
                            </div>

                            <div data-cm-for="physical">
                                <label class="tw-meta">@lang('communications::app.sequences.cost-label')</label>
                                <input type="number" name="steps[{{ $loop->index }}][cost]" min="0" step="0.01" class="tw-input" value="{{ $cmOpt($step, 'cost') }}">
                            </div>

                            <div class="md:col-span-6" data-cm-for="call_task physical notify">
                                <label class="tw-meta">@lang('communications::app.sequences.note')</label>
                                <input type="text" name="steps[{{ $loop->index }}][note]" maxlength="1000" class="tw-input" value="{{ $cmOpt($step, 'note') }}" placeholder="@lang('communications::app.sequences.note-placeholder')">
                            </div>

                            <div class="md:col-span-3" data-cm-for="tag">
                                <label class="tw-meta">@lang('communications::app.sequences.tag')</label>
                                <select name="steps[{{ $loop->index }}][tag_id]" class="tw-input">
                                    @foreach ($tags as $id => $name)
                                        <option value="{{ $id }}" @selected((string) $cmOpt($step, 'tag_id') === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1">
                            <button type="button" class="tw-btn" data-cm-move="up" title="@lang('communications::app.outcomes.move-up')">↑</button>
                            <button type="button" class="tw-btn" data-cm-move="down" title="@lang('communications::app.outcomes.move-down')">↓</button>
                            <button type="button" class="tw-btn tw-overdue" data-cm-remove title="@lang('communications::app.sequences.remove')">✕</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-4 pb-4">
                <button type="button" class="tw-btn w-full justify-center" data-cm-add="steps">+ @lang('communications::app.sequences.add-step')</button>
            </div>
        </section>

        {{-- Recent enrollments --}}
        @if ($sequence->exists)
            <section class="tw-card tw-ok">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('communications::app.enrollments.recent')</span>
                    <span class="tw-count">{{ $enrollments->count() }}</span>
                </div>

                @forelse ($enrollments as $enrollment)
                    <a href="{{ route('admin.contacts.persons.view', $enrollment->person_id) }}" class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto auto; text-decoration: none;">
                        <span class="tw-title">{{ $people[$enrollment->person_id] ?? '#'.$enrollment->person_id }}</span>
                        <span class="{{ $cmStatusColors[$enrollment->status] ?? 'tw-info' }}"><span class="tw-pill">@lang('communications::app.enrollments.statuses.'.$enrollment->status)</span></span>
                        <span class="tw-meta">
                            @if ($enrollment->next_run_at && in_array($enrollment->status, ['active', 'paused']))
                                @lang('communications::app.enrollments.next', ['step' => $enrollment->next_position, 'when' => core()->formatDate($enrollment->next_run_at, 'd M H:i')])
                            @elseif ($enrollment->exit_reason)
                                @lang('communications::app.enrollments.reasons.'.$enrollment->exit_reason)
                            @endif
                        </span>
                    </a>
                @empty
                    <p class="tw-empty">@lang('communications::app.enrollments.none')</p>
                @endforelse
            </section>

            <button type="submit" form="cm-delete-sequence" class="tw-btn tw-overdue self-start">@lang('communications::app.sequences.delete')</button>
        @endif
    </form>

    @if ($sequence->exists)
        <form id="cm-toggle-sequence" method="POST" action="{{ route('admin.communications.sequences.toggle', $sequence->id) }}">@csrf</form>
        <form id="cm-delete-sequence" method="POST" action="{{ route('admin.communications.sequences.delete', $sequence->id) }}" onsubmit="return confirm(@js(trans('communications::app.sequences.delete-confirm')));">@csrf @method('DELETE')</form>
    @endif

    @pushOnce('scripts')
        <script>
            /**
             * Sequence editor: add, remove and reorder steps and triggers,
             * and show only the fields that apply to each step type.
             */
            (function () {
                function reindex(list) {
                    list.querySelectorAll('[data-cm-row]').forEach(function (row, index) {
                        row.querySelectorAll('[name]').forEach(function (field) {
                            field.name = field.name.replace(/^(steps|triggers)\[\d+\]/, '$1[' + index + ']');
                        });

                        var num = row.querySelector('[data-cm-num]');

                        if (num) num.textContent = index + 1;
                    });
                }

                function refresh(row) {
                    var type = row.querySelector('[data-cm-type]');

                    if (type) {
                        row.querySelectorAll('[data-cm-for]').forEach(function (el) {
                            el.hidden = el.getAttribute('data-cm-for').split(' ').indexOf(type.value) === -1;
                        });
                    }

                    var event = row.querySelector('[data-cm-event]');

                    if (event) {
                        row.querySelectorAll('[data-cm-when]').forEach(function (el) {
                            el.hidden = el.getAttribute('data-cm-when') !== event.value;
                        });
                    }
                }

                document.addEventListener('change', function (e) {
                    if (e.target.matches && e.target.matches('[data-cm-type], [data-cm-event]')) refresh(e.target.closest('[data-cm-row]'));
                });

                document.addEventListener('click', function (e) {
                    var add = e.target.closest && e.target.closest('[data-cm-add]');

                    if (add) {
                        var list = document.querySelector('[data-cm-list="' + add.getAttribute('data-cm-add') + '"]');
                        var rows = list.querySelectorAll('[data-cm-row]');
                        var clone = rows[rows.length - 1].cloneNode(true);

                        clone.querySelectorAll('input[type=text], textarea').forEach(function (f) { f.value = ''; });
                        clone.querySelectorAll('input[type=number]').forEach(function (f) { f.value = f.name.indexOf('delay_days') > -1 ? 15 : f.defaultValue; });
                        list.appendChild(clone);
                        reindex(list);
                        refresh(clone);

                        return;
                    }

                    var remove = e.target.closest && e.target.closest('[data-cm-remove]');

                    if (remove) {
                        var row = remove.closest('[data-cm-row]');
                        var parent = row.parentElement;

                        if (parent.querySelectorAll('[data-cm-row]').length > 1) {
                            row.remove();
                        } else {
                            row.querySelectorAll('select, input').forEach(function (f) { if (f.matches('[data-cm-event]')) f.value = ''; });
                            refresh(row);
                        }

                        reindex(parent);

                        return;
                    }

                    var move = e.target.closest && e.target.closest('[data-cm-move]');

                    if (move) {
                        var current = move.closest('[data-cm-row]');
                        var sibling = move.getAttribute('data-cm-move') === 'up' ? current.previousElementSibling : current.nextElementSibling;

                        if (sibling) {
                            move.getAttribute('data-cm-move') === 'up' ? sibling.before(current) : sibling.after(current);
                            reindex(current.parentElement);
                        }
                    }
                });

                window.addEventListener('load', function () {
                    setTimeout(function () { document.querySelectorAll('[data-cm-row]').forEach(refresh); }, 50);
                });
            })();
        </script>
    @endPushOnce
</x-admin::layouts>

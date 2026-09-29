<x-admin::layouts>
    <x-slot:title>
        @lang('teamwork::app.automations.title')
    </x-slot>

    @php
        $stageLabel = function ($automation) use ($pipelines) {
            if ($id = $automation->condition('stage_id')) {
                foreach ($pipelines as $pipeline) {
                    if ($stage = $pipeline->stages->firstWhere('id', (int) $id)) {
                        return $pipeline->name.' › '.$stage->name;
                    }
                }
            }

            return trans('teamwork::app.automations.stage-codes.'.($automation->condition('stage_code') ?? 'won'));
        };
    @endphp

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.teamwork.automations" />
                <div class="text-xl font-bold dark:text-white">@lang('teamwork::app.automations.title')</div>
            </div>

            <a href="{{ route('admin.settings.teamwork.rules.index') }}" class="tw-btn">@lang('teamwork::app.rules.title')</a>
        </div>

        <div class="tw-card tw-info p-4 text-sm text-gray-700 dark:text-gray-300">
            <p>@lang('teamwork::app.automations.info')</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Automations --}}
            <section class="tw-card tw-info lg:col-span-2">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('teamwork::app.automations.title')</span>
                    <span class="tw-count">{{ $automations->count() }}</span>
                </div>

                @forelse ($automations as $automation)
                    <div class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto; {{ $automation->is_active ? '' : 'opacity: 0.6;' }}">
                        <div class="min-w-0">
                            <span class="tw-title" style="white-space: normal;">{{ $automation->name }}</span>

                            <span class="tw-meta" style="white-space: normal;">
                                <strong>@lang('teamwork::app.automations.when'):</strong>
                                @lang('teamwork::app.automations.triggers.'.$automation->trigger)
                                @if ($automation->trigger === 'stage_entered') — {{ $stageLabel($automation) }} @endif
                                @if ($automation->trigger === 'renewal_upcoming') — @lang('teamwork::app.automations.days-before', ['days' => $automation->condition('days_before', 60)]) @endif
                                · <strong>@lang('teamwork::app.automations.then'):</strong>
                                @lang('teamwork::app.automations.actions.'.$automation->action)
                                @lang('teamwork::app.automations.assignees.'.$automation->assign_to)@if ($automation->assign_to === 'user') {{ $members->firstWhere('id', $automation->assign_user_id)?->name }}@endif
                                @if ($automation->action === 'follow_up') · @lang('teamwork::app.automations.due', ['days' => $automation->due_in_days]) @endif
                                @if ($automation->priority === 'urgent') · <span class="tw-urgent tw-idle">@lang('teamwork::app.follow-up.urgent')</span> @endif
                            </span>

                            @if ($automation->note_template)
                                <span class="tw-meta" style="white-space: normal; font-style: italic;">“{{ $automation->note_template }}”</span>
                            @endif
                        </div>

                        <div class="tw-actions">
                            <span class="tw-meta">@lang('teamwork::app.automations.runs', ['count' => $automation->runs_count])</span>

                            <form method="POST" action="{{ route('admin.settings.teamwork.automations.toggle', $automation->id) }}">
                                @csrf
                                <button type="submit" class="tw-btn {{ $automation->is_active ? 'tw-ok' : '' }}">{{ $automation->is_active ? trans('teamwork::app.rules.active') : trans('teamwork::app.automations.activate') }}</button>
                            </form>

                            <form method="POST" action="{{ route('admin.settings.teamwork.automations.delete', $automation->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tw-btn tw-overdue">@lang('teamwork::app.rules.delete')</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="tw-empty">@lang('teamwork::app.automations.empty')</p>
                @endforelse
            </section>

            {{-- New automation --}}
            <section class="tw-card tw-info">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('teamwork::app.automations.new')</span>
                </div>

                <form method="POST" action="{{ route('admin.settings.teamwork.automations.store') }}" class="flex flex-col gap-3 p-4" data-tw-automation-form>
                    @csrf

                    <div>
                        <label class="tw-label">@lang('teamwork::app.automations.name')</label>
                        <input type="text" name="name" required maxlength="190" class="tw-input" value="{{ old('name') }}">
                    </div>

                    <div>
                        <label class="tw-label">@lang('teamwork::app.automations.when')</label>
                        <select name="trigger" class="tw-input" onchange="this.form.querySelectorAll('[data-for-trigger]').forEach(function (el) { el.style.display = el.dataset.forTrigger === this.value ? '' : 'none'; }, this)">
                            @foreach (\Webkul\Teamwork\Models\Automation::TRIGGERS as $trigger)
                                <option value="{{ $trigger }}">@lang('teamwork::app.automations.triggers.'.$trigger)</option>
                            @endforeach
                        </select>
                    </div>

                    <div data-for-trigger="stage_entered" style="display: none;">
                        <label class="tw-label">@lang('teamwork::app.automations.stage')</label>
                        <select name="stage" class="tw-input">
                            <option value="code:won">@lang('teamwork::app.automations.stage-codes.won')</option>
                            <option value="code:lost">@lang('teamwork::app.automations.stage-codes.lost')</option>
                            <option value="code:new">@lang('teamwork::app.automations.stage-codes.new')</option>

                            @foreach ($pipelines as $pipeline)
                                <optgroup label="{{ $pipeline->name }}">
                                    @foreach ($pipeline->stages as $stage)
                                        <option value="id:{{ $stage->id }}">{{ $stage->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div data-for-trigger="renewal_upcoming" style="display: none;">
                        <label class="tw-label">@lang('teamwork::app.automations.days-before-label')</label>
                        <input type="number" name="days_before" min="1" max="365" value="60" class="tw-input">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="tw-label">@lang('teamwork::app.automations.then')</label>
                            <select name="action" class="tw-input">
                                @foreach (\Webkul\Teamwork\Models\Automation::ACTIONS as $action)
                                    <option value="{{ $action }}">@lang('teamwork::app.automations.actions.'.$action)</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="tw-label">@lang('teamwork::app.automations.who')</label>
                            <select name="assign_to" class="tw-input" onchange="this.form.querySelector('[data-tw-assign-user]').style.display = this.value === 'user' ? '' : 'none'">
                                @foreach (\Webkul\Teamwork\Models\Automation::ASSIGNEES as $assignee)
                                    <option value="{{ $assignee }}">@lang('teamwork::app.automations.assignee-options.'.$assignee)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div data-tw-assign-user style="display: none;">
                        <select name="assign_user_id" class="tw-input">
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="tw-label">@lang('teamwork::app.follow-up.priority')</label>
                            <select name="priority" class="tw-input">
                                <option value="normal">@lang('teamwork::app.follow-up.normal')</option>
                                <option value="urgent">@lang('teamwork::app.follow-up.urgent')</option>
                            </select>
                        </div>

                        <div>
                            <label class="tw-label">@lang('teamwork::app.automations.due-label')</label>
                            <input type="number" name="due_in_days" min="0" max="365" value="1" class="tw-input">
                        </div>
                    </div>

                    <div>
                        <label class="tw-label">@lang('teamwork::app.automations.note')</label>
                        <textarea name="note_template" rows="3" maxlength="2000" class="tw-input" placeholder="@lang('teamwork::app.automations.note-placeholder')"></textarea>
                        <p class="mt-1 tw-meta" style="white-space: normal;">@lang('teamwork::app.automations.variables')</p>
                    </div>

                    @if ($errors->any())
                        <p class="text-xs text-red-600">{{ $errors->first() }}</p>
                    @endif

                    <button type="submit" class="tw-btn tw-btn-primary justify-center">@lang('teamwork::app.automations.save')</button>
                </form>
            </section>
        </div>
    </div>
</x-admin::layouts>

{{-- Stage playbook on the lead page: milestones of the current stage, what blocks the next one, open stage tasks. --}}
@php
    $twPlaybooks = app(\Webkul\Teamwork\Services\StagePlaybooks::class);
    $twLead = \Illuminate\Support\Facades\DB::table('leads')->where('id', $lead->id)->first();
    $twItems = $twPlaybooks->milestones($twLead);
    $twStages = \Illuminate\Support\Facades\DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $twLead->lead_pipeline_id)->orderBy('sort_order')->get(['id', 'name', 'code', 'sort_order']);
    $twCurrent = $twStages->firstWhere('id', $twLead->lead_pipeline_stage_id);
    $twNext = $twCurrent ? $twStages->first(fn ($s) => $s->sort_order > $twCurrent->sort_order && $s->code !== 'lost') : null;
    $twGate = $twNext ? $twPlaybooks->gate($twLead, $twNext->id) : ['mode' => 'off', 'missing' => []];
    $twPlaybook = \Webkul\Teamwork\Models\StagePlaybook::forStage((int) $twLead->lead_pipeline_stage_id);
    $twTasks = $twPlaybooks->openTasks($twLead);
    $twDone = $twItems->where('done', true)->count();
    $twCanTick = bouncer()->hasPermission('leads.edit');
    $twIsMaster = $twPlaybooks->isMaster(auth()->guard('user')->user());
    $twOverrides = \Illuminate\Support\Facades\DB::table('teamwork_stage_overrides')->leftJoin('users', 'users.id', '=', 'teamwork_stage_overrides.user_id')->where('lead_id', $twLead->id)->latest('teamwork_stage_overrides.id')->limit(3)->get(['reason', 'teamwork_stage_overrides.created_at', 'users.name as user_name', 'to_stage_id']);
@endphp

@if ($twItems->isNotEmpty() || $twTasks->isNotEmpty() || $twOverrides->isNotEmpty())
    <div class="tw-card {{ $twGate['mode'] === 'block' ? 'tw-overdue' : ($twGate['missing'] ? 'tw-warning' : 'tw-ok') }} tw-playbook" v-pre>
        <div class="tw-card-head">
            <span class="tw-card-title">🧭 @lang('teamwork::app.playbook.lead-title', ['stage' => $twCurrent ? $twPlaybooks->stageName($twCurrent) : ''])</span>

            @if ($twItems->isNotEmpty())
                <span class="tw-meta">{{ $twDone }}/{{ $twItems->count() }}</span>
            @endif
        </div>

        <div class="flex flex-col gap-3 p-4">
            @if ($twItems->isNotEmpty())
                <div class="tw-progress"><span style="width: {{ round($twDone * 100 / max(1, $twItems->count())) }}%;"></span></div>

                <div class="flex flex-col gap-1.5">
                    @foreach ($twItems as $item)
                        <div class="tw-milestone {{ $item->done ? 'is-done' : '' }}">
                            @if ($item->automatic)
                                <span class="tw-milestone-mark" title="@lang('teamwork::app.playbook.auto-done')">⚡</span>
                            @elseif ($twCanTick)
                                <form method="POST" action="{{ route('admin.teamwork.milestones.toggle', [$twLead->id, $item->milestone->id]) }}">
                                    @csrf
                                    <input type="hidden" name="done" value="{{ $item->done ? 0 : 1 }}">
                                    <button type="submit" class="tw-milestone-check" aria-label="@lang('teamwork::app.playbook.tick')">{{ $item->done ? '✓' : '' }}</button>
                                </form>
                            @else
                                <span class="tw-milestone-mark">{{ $item->done ? '✓' : '○' }}</span>
                            @endif

                            <span class="min-w-0 flex-1">
                                <span class="tw-milestone-name">{{ $item->milestone->name }}</span>
                                <span class="tw-meta" style="white-space: normal;">
                                    @if ($item->milestone->check)
                                        ⚡ @lang('teamwork::app.playbook.checks.'.$item->milestone->check)
                                    @endif
                                    @unless ($item->milestone->is_required)
                                        · @lang('teamwork::app.playbook.recommended')
                                    @endunless
                                    @if ($item->ticked && ! $item->automatic)
                                        · {{ $item->ticked->user_name }}, {{ core()->formatDate($item->ticked->done_at, 'd M H:i') }}
                                    @endif
                                </span>
                            </span>

                            @if ($item->milestone->check && ! $item->done && $twCanTick)
                                <form method="POST" action="{{ route('admin.teamwork.milestones.toggle', [$twLead->id, $item->milestone->id]) }}" title="@lang('teamwork::app.playbook.tick-auto')">
                                    @csrf
                                    <input type="hidden" name="done" value="1">
                                    <button type="submit" class="tw-btn" style="height: 26px;">@lang('teamwork::app.playbook.mark-done')</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($twNext && $twGate['missing'])
                <p class="text-sm {{ $twGate['mode'] === 'block' ? 'text-red-600' : 'text-amber-700' }}" style="white-space: normal;">
                    {{ $twGate['mode'] === 'block' ? '⛔' : '⚠' }}
                    @lang('teamwork::app.playbook.to-advance', ['stage' => $twPlaybooks->stageName($twNext), 'items' => collect($twGate['missing'])->pluck('name')->unique()->implode(', ')])
                </p>

                @if ($twGate['mode'] === 'block' && $twIsMaster)
                    <details>
                        <summary class="tw-btn">@lang('teamwork::app.playbook.override')</summary>

                        <form method="POST" action="{{ route('admin.teamwork.milestones.override', $twLead->id) }}" class="mt-2 flex flex-col gap-2">
                            @csrf
                            <select name="stage_id" class="tw-input">
                                @foreach ($twStages->filter(fn ($s) => $twCurrent && $s->sort_order > $twCurrent->sort_order && $s->code !== 'lost') as $stage)
                                    <option value="{{ $stage->id }}">{{ $twPlaybooks->stageName($stage) }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="reason" minlength="5" maxlength="500" required class="tw-input" placeholder="@lang('teamwork::app.playbook.override-reason')">
                            <button type="submit" class="tw-btn tw-btn-primary self-start">@lang('teamwork::app.playbook.override-confirm')</button>
                        </form>
                    </details>
                @endif
            @elseif ($twNext && $twItems->isNotEmpty())
                <p class="text-sm text-emerald-700" style="white-space: normal;">✅ @lang('teamwork::app.playbook.ready', ['stage' => $twPlaybooks->stageName($twNext)])</p>
            @endif

            @if ($twTasks->isNotEmpty())
                <div class="flex flex-col gap-1">
                    <span class="tw-label" style="margin: 0;">@lang('teamwork::app.playbook.stage-tasks')</span>

                    @foreach ($twTasks as $task)
                        <a href="{{ route('admin.teamwork.follow_ups.show', $task->id) }}" class="tw-meta" style="white-space: normal; text-decoration: none;">
                            {{ $task->due_at && $task->due_at->isPast() ? '🔴' : '🟢' }} {{ $task->title }} · {{ $task->assignee?->name }} · {{ $task->due_at ? core()->formatDate($task->due_at, 'd M H:i') : '' }}
                        </a>
                    @endforeach
                </div>
            @endif

            @foreach ($twOverrides as $override)
                <p class="tw-meta" style="white-space: normal;">🔓 @lang('teamwork::app.playbook.overridden-by', ['name' => $override->user_name, 'when' => core()->formatDate($override->created_at, 'd M'), 'reason' => $override->reason])</p>
            @endforeach
        </div>
    </div>
@endif

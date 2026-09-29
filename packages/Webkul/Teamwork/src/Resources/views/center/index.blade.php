<x-admin::layouts>
    <x-slot:title>
        @lang('teamwork::app.center.title')
    </x-slot>

    @php
        $lateCases = $cases->where('state', 'overdue')->count();
        $attentionCases = $cases->where('state', 'warning')->count();
        $me = auth()->guard('user')->user();
        $canHandoff = $me->role?->permission_type === 'all' || bouncer()->hasPermission('leads.edit');
    @endphp

    <div class="flex flex-col gap-4">
        {{-- Header --}}
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="teamwork.center" />

                <div class="text-xl font-bold dark:text-white">@lang('teamwork::app.center.title')</div>

                <p class="text-xs text-gray-500">@lang('teamwork::app.center.subtitle')</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <nav class="tw-tabs">
                    <a href="{{ route('admin.teamwork.center') }}" class="tw-tab {{ $tab === 'mine' ? 'is-active' : '' }}">@lang('teamwork::app.center.tab-mine')</a>
                    <a href="{{ route('admin.teamwork.center', ['tab' => 'team']) }}" class="tw-tab {{ $tab === 'team' ? 'is-active' : '' }}">@lang('teamwork::app.center.tab-team')</a>
                </nav>

                @if ($tab === 'team')
                    {{-- Focus on one teammate --}}
                    <form method="GET" action="{{ route('admin.teamwork.center') }}">
                        <input type="hidden" name="tab" value="team">

                        <select name="member" class="tw-input" style="width: auto; padding-top: 6px; padding-bottom: 6px;" onchange="this.form.submit()">
                            <option value="">@lang('teamwork::app.center.team.everyone')</option>

                            @foreach ($members as $person)
                                <option value="{{ $person->id }}" @selected($member?->id === $person->id)>{{ $person->name }}{{ $person->id === $me->id ? ' ('.trans('teamwork::app.follow-up.me').')' : '' }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif

                @if (bouncer()->hasPermission('settings.teamwork.rules'))
                    <a href="{{ route('admin.settings.teamwork.rules.index') }}" class="tw-btn">@lang('teamwork::app.center.rules-link')</a>
                @endif
            </div>
        </div>

        @if ($tab === 'mine')
            {{-- KPIs --}}
            <div class="tw-kpis">
                <a href="#tw-urgent" class="tw-kpi tw-urgent">
                    <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.urgent')</span>
                    <span class="tw-kpi-value">{{ $urgent->count() + $urgentActivities->count() }}</span>
                </a>

                <a href="#tw-cases" class="tw-kpi tw-overdue">
                    <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.late-cases')</span>
                    <span class="tw-kpi-value">{{ $lateCases }}</span>
                </a>

                <a href="#tw-cases" class="tw-kpi tw-warning">
                    <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.attention')</span>
                    <span class="tw-kpi-value">{{ $attentionCases }}</span>
                </a>

                <a href="#tw-follow-ups" class="tw-kpi tw-info">
                    <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.follow-ups')</span>
                    <span class="tw-kpi-value">{{ $followUps->count() }}
                        @if ($late = $followUps->where('state', 'overdue')->count())
                            <small>{{ $late }} @lang('teamwork::app.states.overdue')</small>
                        @endif
                    </span>
                </a>
            </div>

            {{-- Notes from teammates waiting to be read --}}
            @if ($unreadNotes->count())
                <section class="tw-card tw-info" style="border-color: rgba(79, 124, 255, 0.35); box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.07);">
                    <div class="tw-card-head">
                        <span class="tw-card-title">@lang('teamwork::app.notes.unread-title')</span>
                        <span class="tw-count">{{ $unreadNotes->count() }}</span>
                    </div>

                    @foreach ($unreadNotes as $note)
                        <a href="{{ route('admin.teamwork.notes.show', $note->id) }}" class="tw-row tw-info" style="grid-template-columns: auto minmax(0, 1fr) auto; text-decoration: none;">
                            <span class="tw-avatar">{{ mb_substr($note->sender?->name ?? '?', 0, 1) }}</span>
                            <span class="min-w-0">
                                <span class="tw-title">{{ $note->sender?->name }} · {{ $note->title }}</span>
                                <span class="tw-meta">{{ \Illuminate\Support\Str::limit($note->body, 140) }}</span>
                            </span>
                            <span class="tw-btn tw-btn-primary">@lang('teamwork::app.notes.read-action')</span>
                        </a>
                    @endforeach
                </section>
            @endif

            {{-- Urgent: follow-ups and urgent activities --}}
            @if ($urgent->count() || $urgentActivities->count())
                <section id="tw-urgent" class="tw-card tw-urgent-card tw-urgent">
                    <div class="tw-card-head">
                        <span class="tw-card-title">@lang('teamwork::app.center.urgent-title')</span>
                        <span class="tw-count">{{ $urgent->count() + $urgentActivities->count() }}</span>
                    </div>

                    @if ($urgent->count())
                        @include('teamwork::center.follow-up-rows', ['items' => $urgent, 'showFrom' => true])
                    @endif

                    @if ($urgentActivities->count())
                        @include('teamwork::center.activity-rows', ['items' => $urgentActivities, 'showOwner' => false])
                    @endif
                </section>
            @endif

            {{-- My follow-ups --}}
            <section id="tw-follow-ups" class="tw-card tw-info">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('teamwork::app.center.follow-ups-title')</span>
                    <span class="tw-count">{{ $followUps->count() }}</span>
                </div>

                @if ($followUps->count())
                    @include('teamwork::center.follow-up-rows', ['items' => $followUps, 'showFrom' => true])
                @else
                    <p class="tw-empty">@lang('teamwork::app.center.empty-follow-ups')</p>
                @endif
            </section>

            {{-- Open cases by idle time --}}
            <section id="tw-cases" class="tw-card tw-warning">
                <div class="tw-card-head">
                    <div>
                        <span class="tw-card-title">@lang('teamwork::app.center.cases-title')</span>
                        <p class="tw-card-sub">@lang('teamwork::app.center.cases-info')</p>
                    </div>

                    <span class="tw-count">{{ $cases->count() }}</span>
                </div>

                @if ($cases->count())
                    @include('teamwork::center.case-rows', ['items' => $cases, 'showOwner' => false, 'canHandoff' => $canHandoff])
                @else
                    <p class="tw-empty">@lang('teamwork::app.center.empty-cases')</p>
                @endif
            </section>

            {{-- Notes I sent that are still unread --}}
            @if ($sentUnread->count())
                <section class="tw-card tw-warning">
                    <div class="tw-card-head">
                        <span class="tw-card-title">@lang('teamwork::app.notes.sent-unread-title')</span>
                        <span class="tw-count">{{ $sentUnread->count() }}</span>
                    </div>

                    @foreach ($sentUnread as $note)
                        <a href="{{ route('admin.teamwork.notes.show', $note->id) }}" class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto; text-decoration: none;">
                            <span class="min-w-0">
                                <span class="tw-title">→ {{ $note->recipient?->name }} · {{ $note->title }}</span>
                                <span class="tw-meta">{{ \Illuminate\Support\Str::limit($note->body, 140) }} · {{ $note->created_at->diffForHumans() }}</span>
                            </span>
                            <span class="tw-warning"><span class="tw-pill">✓ @lang('teamwork::app.notes.unread')</span></span>
                        </a>
                    @endforeach
                </section>
            @endif

            {{-- Records I follow --}}
            @if ($following->count())
                <section class="tw-card tw-info">
                    <div class="tw-card-head">
                        <div>
                            <span class="tw-card-title">@lang('teamwork::app.followers.title')</span>
                            <p class="tw-card-sub">@lang('teamwork::app.followers.info')</p>
                        </div>
                        <span class="tw-count">{{ $following->count() }}</span>
                    </div>

                    @foreach ($following as $record)
                        <div class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto;">
                            <span class="min-w-0">
                                <a href="{{ $record->url }}" class="tw-title">{{ $record->title }}</a>
                                <span class="tw-meta">@lang('teamwork::app.entities.'.$record->type){{ $record->auto ? ' · '.trans('teamwork::app.followers.auto') : '' }}</span>
                            </span>

                            <span class="tw-actions">
                                <a href="{{ route('admin.teamwork.records.show', [$record->type, $record->id]) }}" class="tw-btn">@lang('teamwork::app.records.team-activity')</a>

                                <form method="POST" action="{{ route('admin.teamwork.records.follow', [$record->type, $record->id]) }}">
                                    @csrf
                                    <button type="submit" class="tw-btn">@lang('teamwork::app.followers.unfollow')</button>
                                </form>
                            </span>
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- Delegated --}}
            @if ($delegated->count())
                <section class="tw-card tw-info">
                    <div class="tw-card-head">
                        <span class="tw-card-title">@lang('teamwork::app.center.delegated-title')</span>
                        <span class="tw-count">{{ $delegated->count() }}</span>
                    </div>

                    @include('teamwork::center.follow-up-rows', ['items' => $delegated, 'showTo' => true])
                </section>
            @endif
        @else
            {{-- Team KPIs (whole team, whatever the focus) --}}
            <div class="tw-kpis">
                <div class="tw-kpi tw-overdue">
                    <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.late-cases')</span>
                    <span class="tw-kpi-value">{{ $team->sum('overdue') }}</span>
                </div>

                <div class="tw-kpi tw-warning">
                    <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.attention')</span>
                    <span class="tw-kpi-value">{{ $team->sum('warning') }}</span>
                </div>

                <div class="tw-kpi tw-urgent">
                    <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.urgent')</span>
                    <span class="tw-kpi-value">{{ $team->sum('urgent') }}</span>
                </div>

                <div class="tw-kpi tw-info">
                    <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.follow-ups')</span>
                    <span class="tw-kpi-value">{{ $team->sum('follow_ups') }}
                        @if ($team->sum('follow_ups_late'))
                            <small>{{ $team->sum('follow_ups_late') }} @lang('teamwork::app.states.overdue')</small>
                        @endif
                    </span>
                </div>
            </div>

            {{-- Team summary: click a person to focus on them --}}
            <section class="tw-card tw-info">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('teamwork::app.center.team.summary-title')</span>
                    <span class="tw-count">{{ $team->count() }}</span>
                </div>

                <div class="tw-row tw-row-head tw-cols-team">
                    <span>@lang('teamwork::app.center.team.agent')</span>
                    <span>@lang('teamwork::app.center.team.open')</span>
                    <span>@lang('teamwork::app.center.team.attention')</span>
                    <span>@lang('teamwork::app.center.team.late')</span>
                    <span>@lang('teamwork::app.center.team.follow-ups')</span>
                    <span>@lang('teamwork::app.center.team.urgent')</span>
                    <span>@lang('teamwork::app.center.team.oldest')</span>
                </div>

                @foreach ($team as $person)
                    <div class="tw-row tw-cols-team" style="{{ $member?->id === $person->id ? 'background: rgba(79, 124, 255, 0.08);' : '' }}">
                        <a href="{{ route('admin.teamwork.center', ['tab' => 'team', 'member' => $person->id]) }}" class="tw-title">{{ $person->name }}</a>
                        <span class="tw-hide-sm">{{ $person->open_cases }}</span>
                        <span class="tw-hide-sm tw-warning"><span class="tw-idle">{{ $person->warning }}</span></span>
                        <span class="tw-overdue"><span class="tw-idle">{{ $person->overdue }}</span></span>
                        <span class="tw-hide-sm">{{ $person->follow_ups }}@if ($person->follow_ups_late) <span class="tw-overdue tw-idle">({{ $person->follow_ups_late }})</span>@endif</span>
                        <span class="tw-hide-sm tw-urgent"><span class="tw-idle">{{ $person->urgent }}</span></span>
                        <span class="tw-hide-sm tw-meta">{{ $person->oldest_label ?? '—' }}</span>
                    </div>
                @endforeach
            </section>

            {{-- Escalations to the owner (legacy SLA escalation) --}}
            @if ($escalated->count())
                <section class="tw-card tw-urgent-card tw-urgent">
                    <div class="tw-card-head">
                        <div>
                            <span class="tw-card-title">@lang('teamwork::app.center.team.escalated-title')</span>
                            <p class="tw-card-sub">@lang('teamwork::app.center.team.escalated-info')</p>
                        </div>

                        <span class="tw-count">{{ $escalated->count() }}</span>
                    </div>

                    @foreach ($escalated as $lead)
                        <div class="tw-row tw-cols-follow tw-urgent">
                            <div class="min-w-0">
                                <a href="{{ $lead->url }}" class="tw-title">{{ $lead->title }}</a>
                                <span class="tw-meta">{{ $lead->escalation_reason ?: ($lead->person_name ?? '—') }}</span>
                            </div>
                            <span class="tw-hide-sm tw-meta" style="color: inherit;">{{ $lead->owner_name ?? '—' }}</span>
                            <span class="tw-hide-sm tw-idle">{{ $lead->since_label }}</span>
                            <span><span class="tw-pill">@lang('teamwork::app.center.team.escalated')</span></span>
                            <div class="tw-actions">
                                @if ($canHandoff)
                                    <button type="button" class="tw-btn" data-tw-handoff data-lead-id="{{ $lead->id }}" data-owner="{{ $lead->user_id }}" data-heading="{{ $lead->title }}">@lang('teamwork::app.handoff.button')</button>
                                @endif
                                <a href="{{ $lead->url }}" class="tw-btn">@lang('teamwork::app.center.actions.open')</a>
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- Cases: late/at-risk for the whole team, or all open cases of the focused person --}}
            <section class="tw-card tw-overdue">
                <div class="tw-card-head">
                    <span class="tw-card-title">
                        {{ $member ? trans('teamwork::app.center.team.member-cases', ['name' => $member->name]) : trans('teamwork::app.center.team.late-title') }}
                    </span>
                    <span class="tw-count">{{ $teamCases->count() }}</span>
                </div>

                @if ($teamCases->count())
                    @include('teamwork::center.case-rows', ['items' => $teamCases, 'showOwner' => true, 'canHandoff' => $canHandoff])
                @else
                    <p class="tw-empty">@lang('teamwork::app.center.team.empty')</p>
                @endif
            </section>

            {{-- Follow-ups --}}
            <section class="tw-card tw-urgent">
                <div class="tw-card-head">
                    <span class="tw-card-title">
                        {{ $member ? trans('teamwork::app.center.team.member-follow-ups', ['name' => $member->name]) : trans('teamwork::app.center.team.follow-ups-title') }}
                    </span>
                    <span class="tw-count">{{ $teamFollowUps->count() + $teamUrgentActivities->count() }}</span>
                </div>

                @if ($teamFollowUps->count())
                    @include('teamwork::center.follow-up-rows', ['items' => $teamFollowUps, 'showTo' => true, 'showFrom' => true])
                @endif

                @if ($teamUrgentActivities->count())
                    @include('teamwork::center.activity-rows', ['items' => $teamUrgentActivities, 'showOwner' => true])
                @endif

                @if (! $teamFollowUps->count() && ! $teamUrgentActivities->count())
                    <p class="tw-empty">@lang('teamwork::app.center.team.empty')</p>
                @endif
            </section>
        @endif
    </div>

    @include('teamwork::partials.modal')

    @if ($canHandoff)
        @include('teamwork::partials.handoff-modal')
    @endif
</x-admin::layouts>

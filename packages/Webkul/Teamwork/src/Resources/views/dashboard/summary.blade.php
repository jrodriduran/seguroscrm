{{-- Follow-up strip at the top of the dashboard: what needs the user today. --}}
@php
    $twUser = auth()->guard('user')->user();
    $twQueue = app(\Webkul\Teamwork\Services\WorkQueue::class);
    $twScope = app(\Webkul\Teamwork\Services\TeamScope::class);

    $twFollowUps = $twQueue->openFollowUps([$twUser->id]);
    $twCases = $twQueue->openCases([$twUser->id], 500);
    $twTeamIds = $twScope->supervisedUserIds($twUser);
    $twTeamCases = $twTeamIds ? $twQueue->openCases($twTeamIds, PHP_INT_MAX) : collect();
@endphp

<div class="mb-4 flex flex-col gap-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('teamwork::app.dashboard.title')</p>

        <div class="flex items-center gap-2">
            @if ($twTeamIds)
                <a href="{{ route('admin.teamwork.center', ['tab' => 'team']) }}" class="tw-btn tw-overdue">
                    @lang('teamwork::app.dashboard.team', ['late' => $twTeamCases->where('state', 'overdue')->count(), 'attention' => $twTeamCases->where('state', 'warning')->count()])
                </a>
            @endif

            <a href="{{ route('admin.teamwork.center') }}" class="tw-btn tw-btn-primary">@lang('teamwork::app.dashboard.open-center')</a>
        </div>
    </div>

    <div class="tw-kpis">
        <a href="{{ route('admin.teamwork.center') }}#tw-urgent" class="tw-kpi tw-urgent">
            <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.urgent')</span>
            <span class="tw-kpi-value">{{ $twFollowUps->filter->isUrgent()->count() }}</span>
        </a>

        <a href="{{ route('admin.teamwork.center') }}#tw-cases" class="tw-kpi tw-overdue">
            <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.late-cases')</span>
            <span class="tw-kpi-value">{{ $twCases->where('state', 'overdue')->count() }}</span>
        </a>

        <a href="{{ route('admin.teamwork.center') }}#tw-cases" class="tw-kpi tw-warning">
            <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.attention')</span>
            <span class="tw-kpi-value">{{ $twCases->where('state', 'warning')->count() }}</span>
        </a>

        <a href="{{ route('admin.teamwork.center') }}#tw-follow-ups" class="tw-kpi tw-info">
            <span class="tw-kpi-label">@lang('teamwork::app.center.kpi.follow-ups')</span>
            <span class="tw-kpi-value">{{ $twFollowUps->reject->isUrgent()->count() }}
                @if ($twLate = $twFollowUps->where('state', 'overdue')->count())
                    <small>{{ $twLate }} @lang('teamwork::app.states.overdue')</small>
                @endif
            </span>
        </a>
    </div>
</div>

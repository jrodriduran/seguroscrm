{{-- Open cases ranked by business hours without a touch. $showOwner adds the owner and the "mark urgent" action. --}}
<div class="tw-row tw-row-head {{ $showOwner ? 'tw-cols-team-cases' : 'tw-cols-cases' }}">
    <span>@lang('teamwork::app.center.col.record')</span>
    <span>@lang('teamwork::app.center.col.stage')</span>
    <span>{{ $showOwner ? trans('teamwork::app.center.col.owner') : trans('teamwork::app.center.col.state') }}</span>
    <span>@lang('teamwork::app.center.col.idle')</span>
    <span>{{ $showOwner ? trans('teamwork::app.center.col.state') : '' }}</span>
    <span></span>
</div>

@foreach ($items as $case)
    <div class="tw-row {{ $showOwner ? 'tw-cols-team-cases' : 'tw-cols-cases' }} tw-{{ $case->state }}">
        <div class="min-w-0">
            <a href="{{ $case->url }}" class="tw-title">{{ $case->title }}</a>
            <span class="tw-meta">{{ $case->person_name ?? '—' }}</span>
        </div>

        <div class="tw-hide-sm min-w-0">
            <span class="tw-meta" style="color: inherit;">{{ $case->stage_name }}</span>
            <span class="tw-meta">{{ $case->pipeline_name }}</span>
        </div>

        @if ($showOwner)
            <span class="tw-hide-sm tw-meta" style="color: inherit;">{{ $case->owner_name ?? '—' }}</span>
        @else
            <span class="tw-hide-sm"><span class="tw-pill">@lang('teamwork::app.states.'.$case->state)</span></span>
        @endif

        <span class="tw-idle" title="{{ core()->formatDate($case->last_touch_at, 'd M Y H:i') }}">{{ $case->idle_label }}</span>

        <span class="tw-hide-sm">
            @if ($showOwner)
                <span class="tw-pill">@lang('teamwork::app.states.'.$case->state)</span>
            @endif
        </span>

        <div class="tw-actions">
            @if ($showOwner && $case->user_id)
                <button
                    type="button"
                    class="tw-btn tw-urgent"
                    data-tw-open
                    data-entity-type="lead"
                    data-entity-id="{{ $case->id }}"
                    data-assignee="{{ $case->user_id }}"
                    data-priority="urgent"
                    data-heading="{{ trans('teamwork::app.follow-up.modal-urgent-title') }} — {{ $case->title }}"
                >@lang('teamwork::app.center.actions.mark-urgent')</button>
            @else
                <button
                    type="button"
                    class="tw-btn"
                    data-tw-open
                    data-entity-type="lead"
                    data-entity-id="{{ $case->id }}"
                    data-heading="{{ trans('teamwork::app.follow-up.modal-title') }} — {{ $case->title }}"
                >@lang('teamwork::app.center.actions.follow-up')</button>
            @endif

            @if ($canHandoff ?? false)
                <button
                    type="button"
                    class="tw-btn"
                    data-tw-handoff
                    data-lead-id="{{ $case->id }}"
                    data-owner="{{ $case->user_id }}"
                    data-heading="{{ $case->title }}"
                >@lang('teamwork::app.handoff.button')</button>
            @endif

            <a href="{{ $case->url }}" class="tw-btn">@lang('teamwork::app.center.actions.open')</a>
        </div>
    </div>
@endforeach

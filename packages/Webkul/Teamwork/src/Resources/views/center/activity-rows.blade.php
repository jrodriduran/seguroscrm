{{-- Pending activities flagged urgent (from a lead's activity list). --}}
@foreach ($items as $activity)
    <div class="tw-row tw-cols-follow tw-urgent">
        <div class="min-w-0">
            <a href="{{ $activity->url }}" class="tw-title">
                <span class="tw-pill tw-urgent" style="margin-right: 6px;">@lang('teamwork::app.center.activity-badge')</span>
                {{ $activity->title ?: ucfirst($activity->type) }}
            </a>

            <span class="tw-meta">{{ $activity->lead_title ?? '—' }}</span>
        </div>

        <span class="tw-hide-sm tw-meta" style="color: inherit;">
            {{ $showOwner ? ($activity->owner_name ?? '—') : ucfirst($activity->type) }}
        </span>

        <span class="tw-hide-sm tw-meta" style="color: inherit;">
            {{ $activity->schedule_from ? core()->formatDate($activity->schedule_from, 'd M H:i') : '—' }}
        </span>

        <span class="tw-{{ $activity->state }}"><span class="tw-pill">@lang('teamwork::app.states.'.$activity->state)</span></span>

        <div class="tw-actions">
            <a href="{{ $activity->url }}" class="tw-btn">@lang('teamwork::app.center.actions.open')</a>
        </div>
    </div>
@endforeach

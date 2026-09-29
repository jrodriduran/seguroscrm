{{-- Follow-up rows. $showFrom / $showTo pick which person column to show. --}}
@php
    $showFrom ??= false;
    $showTo ??= false;
    $twMe = auth()->guard('user')->user();
    $twIsMaster = app(\Webkul\Teamwork\Services\TeamScope::class)->isMasterAgent($twMe);
@endphp

<div class="tw-row tw-row-head tw-cols-follow">
    <span>@lang('teamwork::app.center.col.record')</span>
    <span>{{ $showTo ? trans('teamwork::app.center.col.to') : trans('teamwork::app.center.col.from') }}</span>
    <span>@lang('teamwork::app.center.col.due')</span>
    <span>@lang('teamwork::app.center.col.state')</span>
    <span></span>
</div>

@foreach ($items as $item)
    <div class="tw-row tw-cols-follow tw-{{ $item->isUrgent() ? 'urgent' : $item->state }}">
        <div class="min-w-0">
            <a href="{{ route('admin.teamwork.follow_ups.show', $item->id) }}" class="tw-title">
                @if ($item->isUrgent())
                    <span class="tw-pill tw-urgent" style="margin-right: 6px;">@lang('teamwork::app.follow-up.urgent')</span>
                @endif
                {{ $item->title }}
            </a>

            <span class="tw-meta">
                @lang('teamwork::app.entities.'.$item->entity_type)
                @if ($item->note) · {{ \Illuminate\Support\Str::limit($item->note, 90) }} @endif
            </span>
        </div>

        <span class="tw-hide-sm tw-meta" style="color: inherit;">
            @if ($showTo)
                {{ $item->assignee?->name }}
                @if ($showFrom && $item->created_by !== $item->assigned_to)
                    <span class="tw-meta">← {{ $item->creator?->name }}</span>
                @endif
            @else
                {{ $item->created_by === $item->assigned_to ? trans('teamwork::app.follow-up.me') : $item->creator?->name }}
            @endif
        </span>

        <span class="tw-hide-sm tw-meta" style="color: inherit;">
            {{ $item->due_at ? core()->formatDate($item->due_at, 'd M H:i') : trans('teamwork::app.center.col.opened').' '.$item->age_label }}
        </span>

        <span class="tw-{{ $item->state }}"><span class="tw-pill">@lang('teamwork::app.states.'.$item->state)</span></span>

        <div class="tw-actions">
            <a href="{{ route('admin.teamwork.follow_ups.show', $item->id) }}" class="tw-btn">@lang('teamwork::app.center.actions.thread')</a>

            @if ($twIsMaster || in_array($twMe->id, [$item->assigned_to, $item->created_by], true))
                <form method="POST" action="{{ route('admin.teamwork.follow_ups.resolve', $item->id) }}">
                    @csrf
                    <input type="hidden" name="return" value="center">
                    <button type="submit" class="tw-btn tw-ok">@lang('teamwork::app.center.actions.done')</button>
                </form>
            @endif
        </div>
    </div>
@endforeach

{{-- Notification bell (header). The counter refreshes every minute; the list is rendered with the page. --}}
@php
    $twNotifier = app(\Webkul\Teamwork\Services\Notifier::class);
    $twUserId = auth()->guard('user')->id();
    $twUnread = $twNotifier->unreadCount($twUserId);
    $twLatest = $twNotifier->latest($twUserId);

    // Teammates for @mention autocomplete.
    $twTeam = app(\Webkul\Teamwork\Services\TeamScope::class)
        ->assignableUsers(auth()->guard('user')->user())
        ->where('id', '!=', $twUserId)
        ->map(fn ($member) => ['id' => $member->id, 'name' => $member->name])
        ->values();
@endphp

<div
    class="tw-bell"
    data-tw-bell
    data-count-url="{{ route('admin.teamwork.notifications.count') }}"
    data-team="{{ json_encode($twTeam) }}"
>
    <button type="button" class="tw-bell-btn" data-tw-bell-toggle aria-label="@lang('teamwork::app.notifications.title')" title="@lang('teamwork::app.notifications.title')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
        <span class="tw-bell-badge" data-tw-bell-badge @if (! $twUnread) hidden @endif>{{ $twUnread > 99 ? '99+' : $twUnread }}</span>
    </button>

    <div class="tw-bell-panel" data-tw-bell-panel>
        <div class="tw-bell-head">
            <span class="text-sm font-semibold text-gray-800 dark:text-white">@lang('teamwork::app.notifications.title')</span>

            @if ($twUnread)
                <form method="POST" action="{{ route('admin.teamwork.notifications.read_all') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-brandColor">@lang('teamwork::app.notifications.read-all')</button>
                </form>
            @endif
        </div>

        <div class="tw-bell-list">
            @forelse ($twLatest as $notification)
                <a
                    href="{{ route('admin.teamwork.notifications.open', $notification->id) }}"
                    class="tw-bell-item {{ $notification->read_at ? '' : 'is-unread' }} {{ $notification->is_urgent ? 'tw-urgent' : 'tw-info' }}"
                >
                    <span class="tw-avatar" style="width: 28px; height: 28px; font-size: 11px;">{{ mb_substr($notification->actor?->name ?? '•', 0, 1) }}</span>

                    <span class="min-w-0 flex-1">
                        <span class="tw-bell-title">{{ $notification->title }}</span>
                        @if ($notification->body)
                            <span class="tw-bell-body">{{ $notification->body }}</span>
                        @endif
                        <span class="tw-bell-time">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <p class="tw-empty">@lang('teamwork::app.notifications.empty')</p>
            @endforelse
        </div>

        <a href="{{ route('admin.teamwork.notifications.index') }}" class="tw-bell-foot">@lang('teamwork::app.notifications.see-all')</a>
    </div>
</div>

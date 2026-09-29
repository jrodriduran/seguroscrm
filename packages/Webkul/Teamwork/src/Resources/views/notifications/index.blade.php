<x-admin::layouts>
    <x-slot:title>
        @lang('teamwork::app.notifications.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="teamwork.notifications" />
                <div class="text-xl font-bold dark:text-white">@lang('teamwork::app.notifications.title')</div>
            </div>

            <form method="POST" action="{{ route('admin.teamwork.notifications.read_all') }}">
                @csrf
                <button type="submit" class="tw-btn">@lang('teamwork::app.notifications.read-all')</button>
            </form>
        </div>

        <section class="tw-card tw-info">
            @forelse ($notifications as $notification)
                <a
                    href="{{ route('admin.teamwork.notifications.open', $notification->id) }}"
                    class="tw-row {{ $notification->is_urgent ? 'tw-urgent' : 'tw-info' }}"
                    style="grid-template-columns: auto minmax(0, 1fr) auto; text-decoration: none; {{ $notification->read_at ? '' : 'font-weight: 600;' }}"
                >
                    <span class="tw-avatar">{{ mb_substr($notification->actor?->name ?? '•', 0, 1) }}</span>

                    <span class="min-w-0">
                        <span class="tw-title" style="white-space: normal;">{{ $notification->title }}</span>
                        @if ($notification->body)
                            <span class="tw-meta" style="white-space: normal;">{{ $notification->body }}</span>
                        @endif
                    </span>

                    <span class="flex flex-col items-end gap-1">
                        <span class="tw-meta">{{ core()->formatDate($notification->created_at, 'd M H:i') }}</span>
                        @unless ($notification->read_at)
                            <span class="tw-pill">@lang('teamwork::app.notifications.new')</span>
                        @endunless
                    </span>
                </a>
            @empty
                <p class="tw-empty">@lang('teamwork::app.notifications.empty')</p>
            @endforelse
        </section>

        {{ $notifications->links() }}
    </div>
</x-admin::layouts>

<x-admin::layouts>
    <x-slot:title>
        @lang('teamwork::app.records.team-activity') · {{ $record['title'] }}
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex min-w-0 flex-col gap-1">
                <x-admin::breadcrumbs name="teamwork.center" />

                <div class="text-xl font-bold dark:text-white">@lang('teamwork::app.records.team-activity')</div>

                <p class="truncate text-xs text-gray-500">@lang('teamwork::app.entities.'.$type) · {{ $record['title'] }}</p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="tw-btn" data-tw-note>@lang('teamwork::app.notes.button')</button>
                <button type="button" class="tw-btn" data-tw-open>@lang('teamwork::app.follow-up.button')</button>
                <a href="{{ $record['url'] }}" class="tw-btn tw-btn-primary">@lang('teamwork::app.follow-up.show.go-to-record')</a>
            </div>
        </div>

        <section class="tw-card tw-info">
            <div class="tw-card-head">
                <span class="tw-card-title">@lang('teamwork::app.records.feed')</span>
                <span class="tw-count">{{ $feed->count() }}</span>
            </div>

            <div class="flex flex-col gap-3 p-4">
                @forelse ($feed as $entry)
                    @php($item = $entry['item'])

                    @if ($entry['kind'] === 'note')
                        <a href="{{ route('admin.teamwork.notes.show', $item->id) }}" class="tw-msg" style="text-decoration: none;">
                            <span class="tw-avatar">{{ mb_substr($item->sender?->name ?? '?', 0, 1) }}</span>

                            <div class="min-w-0 flex-1">
                                <p class="mb-1 text-xs text-gray-500">
                                    <span class="tw-info"><span class="tw-pill">@lang('teamwork::app.notes.title')</span></span>
                                    {{ $item->sender?->name }} → {{ $item->recipient?->name }} · {{ core()->formatDate($item->created_at, 'd M H:i') }}
                                    <span class="{{ $item->read_at ? 'tw-ok' : 'tw-warning' }}"><span class="tw-pill" style="margin-left: 4px;">{{ $item->read_at ? '✓✓ '.trans('teamwork::app.notes.read', ['date' => core()->formatDate($item->read_at, 'd M H:i')]) : '✓ '.trans('teamwork::app.notes.unread') }}</span></span>
                                </p>
                                <div class="tw-bubble">{{ $item->body }}</div>
                            </div>
                        </a>
                    @else
                        <a href="{{ route('admin.teamwork.follow_ups.show', $item->id) }}" class="tw-msg" style="text-decoration: none;">
                            <span class="tw-avatar" style="background: linear-gradient(135deg, #f59e0b, #e11d48);">{{ mb_substr($item->creator?->name ?? '?', 0, 1) }}</span>

                            <div class="min-w-0 flex-1">
                                <p class="mb-1 text-xs text-gray-500">
                                    <span class="{{ $item->isUrgent() ? 'tw-urgent' : 'tw-warning' }}"><span class="tw-pill">{{ $item->isUrgent() ? trans('teamwork::app.follow-up.urgent') : trans('teamwork::app.follow-up.button') }}</span></span>
                                    {{ $item->creator?->name }} → {{ $item->assignee?->name }} · {{ core()->formatDate($item->created_at, 'd M H:i') }}
                                    <span class="{{ $item->isOpen() ? 'tw-'.$item->state : 'tw-ok' }}"><span class="tw-pill" style="margin-left: 4px;">{{ $item->isOpen() ? trans('teamwork::app.states.'.$item->state) : trans('teamwork::app.follow-up.show.done') }}</span></span>
                                </p>
                                <div class="tw-bubble">{{ app(\Webkul\Teamwork\Services\Mentions::class)->render($item->note ?: '—') }}</div>
                            </div>
                        </a>
                    @endif
                @empty
                    <p class="tw-empty">@lang('teamwork::app.records.empty')</p>
                @endforelse
            </div>
        </section>
    </div>

    @include('teamwork::partials.modal', ['entityType' => $type, 'entityId' => $id, 'recordTitle' => $record['title']])
    @include('teamwork::partials.note-modal', ['entityType' => $type, 'entityId' => $id, 'recordTitle' => $record['title']])
</x-admin::layouts>

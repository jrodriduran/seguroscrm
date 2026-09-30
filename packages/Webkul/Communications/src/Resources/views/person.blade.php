<x-admin::layouts>
    <x-slot:title>
        @lang('communications::app.person.title') · {{ $person->name }}
    </x-slot>

    @php
        $channelMeta = [
            'chat' => ['#16a34a', 'M7.9 20A9 9 0 1 0 4 16.1L2 22Z'],
            'email' => ['#3b82f6', 'M22 7l-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z'],
            'call' => ['#0ea5e9', 'M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z'],
            'meeting' => ['#8b5cf6', 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z'],
            'note' => ['#f59e0b', 'M16 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8ZM15 3v4a2 2 0 0 0 2 2h4'],
            'file' => ['#64748b', 'M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7ZM14 2v4a2 2 0 0 0 2 2h4'],
        ];

        $query = fn (array $changes) => route('admin.communications.persons.show', array_filter(array_merge([
            'id' => $person->id,
            'channel' => $filters['channel'],
            'direction' => $filters['direction'],
            'q' => $filters['search'],
            'sort' => $filters['sort'] === 'oldest' ? 'oldest' : null,
        ], $changes), fn ($value) => $value !== null && $value !== ''));
    @endphp

    {{-- v-pre: message text comes from clients and must never be read as Vue template syntax. --}}
    <div class="flex flex-col gap-4" v-pre>
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex min-w-0 flex-col gap-1">
                <x-admin::breadcrumbs name="communications.person" :entity="$person" />

                <div class="text-xl font-bold dark:text-white">@lang('communications::app.person.title')</div>

                <p class="text-xs text-gray-500">
                    {{ $person->name }}
                    @if ($lastContact)
                        · @lang('communications::app.person.last-contact', ['when' => $lastContact->diffForHumans()])
                    @endif
                </p>
            </div>

            <a href="{{ route('admin.contacts.persons.view', $person->id) }}" class="tw-btn tw-btn-primary">@lang('communications::app.person.back')</a>
        </div>

        {{-- Channel filters with counts --}}
        <div class="tw-card p-3">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $query(['channel' => null]) }}" class="tw-btn {{ ! $filters['channel'] ? 'tw-btn-primary' : '' }}">
                    @lang('communications::app.channels.all') <span class="tw-count" style="margin-left: 4px;">{{ $total }}</span>
                </a>

                @foreach (\Webkul\Communications\Services\ClientTimeline::CHANNELS as $channel)
                    <a href="{{ $query(['channel' => $channel]) }}" class="tw-btn {{ $filters['channel'] === $channel ? 'tw-btn-primary' : '' }}" style="--tw-c: {{ $channelMeta[$channel][0] }};">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $channelMeta[$channel][1] }}"/></svg>
                        @lang('communications::app.channels.'.$channel)
                        <span class="tw-count" style="margin-left: 4px;">{{ $counts[$channel] ?? 0 }}</span>
                    </a>
                @endforeach

                <form method="GET" action="{{ route('admin.communications.persons.show', $person->id) }}" class="ml-auto flex flex-wrap items-center gap-2">
                    @foreach (['channel' => $filters['channel']] as $name => $value)
                        @if ($value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    <select name="direction" class="tw-input" style="width: auto;" onchange="this.form.submit()">
                        <option value="">@lang('communications::app.filters.any-direction')</option>
                        <option value="in" @selected($filters['direction'] === 'in')>@lang('communications::app.filters.incoming')</option>
                        <option value="out" @selected($filters['direction'] === 'out')>@lang('communications::app.filters.outgoing')</option>
                    </select>

                    <select name="sort" class="tw-input" style="width: auto;" onchange="this.form.submit()">
                        <option value="newest">@lang('communications::app.filters.newest')</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>@lang('communications::app.filters.oldest')</option>
                    </select>

                    <input type="search" name="q" value="{{ $filters['search'] }}" class="tw-input" style="width: 220px;" placeholder="@lang('communications::app.filters.search')">
                </form>
            </div>
        </div>

        {{-- Reply in the client's latest chat conversation --}}
        @if ($conversation && $canReply)
            <form method="POST" action="{{ route('admin.communications.persons.reply', $person->id) }}" class="tw-card tw-ok p-3">
                @csrf

                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="tw-label" style="margin: 0;">@lang('communications::app.reply.title', ['channel' => trans('communications::app.chatwoot.channels.'.$conversation->channel)])</span>
                    <span class="tw-meta">#{{ $conversation->conversation_id }}</span>
                </div>

                <div class="flex items-end gap-2">
                    <textarea name="message" rows="2" maxlength="2000" required class="tw-input" placeholder="@lang('communications::app.reply.placeholder')">{{ old('message') }}</textarea>
                    <button type="submit" class="tw-btn tw-btn-primary">@lang('communications::app.reply.send')</button>
                </div>
            </form>
        @endif

        {{-- Timeline --}}
        <section class="tw-card tw-info">
            <div class="flex flex-col">
                @forelse ($entries as $entry)
                    @php([$color, $icon] = $channelMeta[$entry['channel']] ?? $channelMeta['note'])

                    <a href="{{ $entry['url'] ?? '#' }}" class="tw-row" style="grid-template-columns: auto minmax(0, 1fr) auto; text-decoration: none; --tw-c: {{ $color }};" @if (str_starts_with($entry['url'] ?? '', 'http') && ! str_starts_with($entry['url'] ?? '', url('/'))) target="_blank" rel="noopener" @endif>
                        <span class="flex items-center justify-center rounded-full" style="width: 36px; height: 36px; color: {{ $color }}; background: color-mix(in srgb, {{ $color }} 12%, transparent);">
                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                        </span>

                        <span class="min-w-0">
                            <span class="tw-title" style="white-space: normal;">
                                <span class="tw-pill" style="margin-right: 6px;">{{ $entry['direction'] === 'in' ? '↙ '.trans('communications::app.filters.incoming') : '↗ '.trans('communications::app.filters.outgoing') }}</span>
                                {{ $entry['title'] ?: trans('communications::app.channels.'.$entry['channel']) }}
                            </span>

                            @if ($entry['body'])
                                <span class="tw-meta" style="white-space: normal;">{{ $entry['body'] }}</span>
                            @endif
                        </span>

                        <span class="flex flex-col items-end gap-1 text-right">
                            <span class="tw-meta">{{ core()->formatDate($entry['at'], 'd M Y H:i') }}</span>
                            <span class="tw-meta">{{ $entry['author'] ?? '' }}</span>
                        </span>
                    </a>
                @empty
                    <p class="tw-empty">@lang('communications::app.person.empty')</p>
                @endforelse
            </div>
        </section>
    </div>
</x-admin::layouts>

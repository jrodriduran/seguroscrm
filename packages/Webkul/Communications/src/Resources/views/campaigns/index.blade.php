<x-admin::layouts>
    <x-slot:title>
        @lang('communications::app.campaigns.title')
    </x-slot>

    @php
        $cmStatus = ['draft' => 'tw-info', 'scheduled' => 'tw-warning', 'sending' => 'tw-warning', 'sent' => 'tw-ok', 'cancelled' => 'tw-overdue'];
        $cmIcons = ['mothers_day' => '💐', 'fathers_day' => '👔', 'thanksgiving' => '🦃', 'christmas' => '🎄', 'new_year' => '🎉'];
    @endphp

    <div class="flex flex-col gap-4" v-pre>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="communications.campaigns" />
                <div class="text-xl font-bold dark:text-white">@lang('communications::app.campaigns.title')</div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.communications.audiences.index') }}" class="tw-btn">@lang('communications::app.audiences.title')</a>
                <a href="{{ route('admin.communications.campaigns.create') }}" class="tw-btn tw-btn-primary">+ @lang('communications::app.campaigns.new')</a>
            </div>
        </div>

        {{-- Special dates --}}
        <section class="tw-card tw-ok">
            <div class="tw-card-head">
                <span class="tw-card-title">📅 @lang('communications::app.occasions.title')</span>
                <span class="tw-meta">@lang('communications::app.occasions.info')</span>
            </div>

            <div class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($occasions as $occasion)
                    <div class="cm-occasion">
                        <span class="cm-occasion-icon">{{ $cmIcons[$occasion['code']] ?? '📅' }}</span>
                        <strong>@lang('communications::app.occasions.names.'.$occasion['code'])</strong>
                        <span class="tw-meta">{{ $occasion['date']->translatedFormat('l j \d\e F Y') }}</span>
                        <span class="tw-meta">@lang('communications::app.occasions.in-days', ['days' => (int) now()->startOfDay()->diffInDays($occasion['date'])])</span>

                        @if (in_array($occasion['code'], $existing, true))
                            <span class="tw-ok"><span class="tw-pill">@lang('communications::app.occasions.ready')</span></span>
                        @else
                            <a href="{{ route('admin.communications.campaigns.create', ['occasion' => $occasion['code']]) }}" class="tw-btn tw-btn-primary justify-center">@lang('communications::app.occasions.prepare')</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section class="tw-card tw-info">
            <div class="tw-card-head">
                <span class="tw-card-title">@lang('communications::app.campaigns.title')</span>
                <span class="tw-count">{{ $campaigns->count() }}</span>
            </div>

            @forelse ($campaigns as $campaign)
                <a href="{{ route('admin.communications.campaigns.show', $campaign->id) }}" class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto auto auto; text-decoration: none;">
                    <span class="min-w-0">
                        <span class="tw-title">{{ $cmIcons[$campaign->occasion] ?? '✉' }} {{ $campaign->name }}</span>
                        <span class="tw-meta">{{ $campaign->audience?->name }} · {{ $campaign->template?->name }} · @lang('communications::app.campaigns.channels.'.$campaign->channel)</span>
                    </span>
                    <span class="tw-meta">{{ $campaign->scheduled_at ? core()->formatDate($campaign->scheduled_at, 'd M Y H:i') : '—' }}</span>
                    <span class="tw-meta">
                        @if ($campaign->stats['total'])
                            @lang('communications::app.campaigns.sent-of', ['sent' => $campaign->stats['sent'], 'total' => $campaign->stats['total']])
                        @endif
                    </span>
                    <span class="{{ $cmStatus[$campaign->status] ?? 'tw-info' }}"><span class="tw-pill">@lang('communications::app.campaigns.statuses.'.$campaign->status)</span></span>
                </a>
            @empty
                <p class="tw-empty">@lang('communications::app.campaigns.empty')</p>
            @endforelse
        </section>
    </div>
</x-admin::layouts>

<x-admin::layouts>
    <x-slot:title>
        {{ $campaign->name }}
    </x-slot>

    @php
        $cmStatus = ['draft' => 'tw-info', 'scheduled' => 'tw-warning', 'sending' => 'tw-warning', 'sent' => 'tw-ok', 'cancelled' => 'tw-overdue'];
        $cmRecipient = ['sent' => 'tw-ok', 'pending' => 'tw-info', 'skipped' => 'tw-warning', 'failed' => 'tw-overdue', 'cancelled' => 'tw-info'];
    @endphp

    <div class="flex flex-col gap-4" v-pre>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="communications.campaigns" />
                <div class="flex items-center gap-2 text-xl font-bold dark:text-white">
                    {{ $campaign->name }}
                    <span class="{{ $cmStatus[$campaign->status] ?? 'tw-info' }}"><span class="tw-pill">@lang('communications::app.campaigns.statuses.'.$campaign->status)</span></span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.communications.campaigns.index') }}" class="tw-btn">@lang('communications::app.templates.back')</a>

                <form method="POST" action="{{ route('admin.communications.campaigns.test', $campaign->id) }}">@csrf<button type="submit" class="tw-btn">✉ @lang('communications::app.campaigns.test')</button></form>

                @if ($campaign->isEditable())
                    <a href="{{ route('admin.communications.campaigns.edit', $campaign->id) }}" class="tw-btn">@lang('communications::app.campaigns.edit')</a>
                @endif

                @if ($campaign->status === 'draft' && $canLaunch)
                    <form method="POST" action="{{ route('admin.communications.campaigns.launch', $campaign->id) }}" onsubmit="return confirm(@js(trans('communications::app.campaigns.launch-confirm', ['count' => $audienceCount])));">
                        @csrf
                        <button type="submit" class="tw-btn tw-btn-primary">🚀 @lang('communications::app.campaigns.launch')</button>
                    </form>
                @elseif ($campaign->status === 'draft')
                    <span class="tw-meta">@lang('communications::app.campaigns.needs-owner')</span>
                @endif

                @if (in_array($campaign->status, ['scheduled', 'sending'], true) && $canLaunch)
                    <form method="POST" action="{{ route('admin.communications.campaigns.cancel', $campaign->id) }}" onsubmit="return confirm(@js(trans('communications::app.campaigns.cancel-confirm')));">
                        @csrf
                        <button type="submit" class="tw-btn tw-overdue">@lang('communications::app.campaigns.cancel')</button>
                    </form>
                @endif

                @if (in_array($campaign->status, ['draft', 'cancelled'], true))
                    <form method="POST" action="{{ route('admin.communications.campaigns.delete', $campaign->id) }}" onsubmit="return confirm(@js(trans('communications::app.campaigns.delete-confirm')));">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tw-btn tw-overdue">@lang('communications::app.campaigns.delete')</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            @foreach (['total' => 'tw-info', 'sent' => 'tw-ok', 'skipped' => 'tw-warning', 'failed' => 'tw-overdue'] as $key => $tone)
                <div class="tw-card {{ $tone }} p-4">
                    <span class="tw-meta">@lang('communications::app.campaigns.stats.'.$key)</span>
                    <div class="text-2xl font-bold dark:text-white">{{ $key === 'total' && ! $stats['total'] ? $audienceCount : $stats[$key] }}</div>
                    @if ($key === 'total' && ! $stats['total'])
                        <span class="tw-meta">@lang('communications::app.campaigns.estimated')</span>
                    @elseif ($key === 'sent' && $stats['by_channel'])
                        <span class="tw-meta">{{ collect($stats['by_channel'])->map(fn ($n, $c) => trans('communications::app.contact.channels.'.$c).': '.$n)->implode(' · ') }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        <section class="tw-card tw-info p-4 text-sm dark:text-gray-300">
            <p><strong>@lang('communications::app.campaigns.audience'):</strong> {{ $campaign->audience?->name ?? '—' }}</p>
            <p><strong>@lang('communications::app.campaigns.template'):</strong> {{ $campaign->template?->name ?? '—' }} @if ($campaign->template) ({{ trans('communications::app.templates.purposes.'.$campaign->template->purpose) }}) @endif</p>
            <p><strong>@lang('communications::app.campaigns.channel'):</strong> @lang('communications::app.campaigns.channels.'.$campaign->channel)</p>
            <p><strong>@lang('communications::app.campaigns.scheduled'):</strong> {{ $campaign->scheduled_at ? core()->formatDate($campaign->scheduled_at, 'd M Y H:i') : trans('communications::app.campaigns.asap') }}</p>
            @if ($campaign->template?->purpose === 'marketing')
                <p class="tw-meta mt-2" style="white-space: normal;">ℹ @lang('communications::app.campaigns.marketing-info')</p>
            @endif
        </section>

        @if ($recipients->isNotEmpty())
            <section class="tw-card tw-ok">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('communications::app.campaigns.recipients')</span>
                    <span class="tw-count">{{ $stats['total'] }}</span>
                </div>

                @foreach ($recipients as $recipient)
                    <a href="{{ route('admin.contacts.persons.view', $recipient->person_id) }}" class="tw-row" style="grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr) auto; text-decoration: none;">
                        <span class="tw-title">{{ $recipient->name }}</span>
                        <span class="tw-meta" style="white-space: normal;">{{ $recipient->channel ? trans('communications::app.contact.channels.'.$recipient->channel).' · ' : '' }}{{ $recipient->detail }}</span>
                        <span class="{{ $cmRecipient[$recipient->status] ?? 'tw-info' }}"><span class="tw-pill">@lang('communications::app.campaigns.recipient-statuses.'.$recipient->status)</span></span>
                    </a>
                @endforeach
            </section>
        @endif
    </div>
</x-admin::layouts>

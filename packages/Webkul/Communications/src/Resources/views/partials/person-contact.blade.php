{{-- How the client wants to be reached and what they agreed to (contact page, left panel). --}}
@php
    $cmConsents = app(\Webkul\Communications\Services\ConsentRegistry::class)->current($person->id);
    $cmPreferred = $person->preferred_channel;
    $cmCanEdit = bouncer()->hasPermission('contacts.persons.consent');
    $cmTones = ['granted' => 'tw-ok', 'revoked' => 'tw-overdue'];
@endphp

<div class="cm-contact flex w-full flex-col gap-3 border-b border-gray-300 p-4 dark:border-gray-800" v-pre>
    <div class="flex items-center justify-between">
        <h4 class="font-semibold dark:text-white">@lang('communications::app.contact.title')</h4>

        <a href="{{ route('admin.communications.persons.show', $person->id) }}" class="tw-meta" style="text-decoration: none;">@lang('communications::app.contact.history') →</a>
    </div>

    {{-- Preferred channel --}}
    <div>
        <span class="tw-label">@lang('communications::app.contact.preferred')</span>

        <form method="POST" action="{{ route('admin.communications.persons.preferences', $person->id) }}" class="cm-chips">
            @csrf

            @foreach (\Webkul\Communications\Services\ConsentRegistry::PREFERRED as $channel)
                <button
                    type="submit"
                    name="preferred_channel"
                    value="{{ $cmPreferred === $channel ? '' : $channel }}"
                    class="cm-chip {{ $cmPreferred === $channel ? 'is-on' : '' }}"
                    @disabled(! $cmCanEdit)
                    title="{{ $cmPreferred === $channel ? trans('communications::app.contact.clear-preferred') : trans('communications::app.contact.set-preferred') }}"
                >
                    @lang('communications::app.contact.channels.'.$channel)
                </button>
            @endforeach
        </form>
    </div>

    {{-- Consent per channel --}}
    <div>
        <span class="tw-label">@lang('communications::app.contact.consent')</span>

        <div class="cm-consents">
            @foreach ($cmConsents as $channel => $consent)
                <div class="cm-consent">
                    <span class="cm-consent-channel">@lang('communications::app.contact.channels.'.$channel)</span>

                    <span class="{{ $consent ? $cmTones[$consent->status] : 'tw-warning' }}">
                        <span class="tw-pill">{{ trans('communications::app.contact.status.'.($consent->status ?? 'unknown')) }}</span>
                    </span>

                    @if ($consent)
                        <span class="tw-meta cm-consent-meta" title="{{ $consent->note }}">
                            @lang('communications::app.contact.sources.'.$consent->source)
                            · {{ core()->formatDate($consent->created_at, 'd M Y') }}
                            @if ($consent->user)
                                · {{ $consent->user->name }}
                            @endif
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @if ($cmCanEdit)
        <details class="cm-consent-form">
            <summary class="tw-btn">@lang('communications::app.contact.record')</summary>

            <form method="POST" action="{{ route('admin.communications.persons.consent', $person->id) }}" class="mt-3 flex flex-col gap-2">
                @csrf

                <div class="grid grid-cols-2 gap-2">
                    <select name="channel" class="tw-input" aria-label="@lang('communications::app.contact.channel')">
                        @foreach (\Webkul\Communications\Services\ConsentRegistry::CHANNELS as $channel)
                            <option value="{{ $channel }}">@lang('communications::app.contact.channels.'.$channel)</option>
                        @endforeach

                        <option value="all">@lang('communications::app.contact.channels.all')</option>
                    </select>

                    <select name="status" class="tw-input" aria-label="@lang('communications::app.contact.consent')">
                        <option value="granted">@lang('communications::app.contact.grant')</option>
                        <option value="revoked">@lang('communications::app.contact.revoke')</option>
                    </select>
                </div>

                <select name="source" class="tw-input" aria-label="@lang('communications::app.contact.source')">
                    @foreach (['verbal', 'written', 'web_form', 'signed_consent', 'client_request'] as $source)
                        <option value="{{ $source }}">@lang('communications::app.contact.sources.'.$source)</option>
                    @endforeach
                </select>

                <input type="text" name="note" maxlength="500" class="tw-input" placeholder="@lang('communications::app.contact.note')">

                <button type="submit" class="tw-btn tw-btn-primary justify-center">@lang('communications::app.contact.save')</button>
            </form>

            <form method="POST" action="{{ route('admin.communications.persons.consent', $person->id) }}" class="mt-2" onsubmit="return confirm(@js(trans('communications::app.contact.do-not-contact-confirm')));">
                @csrf
                <input type="hidden" name="channel" value="all">
                <input type="hidden" name="status" value="revoked">
                <input type="hidden" name="source" value="client_request">
                <button type="submit" class="tw-btn tw-overdue w-full justify-center">@lang('communications::app.contact.do-not-contact')</button>
            </form>
        </details>
    @endif
</div>

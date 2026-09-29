<x-admin::layouts>
    <x-slot:title>
        @lang('communications::app.outcomes.title')
    </x-slot>

    @php
        $cmToneClass = ['positive' => 'tw-ok', 'neutral' => 'tw-info', 'negative' => 'tw-overdue'];
        $cmChannels = \Webkul\Communications\Services\ConsentRegistry::CHANNELS;
    @endphp

    <div class="flex flex-col gap-4" v-pre>
        <div class="flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.communications.outcomes" />

                <div class="text-xl font-bold dark:text-white">@lang('communications::app.outcomes.title')</div>
            </div>
        </div>

        <div class="tw-card tw-info p-4 text-sm text-gray-700 dark:text-gray-300">
            <p>@lang('communications::app.outcomes.info')</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="tw-card tw-info lg:col-span-2">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('communications::app.outcomes.title')</span>
                    <span class="tw-count">{{ $outcomes->where('is_active', true)->count() }}</span>
                </div>

                <div class="tw-row tw-row-head" style="grid-template-columns: 56px minmax(0, 2fr) 110px minmax(0, 1fr) auto;">
                    <span></span>
                    <span>@lang('communications::app.outcomes.name')</span>
                    <span>@lang('communications::app.outcomes.tone')</span>
                    <span>@lang('communications::app.outcomes.revokes')</span>
                    <span></span>
                </div>

                @foreach ($outcomes as $outcome)
                    <div class="tw-row" style="grid-template-columns: 56px minmax(0, 2fr) 110px minmax(0, 1fr) auto; {{ $outcome->is_active ? '' : 'opacity: 0.55;' }}">
                        <div class="flex gap-1">
                            @foreach (['up' => '↑', 'down' => '↓'] as $direction => $arrow)
                                <form method="POST" action="{{ route('admin.settings.communications.outcomes.move', $outcome->id) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                    <button type="submit" class="tw-btn" style="padding: 0 7px;" @disabled($direction === 'up' ? $loop->parent->first : $loop->parent->last) aria-label="@lang('communications::app.outcomes.move-'.$direction)">{{ $arrow }}</button>
                                </form>
                            @endforeach
                        </div>

                        <details>
                            <summary class="tw-title" style="cursor: pointer; white-space: normal;">{{ $outcome->label }}</summary>

                            <form method="POST" action="{{ route('admin.settings.communications.outcomes.update', $outcome->id) }}" class="mt-2 flex flex-col gap-2">
                                @csrf
                                @method('PUT')
                                @include('communications::settings.call-outcome-fields', ['outcome' => $outcome])
                                <button type="submit" class="tw-btn tw-btn-primary justify-center">@lang('communications::app.outcomes.save')</button>
                            </form>
                        </details>

                        <span class="{{ $cmToneClass[$outcome->tone] ?? 'tw-info' }}"><span class="tw-pill">@lang('communications::app.outcomes.tones.'.$outcome->tone)</span></span>

                        <span class="tw-meta">
                            {{ $outcome->revokes ? trans('communications::app.outcomes.revokes-'.($outcome->revokes === 'all' ? 'all' : 'channel'), ['channel' => trans('communications::app.contact.channels.'.$outcome->revokes)]) : '—' }}
                        </span>

                        <div class="tw-actions">
                            <form method="POST" action="{{ route('admin.settings.communications.outcomes.toggle', $outcome->id) }}">
                                @csrf
                                <button type="submit" class="tw-btn">{{ $outcome->is_active ? trans('communications::app.outcomes.pause') : trans('communications::app.outcomes.resume') }}</button>
                            </form>

                            @if ($outcome->agency_id !== null && ! in_array($outcome->code, $defaultCodes ?? [], true))
                                <form method="POST" action="{{ route('admin.settings.communications.outcomes.delete', $outcome->id) }}" onsubmit="return confirm(@js(trans('communications::app.outcomes.delete-confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tw-btn tw-overdue">@lang('communications::app.outcomes.delete')</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </section>

            <section class="tw-card tw-info self-start">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('communications::app.outcomes.add')</span>
                </div>

                <form method="POST" action="{{ route('admin.settings.communications.outcomes.store') }}" class="flex flex-col gap-3 p-4">
                    @csrf
                    @include('communications::settings.call-outcome-fields', ['outcome' => null])

                    @if ($errors->any())
                        <p class="text-xs text-red-600">{{ $errors->first() }}</p>
                    @endif

                    <button type="submit" class="tw-btn tw-btn-primary justify-center">@lang('communications::app.outcomes.save')</button>
                </form>
            </section>
        </div>
    </div>
</x-admin::layouts>

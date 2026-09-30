<x-admin::layouts>
    <x-slot:title>
        @lang('communications::app.templates.title')
    </x-slot>

    @php($cmChannelIcons = ['email' => '✉', 'whatsapp' => '💬', 'sms' => '📱'])

    <div class="flex flex-col gap-4" v-pre>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="communications.templates" />

                <div class="text-xl font-bold dark:text-white">@lang('communications::app.templates.title')</div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.communications.sequences.index') }}" class="tw-btn">@lang('communications::app.sequences.title')</a>
                <a href="{{ route('admin.communications.templates.create') }}" class="tw-btn tw-btn-primary">+ @lang('communications::app.templates.new')</a>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="tw-card tw-info lg:col-span-2">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('communications::app.templates.title')</span>
                    <span class="tw-count">{{ $templates->count() }}</span>
                </div>

                @forelse ($templates->groupBy('category') as $category => $group)
                    <div class="px-4 pt-3 tw-label">@lang('communications::app.templates.categories.'.$category)</div>

                    @foreach ($group as $template)
                        <a href="{{ route('admin.communications.templates.edit', $template->id) }}" class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto auto; text-decoration: none; {{ $template->is_active ? '' : 'opacity: .55;' }}">
                            <span class="min-w-0">
                                <span class="tw-title">{{ $template->name }}</span>
                                <span class="tw-meta">
                                    @foreach ($template->contents->groupBy('channel') as $channel => $versions)
                                        <span style="margin-right: 8px;">{{ $cmChannelIcons[$channel] ?? '' }} {{ $versions->pluck('locale')->map(fn ($l) => strtoupper($l))->implode('·') }}</span>
                                    @endforeach
                                    @if (! empty($usage[$template->id]))
                                        · @lang('communications::app.templates.used-in', ['count' => $usage[$template->id]])
                                    @endif
                                </span>
                            </span>

                            <span class="{{ $template->purpose === 'marketing' ? 'tw-warning' : 'tw-info' }}"><span class="tw-pill">@lang('communications::app.templates.purposes.'.$template->purpose)</span></span>
                            <span class="tw-meta">→</span>
                        </a>
                    @endforeach
                @empty
                    <p class="tw-empty">@lang('communications::app.templates.empty')</p>
                @endforelse
            </section>

            <section class="tw-card tw-ok self-start">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('communications::app.templates.settings')</span>
                </div>

                <form method="POST" action="{{ route('admin.communications.templates.settings') }}" class="flex flex-col gap-3 p-4">
                    @csrf

                    <p class="tw-meta" style="white-space: normal;">@lang('communications::app.templates.settings-info')</p>

                    @foreach (['name', 'phone', 'whatsapp', 'website', 'address'] as $key)
                        <div>
                            <label class="tw-label">@lang('communications::app.templates.agency.'.$key)</label>
                            <input type="text" name="agency[{{ $key }}]" class="tw-input" value="{{ old('agency.'.$key, $agency[$key]) }}" maxlength="{{ $key === 'address' ? 255 : 120 }}">
                        </div>
                    @endforeach

                    <div>
                        <label class="tw-label">@lang('communications::app.templates.agency.locale')</label>
                        <select name="agency[locale]" class="tw-input">
                            @foreach (\Webkul\Communications\Models\CommunicationTemplate::LOCALES as $locale)
                                <option value="{{ $locale }}" @selected(($agency['locale'] ?? 'es') === $locale)>@lang('communications::app.templates.locales.'.$locale)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="tw-label" style="margin-top: 6px;">@lang('communications::app.templates.sending')</div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="tw-meta">@lang('communications::app.templates.send-from')</label>
                            <input type="number" name="sequences[send_from]" min="0" max="23" class="tw-input" value="{{ $sending['from'] }}">
                        </div>
                        <div>
                            <label class="tw-meta">@lang('communications::app.templates.send-until')</label>
                            <input type="number" name="sequences[send_until]" min="1" max="24" class="tw-input" value="{{ $sending['until'] }}">
                        </div>
                        <div>
                            <label class="tw-meta">@lang('communications::app.templates.weekly-cap')</label>
                            <input type="number" name="sequences[weekly_cap]" min="0" max="20" class="tw-input" value="{{ $sending['cap'] }}">
                        </div>
                    </div>

                    <p class="tw-meta" style="white-space: normal;">@lang('communications::app.templates.sending-info')</p>

                    @if ($errors->any())
                        <p class="text-xs text-red-600">{{ $errors->first() }}</p>
                    @endif

                    <button type="submit" class="tw-btn tw-btn-primary justify-center">@lang('communications::app.templates.save')</button>
                </form>
            </section>
        </div>
    </div>
</x-admin::layouts>

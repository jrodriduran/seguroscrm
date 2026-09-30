<x-admin::layouts>
    <x-slot:title>
        {{ $template->exists ? $template->name : trans('communications::app.templates.new') }}
    </x-slot>

    @php
        $cmContent = fn ($channel, $locale, $field) => old("contents.{$channel}.{$locale}.{$field}", optional($template->contents?->where('channel', $channel)->firstWhere('locale', $locale))->{$field});
        $cmTabs = ['email' => '✉ Email', 'whatsapp' => '💬 WhatsApp', 'sms' => '📱 SMS'];
    @endphp

    <form method="POST" action="{{ $template->exists ? route('admin.communications.templates.update', $template->id) : route('admin.communications.templates.store') }}" class="flex flex-col gap-4" v-pre data-cm-template data-cm-sample='@json($sample)'>
        @csrf
        @if ($template->exists)
            @method('PUT')
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="communications.templates" />
                <div class="text-xl font-bold dark:text-white">{{ $template->exists ? $template->name : trans('communications::app.templates.new') }}</div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.communications.templates.index') }}" class="tw-btn">@lang('communications::app.templates.back')</a>
                <button type="submit" class="tw-btn tw-btn-primary">@lang('communications::app.templates.save')</button>
            </div>
        </div>

        @if ($errors->any())
            <div class="tw-card tw-overdue p-3 text-sm">{{ $errors->first() }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-4">
            <section class="tw-card tw-info p-4 lg:col-span-3">
                <div class="grid gap-3 md:grid-cols-3">
                    <div class="md:col-span-3">
                        <label class="tw-label">@lang('communications::app.templates.name')</label>
                        <input type="text" name="name" required maxlength="150" class="tw-input" value="{{ old('name', $template->name) }}">
                    </div>

                    <div>
                        <label class="tw-label">@lang('communications::app.templates.category')</label>
                        <select name="category" class="tw-input">
                            @foreach (\Webkul\Communications\Models\CommunicationTemplate::CATEGORIES as $category)
                                <option value="{{ $category }}" @selected(old('category', $template->category) === $category)>@lang('communications::app.templates.categories.'.$category)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="tw-label">@lang('communications::app.templates.purpose')</label>
                        <select name="purpose" class="tw-input">
                            @foreach (\Webkul\Communications\Models\CommunicationTemplate::PURPOSES as $purpose)
                                <option value="{{ $purpose }}" @selected(old('purpose', $template->purpose) === $purpose)>@lang('communications::app.templates.purposes.'.$purpose)</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="flex items-center gap-2 self-end pb-2 text-sm dark:text-gray-300">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active))>
                        @lang('communications::app.templates.active')
                    </label>
                </div>

                <p class="tw-meta mt-2" style="white-space: normal;">@lang('communications::app.templates.purpose-info')</p>

                {{-- Channel tabs --}}
                <div class="mt-4 flex flex-wrap gap-2" role="tablist">
                    @foreach ($cmTabs as $channel => $label)
                        <button type="button" class="tw-btn {{ $loop->first ? 'tw-btn-primary' : '' }}" data-cm-tab="{{ $channel }}">{{ $label }}</button>
                    @endforeach
                </div>

                @foreach ($cmTabs as $channel => $label)
                    <div class="mt-3 grid gap-4 md:grid-cols-2" data-cm-panel="{{ $channel }}" @if (! $loop->first) hidden @endif>
                        @foreach (\Webkul\Communications\Models\CommunicationTemplate::LOCALES as $locale)
                            <div class="flex flex-col gap-2">
                                <span class="tw-label">@lang('communications::app.templates.locales.'.$locale)</span>

                                @if ($channel === 'email')
                                    <input type="text" name="contents[{{ $channel }}][{{ $locale }}][subject]" maxlength="200" class="tw-input" placeholder="@lang('communications::app.templates.subject')" value="{{ $cmContent($channel, $locale, 'subject') }}" data-cm-field>
                                @endif

                                <textarea name="contents[{{ $channel }}][{{ $locale }}][body]" rows="{{ $channel === 'email' ? 10 : 5 }}" maxlength="5000" class="tw-input" placeholder="@lang('communications::app.templates.body-placeholder')" data-cm-field data-cm-channel="{{ $channel }}">{{ $cmContent($channel, $locale, 'body') }}</textarea>

                                @if ($channel !== 'email')
                                    <span class="tw-meta" data-cm-count></span>
                                @endif

                                <div class="cm-preview" data-cm-preview></div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <p class="tw-meta mt-3" style="white-space: normal;">@lang('communications::app.templates.channel-info')</p>
            </section>

            {{-- Variables --}}
            <aside class="flex flex-col gap-4">
                <section class="tw-card tw-ok p-4">
                    <span class="tw-label">@lang('communications::app.templates.variables')</span>
                    <p class="tw-meta mb-2" style="white-space: normal;">@lang('communications::app.templates.variables-info')</p>

                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($variables as $variable)
                            <button type="button" class="cm-chip" data-cm-variable="{{ '{'.$variable.'}' }}" title="{{ $sample[$variable] ?? '' }}">@lang('communications::app.templates.vars.'.$variable)</button>
                        @endforeach
                    </div>
                </section>

                <section class="tw-card tw-info p-4 text-sm">
                    <span class="tw-label">@lang('communications::app.templates.format')</span>
                    <p class="tw-meta" style="white-space: normal;">@lang('communications::app.templates.format-info')</p>
                </section>

                @if ($template->exists)
                    <button type="submit" form="cm-delete-template" class="tw-btn tw-overdue justify-center">@lang('communications::app.templates.delete')</button>
                @endif
            </aside>
        </div>
    </form>

    @if ($template->exists)
        <form id="cm-delete-template" method="POST" action="{{ route('admin.communications.templates.delete', $template->id) }}" onsubmit="return confirm(@js(trans('communications::app.templates.delete-confirm')));">
            @csrf
            @method('DELETE')
        </form>
    @endif

    @pushOnce('scripts')
        <script>
            /**
             * Template editor: channel tabs, variable chips inserted at the
             * cursor, live preview with sample data and SMS length.
             */
            (function () {
                var lastField = null;

                function form() { return document.querySelector('[data-cm-template]'); }

                function sample() {
                    try { return JSON.parse(form().getAttribute('data-cm-sample')) || {}; } catch (e) { return {}; }
                }

                function escapeHtml(text) {
                    return text.replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
                }

                function fill(text) {
                    var vars = sample();

                    return text.replace(/\{([a-z_]+)\}/g, function (all, key) { return key in vars ? vars[key] : all; });
                }

                function preview(textarea) {
                    var box = textarea.parentElement.querySelector('[data-cm-preview]');
                    var count = textarea.parentElement.querySelector('[data-cm-count]');
                    var text = fill(textarea.value || '');

                    if (count) {
                        var parts = Math.max(1, Math.ceil(text.length / 160));
                        count.textContent = text.length + ' / 160' + (text.length > 160 ? ' · ' + parts + ' SMS' : '');
                    }

                    if (! box) return;

                    if (! text.trim()) { box.innerHTML = ''; return; }

                    var html = escapeHtml(text)
                        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
                        .replace(/\n/g, '<br>');

                    box.innerHTML = html;
                }

                document.addEventListener('focusin', function (event) {
                    if (event.target.matches && event.target.matches('[data-cm-field]')) lastField = event.target;
                });

                document.addEventListener('input', function (event) {
                    if (event.target.matches && event.target.matches('textarea[data-cm-field]')) preview(event.target);
                });

                document.addEventListener('click', function (event) {
                    var tab = event.target.closest && event.target.closest('[data-cm-tab]');

                    if (tab) {
                        document.querySelectorAll('[data-cm-tab]').forEach(function (b) { b.classList.toggle('tw-btn-primary', b === tab); });
                        document.querySelectorAll('[data-cm-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-cm-panel') !== tab.getAttribute('data-cm-tab'); });

                        return;
                    }

                    var chip = event.target.closest && event.target.closest('[data-cm-variable]');

                    if (chip && lastField) {
                        var value = chip.getAttribute('data-cm-variable');
                        var start = lastField.selectionStart || 0;
                        var end = lastField.selectionEnd || 0;

                        lastField.value = lastField.value.slice(0, start) + value + lastField.value.slice(end);
                        lastField.focus();
                        lastField.selectionStart = lastField.selectionEnd = start + value.length;
                        lastField.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });

                function init() { document.querySelectorAll('textarea[data-cm-field]').forEach(preview); }

                window.addEventListener('load', function () { setTimeout(init, 50); });
            })();
        </script>
    @endPushOnce
</x-admin::layouts>

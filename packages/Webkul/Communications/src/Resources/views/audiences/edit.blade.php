<x-admin::layouts>
    <x-slot:title>
        {{ $audience->exists ? $audience->name : trans('communications::app.audiences.new') }}
    </x-slot>

    @php
        $cmRows = array_values($rules ?: []);
        $cmRows[] = ['field' => '', 'value' => ''];
        $cmMonths = collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F')]);
        $cmControl = function ($kind, $field, $row) {
            $current = $row['field'] ?? '';
            $kindOf = \Webkul\Communications\Services\AudienceQuery::FIELDS[$current] ?? null;

            return $kind === 'select' ? $current === $field : ($kindOf === $kind && $kindOf !== 'select');
        };
    @endphp

    <div class="flex flex-col gap-4" v-pre>
        <form method="POST" action="{{ $audience->exists ? route('admin.communications.audiences.update', $audience->id) : route('admin.communications.audiences.store') }}" class="flex flex-col gap-4" data-cm-audience>
            @csrf
            @if ($audience->exists)
                @method('PUT')
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-1">
                    <x-admin::breadcrumbs name="communications.audiences" />
                    <div class="text-xl font-bold dark:text-white">{{ $audience->exists ? $audience->name : trans('communications::app.audiences.new') }}</div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('admin.communications.audiences.index') }}" class="tw-btn">@lang('communications::app.templates.back')</a>
                    @if ($audience->exists)
                        <a href="{{ route('admin.communications.audiences.export', $audience->id) }}" class="tw-btn">⬇ CSV</a>
                        <a href="{{ route('admin.communications.campaigns.create', ['audience' => $audience->id]) }}" class="tw-btn">✉ @lang('communications::app.audiences.new-campaign')</a>
                    @endif
                    <button type="submit" class="tw-btn tw-btn-primary">@lang('communications::app.audiences.save-preview')</button>
                </div>
            </div>

            @if ($errors->any())
                <div class="tw-card tw-overdue p-3 text-sm">{{ $errors->first() }}</div>
            @endif

            <section class="tw-card tw-info p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="tw-label">@lang('communications::app.audiences.name')</label>
                        <input type="text" name="name" required maxlength="150" class="tw-input" value="{{ old('name', $audience->name) }}">
                    </div>
                    <div>
                        <label class="tw-label">@lang('communications::app.audiences.description')</label>
                        <input type="text" name="description" maxlength="500" class="tw-input" value="{{ old('description', $audience->description) }}">
                    </div>
                </div>

                <span class="tw-label mt-4">@lang('communications::app.audiences.conditions')</span>
                <p class="tw-meta mb-2" style="white-space: normal;">@lang('communications::app.audiences.conditions-info')</p>

                <div class="flex flex-col gap-2" data-cm-rules>
                    @foreach ($cmRows as $i => $row)
                        <div class="cm-rule-row" data-cm-rule>
                            <select name="rules[{{ $i }}][field]" class="tw-input" data-cm-field>
                                <option value="">— @lang('communications::app.audiences.choose-field') —</option>
                                @foreach ($fields as $field => $kind)
                                    <option value="{{ $field }}" data-kind="{{ $kind }}" @selected(($row['field'] ?? '') === $field)>@lang('communications::app.audiences.fields.'.$field)</option>
                                @endforeach
                            </select>

                            @foreach ($options as $field => $values)
                                <select name="rules[{{ $i }}][value]" class="tw-input" data-cm-value data-field="{{ $field }}" @disabled(! $cmControl('select', $field, $row)) @if (! $cmControl('select', $field, $row)) hidden @endif>
                                    @foreach ($values as $value)
                                        <option value="{{ $value }}" @selected(($row['value'] ?? '') === $value)>{{ $value }}</option>
                                    @endforeach
                                </select>
                            @endforeach

                            @foreach (['number' => null, 'text' => null] as $kind => $unused)
                                <input type="{{ $kind }}" name="rules[{{ $i }}][value]" class="tw-input" data-cm-value data-kind="{{ $kind }}" value="{{ $cmControl($kind, null, $row) ? ($row['value'] ?? '') : '' }}" @disabled(! $cmControl($kind, null, $row)) @if (! $cmControl($kind, null, $row)) hidden @endif @if ($kind === 'number') min="0" max="120" @else maxlength="120" @endif>
                            @endforeach

                            @php
                                $cmSelects = [
                                    'month' => ['current' => trans('communications::app.audiences.this-month')] + $cmMonths->all(),
                                    'yesno' => ['yes' => trans('communications::app.audiences.yes'), 'no' => trans('communications::app.audiences.no')],
                                    'tag' => $tags->all(),
                                    'user' => $users->all(),
                                    'stage' => $stages->all(),
                                    'policy_status' => collect(\Webkul\Communications\Services\AudienceQuery::POLICY_STATUSES)->mapWithKeys(fn ($s) => [$s => trans('communications::app.triggers.statuses.'.$s)])->all(),
                                    'channel' => collect(\Webkul\Communications\Services\ConsentRegistry::PREFERRED)->mapWithKeys(fn ($c) => [$c => trans('communications::app.contact.channels.'.$c)])->all(),
                                    'consent_channel' => collect(\Webkul\Communications\Services\ConsentRegistry::CHANNELS)->mapWithKeys(fn ($c) => [$c => trans('communications::app.contact.channels.'.$c)])->all(),
                                ];
                            @endphp

                            @foreach ($cmSelects as $kind => $choices)
                                <select name="rules[{{ $i }}][value]" class="tw-input" data-cm-value data-kind="{{ $kind }}" @disabled(! $cmControl($kind, null, $row)) @if (! $cmControl($kind, null, $row)) hidden @endif>
                                    @foreach ($choices as $value => $label)
                                        <option value="{{ $value }}" @selected((string) ($row['value'] ?? '') === (string) $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @endforeach

                            <button type="button" class="tw-btn" data-cm-rule-remove title="@lang('communications::app.sequences.remove')">✕</button>
                        </div>
                    @endforeach
                </div>

                <button type="button" class="tw-btn mt-2" data-cm-rule-add>+ @lang('communications::app.audiences.add-condition')</button>
            </section>
        </form>

        {{-- Preview --}}
        <section class="tw-card tw-ok">
            <div class="tw-card-head">
                <span class="tw-card-title">@lang('communications::app.audiences.preview')</span>
                <span class="tw-ok"><span class="tw-pill">@lang('communications::app.audiences.count', ['count' => $total])</span></span>
            </div>

            @forelse ($sample as $person)
                <a href="{{ route('admin.contacts.persons.view', $person->id) }}" class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto auto; text-decoration: none;">
                    <span class="tw-title">{{ $person->name }}</span>
                    <span class="tw-meta">{{ collect(json_decode((string) $person->emails, true))->pluck('value')->filter()->first() }}</span>
                    <span class="tw-meta">{{ $person->preferred_channel ? trans('communications::app.contact.channels.'.$person->preferred_channel) : '' }}</span>
                </a>
            @empty
                <p class="tw-empty">@lang('communications::app.audiences.nobody')</p>
            @endforelse

            @if ($total > $sample->count())
                <p class="tw-meta px-4 pb-3">@lang('communications::app.audiences.and-more', ['count' => $total - $sample->count()])</p>
            @endif
        </section>

        @if ($audience->exists)
            <form method="POST" action="{{ route('admin.communications.audiences.delete', $audience->id) }}" onsubmit="return confirm(@js(trans('communications::app.audiences.delete-confirm')));">
                @csrf
                @method('DELETE')
                <button type="submit" class="tw-btn tw-overdue">@lang('communications::app.audiences.delete')</button>
            </form>
        @endif
    </div>

    @pushOnce('scripts')
        <script>
            /**
             * Condition rows: each field shows the right value control
             * (hidden ones are disabled so only one value is sent).
             */
            (function () {
                function sync(row) {
                    var select = row.querySelector('[data-cm-field]');
                    var field = select.value;
                    var option = select.options[select.selectedIndex];
                    var kind = option ? option.getAttribute('data-kind') : null;

                    row.querySelectorAll('[data-cm-value]').forEach(function (control) {
                        var active = control.hasAttribute('data-field') ? control.getAttribute('data-field') === field : (kind !== 'select' && control.getAttribute('data-kind') === kind);
                        control.hidden = ! active;
                        control.disabled = ! active;
                    });
                }

                function reindex(list) {
                    list.querySelectorAll('[data-cm-rule]').forEach(function (row, index) {
                        row.querySelectorAll('[name]').forEach(function (f) { f.name = f.name.replace(/^rules\[\d+\]/, 'rules[' + index + ']'); });
                    });
                }

                document.addEventListener('change', function (e) {
                    if (e.target.matches && e.target.matches('[data-cm-field]')) sync(e.target.closest('[data-cm-rule]'));
                });

                document.addEventListener('click', function (e) {
                    var add = e.target.closest && e.target.closest('[data-cm-rule-add]');

                    if (add) {
                        var list = document.querySelector('[data-cm-rules]');
                        var rows = list.querySelectorAll('[data-cm-rule]');
                        var clone = rows[rows.length - 1].cloneNode(true);

                        clone.querySelector('[data-cm-field]').value = '';
                        clone.querySelectorAll('input').forEach(function (f) { f.value = ''; });
                        list.appendChild(clone);
                        reindex(list);
                        sync(clone);

                        return;
                    }

                    var remove = e.target.closest && e.target.closest('[data-cm-rule-remove]');

                    if (remove) {
                        var row = remove.closest('[data-cm-rule]');
                        var parent = row.parentElement;

                        if (parent.querySelectorAll('[data-cm-rule]').length > 1) {
                            row.remove();
                        } else {
                            row.querySelector('[data-cm-field]').value = '';
                            sync(row);
                        }

                        reindex(parent);
                    }
                });
            })();
        </script>
    @endPushOnce
</x-admin::layouts>

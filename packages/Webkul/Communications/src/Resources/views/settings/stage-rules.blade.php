<x-admin::layouts>
    <x-slot:title>
        @lang('communications::app.stages.title')
    </x-slot>

    <div class="flex flex-col gap-4" v-pre>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="settings.communications.stages" />
                <div class="text-xl font-bold dark:text-white">@lang('communications::app.stages.title')</div>
            </div>

            <a href="{{ route('admin.communications.sequences.index') }}" class="tw-btn tw-btn-primary">@lang('communications::app.sequences.title')</a>
        </div>

        <div class="tw-card tw-info p-4 text-sm text-gray-700 dark:text-gray-300">
            <p>@lang('communications::app.stages.info')</p>
        </div>

        @if ($errors->any())
            <div class="tw-card tw-overdue p-3 text-sm">{{ $errors->first() }}</div>
        @endif

        @if ($sequences->isEmpty())
            <div class="tw-card tw-warning p-4 text-sm">
                @lang('communications::app.stages.no-sequences')
                <a href="{{ route('admin.communications.sequences.create') }}" class="tw-btn tw-btn-primary ml-2">+ @lang('communications::app.sequences.new')</a>
            </div>
        @endif

        @foreach ($pipelines as $pipeline)
            <section class="tw-card tw-info">
                <div class="tw-card-head">
                    <span class="tw-card-title">{{ $pipeline->name }}</span>
                    <span class="tw-count">{{ $pipeline->stages->sum(fn ($stage) => count($rules[$stage->id] ?? [])) }}</span>
                </div>

                @foreach ($pipeline->stages as $stage)
                    <div class="tw-row" style="grid-template-columns: 170px minmax(0, 1fr) minmax(0, 1.2fr); align-items: start;">
                        <span class="tw-title" style="white-space: normal;">{{ $stage->name }}</span>

                        <div class="flex flex-wrap gap-1.5">
                            @forelse ($rules[$stage->id] ?? [] as $rule)
                                <form method="POST" action="{{ route('admin.settings.communications.stages.delete', $rule->id) }}" class="cm-rule {{ $rule->sequence?->is_active ? '' : 'is-off' }}" onsubmit="return confirm(@js(trans('communications::app.stages.delete-confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <span>{{ $rule->event === 'stage_idle' ? trans('communications::app.stages.after-days', ['days' => $rule->option('days')]) : trans('communications::app.stages.on-enter') }}</span>
                                    <a href="{{ route('admin.communications.sequences.edit', $rule->sequence_id) }}"><strong>{{ $rule->sequence?->name }}</strong></a>
                                    @unless ($rule->sequence?->is_active)
                                        <em>(@lang('communications::app.sequences.is-paused'))</em>
                                    @endunless
                                    <button type="submit" title="@lang('communications::app.sequences.remove')">✕</button>
                                </form>
                            @empty
                                <span class="tw-meta">@lang('communications::app.stages.nothing')</span>
                            @endforelse
                        </div>

                        @if ($sequences->isNotEmpty())
                            <form method="POST" action="{{ route('admin.settings.communications.stages.store') }}" class="flex flex-wrap items-center gap-1.5">
                                @csrf
                                <input type="hidden" name="stage_id" value="{{ $stage->id }}">

                                <select name="moment" class="tw-input" style="width: auto;" onchange="this.form.querySelector('[name=days]').hidden = this.value !== 'stage_idle'">
                                    <option value="stage_entered">@lang('communications::app.stages.on-enter')</option>
                                    <option value="stage_idle">@lang('communications::app.stages.if-stays')</option>
                                </select>

                                <input type="number" name="days" min="1" max="365" value="3" class="tw-input" style="width: 70px;" hidden title="@lang('communications::app.stages.days')">

                                <select name="sequence_id" class="tw-input" style="width: auto; max-width: 220px;">
                                    @foreach ($sequences as $sequence)
                                        <option value="{{ $sequence->id }}">{{ $sequence->name }}{{ $sequence->is_active ? '' : ' ('.trans('communications::app.sequences.is-paused').')' }}</option>
                                    @endforeach
                                </select>

                                <button type="submit" class="tw-btn">+ @lang('communications::app.stages.assign')</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </section>
        @endforeach
    </div>
</x-admin::layouts>

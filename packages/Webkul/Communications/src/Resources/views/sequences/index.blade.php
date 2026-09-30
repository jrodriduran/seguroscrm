<x-admin::layouts>
    <x-slot:title>
        @lang('communications::app.sequences.title')
    </x-slot>

    <div class="flex flex-col gap-4" v-pre>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="communications.sequences" />
                <div class="text-xl font-bold dark:text-white">@lang('communications::app.sequences.title')</div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.settings.communications.stages.index') }}" class="tw-btn">@lang('communications::app.stages.title')</a>
                <a href="{{ route('admin.communications.templates.index') }}" class="tw-btn">@lang('communications::app.templates.title')</a>
                <a href="{{ route('admin.communications.sequences.create') }}" class="tw-btn tw-btn-primary">+ @lang('communications::app.sequences.new')</a>
            </div>
        </div>

        <div class="tw-card tw-info p-4 text-sm text-gray-700 dark:text-gray-300">
            <p>@lang('communications::app.sequences.info')</p>
        </div>

        <section class="tw-card tw-info">
            <div class="tw-card-head">
                <span class="tw-card-title">@lang('communications::app.sequences.title')</span>
                <span class="tw-count">{{ $sequences->count() }}</span>
            </div>

            @forelse ($sequences as $sequence)
                <div class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto auto auto; {{ $sequence->is_active ? '' : 'opacity: .6;' }}">
                    <a href="{{ route('admin.communications.sequences.edit', $sequence->id) }}" class="min-w-0" style="text-decoration: none;">
                        <span class="tw-title">{{ $sequence->name }}</span>
                        <span class="tw-meta" style="white-space: normal;">
                            @forelse ($sequence->triggers as $trigger)
                                @include('communications::sequences.trigger-label', ['trigger' => $trigger, 'stages' => $stages])@if (! $loop->last) · @endif
                            @empty
                                @lang('communications::app.sequences.no-trigger')
                            @endforelse
                        </span>
                    </a>

                    <span class="tw-meta">@lang('communications::app.sequences.steps-count', ['count' => $sequence->steps_count])</span>
                    <span class="tw-meta">@lang('communications::app.sequences.active-count', ['count' => $sequence->active_count])</span>

                    <form method="POST" action="{{ route('admin.communications.sequences.toggle', $sequence->id) }}">
                        @csrf
                        <button type="submit" class="tw-btn {{ $sequence->is_active ? '' : 'tw-btn-primary' }}">{{ $sequence->is_active ? trans('communications::app.sequences.pause') : trans('communications::app.sequences.activate') }}</button>
                    </form>
                </div>
            @empty
                <p class="tw-empty">@lang('communications::app.sequences.empty')</p>
            @endforelse
        </section>
    </div>
</x-admin::layouts>

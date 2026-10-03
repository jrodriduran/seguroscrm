<x-admin::layouts>
    <x-slot:title>
        @lang('communications::app.audiences.title')
    </x-slot>

    <div class="flex flex-col gap-4" v-pre>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="communications.audiences" />
                <div class="text-xl font-bold dark:text-white">@lang('communications::app.audiences.title')</div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.communications.campaigns.index') }}" class="tw-btn">@lang('communications::app.campaigns.title')</a>
                <a href="{{ route('admin.communications.audiences.create') }}" class="tw-btn tw-btn-primary">+ @lang('communications::app.audiences.new')</a>
            </div>
        </div>

        <div class="tw-card tw-info p-4 text-sm text-gray-700 dark:text-gray-300">
            <p>@lang('communications::app.audiences.info')</p>
        </div>

        <section class="tw-card tw-info">
            <div class="tw-card-head">
                <span class="tw-card-title">@lang('communications::app.audiences.title')</span>
                <span class="tw-count">{{ $audiences->count() }}</span>
            </div>

            @forelse ($audiences as $audience)
                <a href="{{ route('admin.communications.audiences.edit', $audience->id) }}" class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto; text-decoration: none;">
                    <span class="min-w-0">
                        <span class="tw-title">{{ $audience->name }}</span>
                        <span class="tw-meta" style="white-space: normal;">
                            @forelse ($audience->rules ?? [] as $rule)
                                @include('communications::audiences.rule-label', ['rule' => $rule])@if (! $loop->last) · @endif
                            @empty
                                @lang('communications::app.audiences.everyone')
                            @endforelse
                        </span>
                    </span>
                    <span class="tw-info"><span class="tw-pill">@lang('communications::app.audiences.count', ['count' => $audience->total])</span></span>
                </a>
            @empty
                <p class="tw-empty">@lang('communications::app.audiences.empty')</p>
            @endforelse
        </section>
    </div>
</x-admin::layouts>

<x-admin::layouts>
    <x-slot:title>
        @lang('teamwork::app.follow-up.show.title') · {{ $followUp->title }}
    </x-slot>

    @php
        $me = auth()->guard('user')->id();
        $canClose = in_array($me, [$followUp->assigned_to, $followUp->created_by], true) || app(\Webkul\Teamwork\Services\TeamScope::class)->isMasterAgent(auth()->guard('user')->user());
        $stateClass = $followUp->isOpen() ? 'tw-'.($followUp->isUrgent() ? 'urgent' : $followUp->state) : 'tw-ok';
    @endphp

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex min-w-0 flex-col gap-1">
                <x-admin::breadcrumbs name="teamwork.follow_up" :entity="$followUp" />

                <div class="flex flex-wrap items-center gap-2 text-xl font-bold dark:text-white">
                    @if ($followUp->isUrgent())
                        <span class="tw-urgent"><span class="tw-pill">@lang('teamwork::app.follow-up.urgent')</span></span>
                    @endif

                    <span class="truncate">{{ $followUp->title }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if ($followUp->url)
                    <a href="{{ $followUp->url }}" class="tw-btn">@lang('teamwork::app.follow-up.show.go-to-record')</a>
                @endif

                @if (! $followUp->isOpen())
                    <form method="POST" action="{{ route('admin.teamwork.follow_ups.reopen', $followUp->id) }}">
                        @csrf
                        <button type="submit" class="tw-btn">@lang('teamwork::app.follow-up.show.reopen')</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Thread --}}
            <section class="tw-card tw-info lg:col-span-2">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('teamwork::app.follow-up.show.thread')</span>
                    <span class="tw-count">{{ $followUp->comments->count() }}</span>
                </div>

                <div class="flex flex-col gap-3 p-4">
                    @if ($followUp->note)
                        <div class="tw-msg {{ $followUp->created_by === $me ? 'is-mine' : '' }}">
                            <span class="tw-avatar">{{ mb_substr($followUp->creator?->name ?? '?', 0, 1) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="mb-1 text-xs text-gray-500">{{ $followUp->creator?->name }} · {{ core()->formatDate($followUp->created_at, 'd M H:i') }}</p>
                                <div class="tw-bubble">{{ app(\Webkul\Teamwork\Services\Mentions::class)->render($followUp->note) }}</div>
                            </div>
                        </div>
                    @endif

                    @forelse ($followUp->comments as $comment)
                        <div class="tw-msg {{ $comment->user_id === $me ? 'is-mine' : '' }}">
                            <span class="tw-avatar">{{ mb_substr($comment->user?->name ?? '?', 0, 1) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="mb-1 text-xs text-gray-500">{{ $comment->user?->name }} · {{ core()->formatDate($comment->created_at, 'd M H:i') }}</p>
                                <div class="tw-bubble">{{ app(\Webkul\Teamwork\Services\Mentions::class)->render($comment->body) }}</div>
                            </div>
                        </div>
                    @empty
                        @unless ($followUp->note)
                            <p class="tw-empty">@lang('teamwork::app.follow-up.show.empty-thread')</p>
                        @endunless
                    @endforelse

                    <form method="POST" action="{{ route('admin.teamwork.follow_ups.comment', $followUp->id) }}" class="mt-2 flex flex-col gap-2">
                        @csrf

                        <textarea data-tw-mentions name="body" rows="3" required maxlength="4000" class="tw-input" placeholder="@lang('teamwork::app.follow-up.show.comment')"></textarea>

                        @error('body')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex justify-end">
                            <button type="submit" class="tw-btn tw-btn-primary">@lang('teamwork::app.follow-up.show.send')</button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- Details --}}
            <div class="flex flex-col gap-4">
                <section class="tw-card {{ $stateClass }}">
                    <div class="tw-card-head">
                        <span class="tw-card-title">@lang('teamwork::app.follow-up.show.status')</span>

                        @if ($followUp->isOpen())
                            <span class="tw-pill">@lang('teamwork::app.states.'.$followUp->state)</span>
                        @else
                            <span class="tw-pill">@lang('teamwork::app.follow-up.show.done')</span>
                        @endif
                    </div>

                    <dl class="grid gap-3 p-4 text-sm">
                        <div>
                            <dt class="tw-meta">@lang('teamwork::app.follow-up.show.record')</dt>
                            <dd class="font-medium text-gray-800 dark:text-white">@lang('teamwork::app.entities.'.$followUp->entity_type) · {{ $followUp->title }}</dd>
                        </div>

                        <div>
                            <dt class="tw-meta">@lang('teamwork::app.follow-up.show.assigned-to')</dt>
                            <dd class="font-medium text-gray-800 dark:text-white">{{ $followUp->assignee?->name }}</dd>
                        </div>

                        <div>
                            <dt class="tw-meta">@lang('teamwork::app.follow-up.show.created-by')</dt>
                            <dd class="font-medium text-gray-800 dark:text-white">{{ $followUp->creator?->name }} · {{ core()->formatDate($followUp->created_at, 'd M Y H:i') }}</dd>
                        </div>

                        @if ($followUp->due_at)
                            <div>
                                <dt class="tw-meta">@lang('teamwork::app.follow-up.show.due')</dt>
                                <dd class="font-medium text-gray-800 dark:text-white">{{ core()->formatDate($followUp->due_at, 'd M Y H:i') }}</dd>
                            </div>
                        @endif

                        @unless ($followUp->isOpen())
                            <p class="tw-meta">@lang('teamwork::app.follow-up.show.closed-by', ['name' => $followUp->resolver?->name ?? '—', 'date' => core()->formatDate($followUp->resolved_at, 'd M Y H:i')])</p>
                        @endunless
                    </dl>

                    @if ($followUp->isOpen() && $canClose)
                        <form method="POST" action="{{ route('admin.teamwork.follow_ups.resolve', $followUp->id) }}" class="flex flex-col gap-2 border-t p-4 dark:border-gray-800">
                            @csrf

                            <textarea name="body" rows="2" maxlength="4000" class="tw-input" placeholder="@lang('teamwork::app.follow-up.show.resolve-note')"></textarea>

                            <button type="submit" class="tw-btn tw-ok justify-center">@lang('teamwork::app.follow-up.show.resolve')</button>
                        </form>
                    @endif
                </section>

                @if ($history->count())
                    <section class="tw-card tw-info">
                        <div class="tw-card-head">
                            <span class="tw-card-title">@lang('teamwork::app.follow-up.show.history')</span>
                        </div>

                        @foreach ($history as $item)
                            <a href="{{ route('admin.teamwork.follow_ups.show', $item->id) }}" class="tw-row" style="grid-template-columns: 1fr auto; text-decoration: none;">
                                <span class="tw-meta" style="color: inherit;">{{ $item->assignee?->name }} · {{ core()->formatDate($item->created_at, 'd M Y') }}</span>
                                <span class="{{ $item->isOpen() ? 'tw-warning' : 'tw-ok' }}"><span class="tw-pill">{{ $item->isOpen() ? trans('teamwork::app.follow-up.show.open') : trans('teamwork::app.follow-up.show.done') }}</span></span>
                            </a>
                        @endforeach
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-admin::layouts>

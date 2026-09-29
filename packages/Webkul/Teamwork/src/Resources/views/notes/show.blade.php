<x-admin::layouts>
    <x-slot:title>
        @lang('teamwork::app.notes.title') · {{ $note->title }}
    </x-slot>

    @php
        $me = auth()->guard('user')->id();
        $isRecipient = $note->to_user_id === $me;
    @endphp

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex min-w-0 flex-col gap-1">
                <x-admin::breadcrumbs name="teamwork.center" />

                <div class="flex flex-wrap items-center gap-2 text-xl font-bold dark:text-white">
                    <span class="tw-info"><span class="tw-pill">@lang('teamwork::app.notes.title')</span></span>
                    <span class="truncate">{{ $note->title }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.teamwork.records.show', [$note->entity_type, $note->entity_id]) }}" class="tw-btn">@lang('teamwork::app.records.team-activity')</a>

                @if ($note->url)
                    <a href="{{ $note->url }}" class="tw-btn">@lang('teamwork::app.follow-up.show.go-to-record')</a>
                @endif
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Conversation --}}
            <section class="tw-card tw-info lg:col-span-2">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('teamwork::app.follow-up.show.thread')</span>
                    <span class="tw-count">{{ $thread->count() }}</span>
                </div>

                <div class="flex flex-col gap-3 p-4">
                    @foreach ($thread as $message)
                        <div class="tw-msg {{ $message->from_user_id === $me ? 'is-mine' : '' }}">
                            <span class="tw-avatar">{{ mb_substr($message->sender?->name ?? '?', 0, 1) }}</span>

                            <div class="min-w-0 flex-1">
                                <p class="mb-1 text-xs text-gray-500">
                                    {{ $message->sender?->name }} → {{ $message->recipient?->name }} · {{ core()->formatDate($message->created_at, 'd M H:i') }}

                                    {{-- Read receipt --}}
                                    @if ($message->read_at)
                                        <span class="tw-ok" title="{{ core()->formatDate($message->read_at, 'd M Y H:i') }}"><span class="tw-pill" style="margin-left: 6px;">✓✓ @lang('teamwork::app.notes.read', ['date' => core()->formatDate($message->read_at, 'd M H:i')])</span></span>
                                    @else
                                        <span class="tw-warning"><span class="tw-pill" style="margin-left: 6px;">✓ @lang('teamwork::app.notes.unread')</span></span>
                                    @endif
                                </p>

                                <div class="tw-bubble">{{ $message->body }}</div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Reply to whoever wrote the note --}}
                    <form method="POST" action="{{ route('admin.teamwork.notes.store') }}" class="mt-2 flex flex-col gap-2">
                        @csrf

                        <input type="hidden" name="entity_type" value="{{ $note->entity_type }}">
                        <input type="hidden" name="entity_id" value="{{ $note->entity_id }}">
                        <input type="hidden" name="reply_to_id" value="{{ $rootId }}">
                        <input type="hidden" name="to_user_id" value="{{ $note->from_user_id === $me ? $note->to_user_id : $note->from_user_id }}">

                        <textarea name="body" rows="3" required maxlength="4000" class="tw-input" placeholder="@lang('teamwork::app.notes.reply-placeholder')"></textarea>

                        <div class="flex justify-end">
                            <button type="submit" class="tw-btn tw-btn-primary">@lang('teamwork::app.notes.reply')</button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- What to do with it --}}
            <div class="flex flex-col gap-4">
                @if ($isRecipient)
                    <section class="tw-card tw-info">
                        <div class="tw-card-head">
                            <span class="tw-card-title">@lang('teamwork::app.notes.what-next')</span>
                        </div>

                        <div class="flex flex-col gap-3 p-4 text-sm text-gray-700 dark:text-gray-300">
                            @if ($note->follow_up_id)
                                <p>@lang('teamwork::app.notes.already-converted')</p>

                                <a href="{{ route('admin.teamwork.follow_ups.show', $note->follow_up_id) }}" class="tw-btn tw-btn-primary justify-center">@lang('teamwork::app.center.actions.thread')</a>
                            @else
                                <form method="POST" action="{{ route('admin.teamwork.notes.convert', $note->id) }}" class="flex flex-col gap-2">
                                    @csrf

                                    <label class="tw-label" for="tw-convert-due">@lang('teamwork::app.follow-up.due')</label>
                                    <input id="tw-convert-due" type="datetime-local" name="due_at" class="tw-input">

                                    <button type="submit" class="tw-btn tw-btn-primary justify-center">@lang('teamwork::app.notes.convert')</button>
                                </form>

                                <a href="{{ route('admin.teamwork.center') }}" class="tw-btn tw-ok justify-center">@lang('teamwork::app.notes.just-note')</a>

                                <p class="tw-meta" style="white-space: normal;">@lang('teamwork::app.notes.what-next-info')</p>
                            @endif
                        </div>
                    </section>
                @endif

                <section class="tw-card {{ $note->read_at ? 'tw-ok' : 'tw-warning' }}">
                    <div class="tw-card-head">
                        <span class="tw-card-title">@lang('teamwork::app.follow-up.show.status')</span>
                        <span class="tw-pill">{{ $note->read_at ? trans('teamwork::app.notes.read', ['date' => core()->formatDate($note->read_at, 'd M H:i')]) : trans('teamwork::app.notes.unread') }}</span>
                    </div>

                    <dl class="grid gap-3 p-4 text-sm">
                        <div>
                            <dt class="tw-meta">@lang('teamwork::app.follow-up.show.record')</dt>
                            <dd class="font-medium text-gray-800 dark:text-white">@lang('teamwork::app.entities.'.$note->entity_type) · {{ $note->title }}</dd>
                        </div>
                        <div>
                            <dt class="tw-meta">@lang('teamwork::app.notes.from')</dt>
                            <dd class="font-medium text-gray-800 dark:text-white">{{ $note->sender?->name }}</dd>
                        </div>
                        <div>
                            <dt class="tw-meta">@lang('teamwork::app.notes.to')</dt>
                            <dd class="font-medium text-gray-800 dark:text-white">{{ $note->recipient?->name }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>
    </div>
</x-admin::layouts>

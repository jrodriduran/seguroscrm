{{-- "Send a note to a teammate" dialog for a record; opened by [data-tw-note]. --}}
@php
    $twUser = auth()->guard('user')->user();
    $twMembers = app(\Webkul\Teamwork\Services\TeamScope::class)->assignableUsers($twUser)->where('id', '!=', $twUser->id);
@endphp

<div id="tw-note" class="tw-modal" role="dialog" aria-modal="true">
    <div class="tw-modal-panel">
        <form method="POST" action="{{ route('admin.teamwork.notes.store') }}">
            @csrf

            <input type="hidden" name="entity_type" value="{{ $entityType }}">
            <input type="hidden" name="entity_id" value="{{ $entityId }}">

            <div class="tw-modal-head">
                <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('teamwork::app.notes.modal-title')</p>

                @isset($recordTitle)
                    <p class="mt-0.5 truncate text-xs text-gray-500">{{ $recordTitle }}</p>
                @endisset
            </div>

            <div class="tw-modal-body">
                <div>
                    <label class="tw-label" for="tw-note-to">@lang('teamwork::app.notes.to')</label>

                    <select id="tw-note-to" name="to_user_id" class="tw-input" required>
                        @foreach ($twMembers as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="tw-label" for="tw-note-body">@lang('teamwork::app.notes.body')</label>

                    <textarea id="tw-note-body" name="body" rows="4" maxlength="4000" required class="tw-input" placeholder="@lang('teamwork::app.notes.body-placeholder')"></textarea>
                </div>

                <p class="text-xs text-gray-500">@lang('teamwork::app.notes.info')</p>
            </div>

            <div class="tw-modal-foot">
                <button type="button" class="tw-btn" data-tw-close>@lang('teamwork::app.follow-up.cancel')</button>
                <button type="submit" class="tw-btn tw-btn-primary">@lang('teamwork::app.notes.send')</button>
            </div>
        </form>
    </div>
</div>

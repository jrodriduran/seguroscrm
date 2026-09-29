@php
    $twUser = auth()->guard('user')->user();
    $twScope = app(\Webkul\Teamwork\Services\TeamScope::class);
    $twAssignees = $twScope->assignableUsers($twUser);
    // Teamwork: anyone in the agency can flag anything, urgent included.
    $twSupervised = $twScope->teamUserIds($twUser);
@endphp

{{-- Shared "flag for follow-up" dialog; opened by any [data-tw-open] element. --}}
<div id="tw-modal" class="tw-modal" role="dialog" aria-modal="true">
    <div class="tw-modal-panel">
        <form
            method="POST"
            action="{{ route('admin.teamwork.follow_ups.store') }}"
            data-me="{{ $twUser->id }}"
            data-supervised="{{ implode(',', $twSupervised) }}"
        >
            @csrf

            <input type="hidden" name="entity_type" value="{{ $entityType ?? '' }}">
            <input type="hidden" name="entity_id" value="{{ $entityId ?? '' }}">

            <div class="tw-modal-head">
                <p class="text-base font-semibold text-gray-800 dark:text-white" data-tw-heading>
                    @lang('teamwork::app.follow-up.modal-title')
                </p>

                @isset($recordTitle)
                    <p class="mt-0.5 truncate text-xs text-gray-500">{{ $recordTitle }}</p>
                @endisset
            </div>

            <div class="tw-modal-body">
                <div>
                    <label class="tw-label" for="tw-assigned-to">@lang('teamwork::app.follow-up.assign-to')</label>

                    <select id="tw-assigned-to" name="assigned_to" class="tw-input">
                        @foreach ($twAssignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected($assignee->id === $twUser->id)>
                                {{ $assignee->id === $twUser->id ? trans('teamwork::app.follow-up.me').' — ' : '' }}{{ $assignee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <span class="tw-label">@lang('teamwork::app.follow-up.priority')</span>

                    <div class="tw-segment">
                        <label><input type="radio" name="priority" value="normal" checked><span>@lang('teamwork::app.follow-up.normal')</span></label>
                        <label><input type="radio" name="priority" value="urgent"><span>@lang('teamwork::app.follow-up.urgent')</span></label>
                    </div>

                </div>

                <div>
                    <label class="tw-label" for="tw-due">@lang('teamwork::app.follow-up.due')</label>

                    <input id="tw-due" type="datetime-local" name="due_at" class="tw-input">
                </div>

                <div>
                    <label class="tw-label" for="tw-note">@lang('teamwork::app.follow-up.note')</label>

                    <textarea data-tw-mentions id="tw-note" name="note" rows="3" maxlength="2000" class="tw-input" placeholder="@lang('teamwork::app.follow-up.note-placeholder')"></textarea>
                </div>
            </div>

            <div class="tw-modal-foot">
                <button type="button" class="tw-btn" data-tw-close>@lang('teamwork::app.follow-up.cancel')</button>
                <button type="submit" class="tw-btn tw-btn-primary">@lang('teamwork::app.follow-up.save')</button>
            </div>
        </form>
    </div>
</div>

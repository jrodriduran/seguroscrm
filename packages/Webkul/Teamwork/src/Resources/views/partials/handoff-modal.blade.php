{{-- "Hand over" dialog; opened by any [data-tw-handoff] element with data-lead-id / data-heading. --}}
@php
    $twUser = auth()->guard('user')->user();
    $twMembers = app(\Webkul\Teamwork\Services\TeamScope::class)->assignableUsers($twUser);
@endphp

<div id="tw-handoff" class="tw-modal" role="dialog" aria-modal="true">
    <div class="tw-modal-panel">
        <form method="POST" action="{{ route('admin.teamwork.follow_ups.handoff') }}">
            @csrf

            <input type="hidden" name="lead_id" value="">

            <div class="tw-modal-head">
                <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('teamwork::app.handoff.modal-title')</p>
                <p class="mt-0.5 truncate text-xs text-gray-500" data-tw-heading></p>
            </div>

            <div class="tw-modal-body">
                <div>
                    <label class="tw-label" for="tw-handoff-to">@lang('teamwork::app.handoff.to')</label>

                    <select id="tw-handoff-to" name="to_user" class="tw-input" required>
                        @foreach ($twMembers as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="tw-label" for="tw-handoff-note">@lang('teamwork::app.handoff.note')</label>

                    <textarea data-tw-mentions id="tw-handoff-note" name="note" rows="3" maxlength="2000" class="tw-input" placeholder="@lang('teamwork::app.handoff.note-placeholder')"></textarea>
                </div>

                <p class="text-xs text-gray-500">@lang('teamwork::app.handoff.info')</p>
            </div>

            <div class="tw-modal-foot">
                <button type="button" class="tw-btn" data-tw-close>@lang('teamwork::app.follow-up.cancel')</button>
                <button type="submit" class="tw-btn tw-btn-primary">@lang('teamwork::app.handoff.save')</button>
            </div>
        </form>
    </div>
</div>

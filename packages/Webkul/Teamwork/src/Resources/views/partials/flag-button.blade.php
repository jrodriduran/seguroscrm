{{-- Team actions in the header on record pages (lead, contact, organization, quote). Dialogs are rendered outside the header by partials.record-modal. --}}
@php
    $twRecord = app(\Webkul\Teamwork\Services\Entities::class)->fromCurrentRoute();
@endphp

@if ($twRecord)
    @php
        $twOpen = \Webkul\Teamwork\Models\FollowUp::where('entity_type', $twRecord[0])
            ->where('entity_id', $twRecord[1])
            ->where('status', 'open')
            ->count();

        $twFollowers = app(\Webkul\Teamwork\Services\Followers::class);
        $twFollowing = $twFollowers->isFollowing(auth()->guard('user')->id(), $twRecord[0], $twRecord[1]);
        $twFollowerCount = count($twFollowers->followerIds($twRecord[0], $twRecord[1]));
        $twCanHandoff = $twRecord[0] === 'lead' && (auth()->guard('user')->user()->role?->permission_type === 'all' || bouncer()->hasPermission('leads.edit'));
        $twLead = $twCanHandoff ? \Illuminate\Support\Facades\DB::table('leads')->where('id', $twRecord[1])->first(['user_id', 'title']) : null;
    @endphp

    <div class="flex items-center gap-1.5 max-md:hidden">
        <button type="button" class="tw-flag" data-tw-open>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>
            @lang('teamwork::app.follow-up.button')

            @if ($twOpen)
                <span class="tw-flag-badge" title="@lang('teamwork::app.follow-up.existing')">{{ $twOpen }}</span>
            @endif
        </button>

        <details class="tw-menu">
            <summary class="tw-flag">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                @lang('teamwork::app.menu-actions.team')

                @if ($twFollowing)
                    <span class="tw-flag-badge" style="background: #059669;" title="@lang('teamwork::app.followers.following')">✓</span>
                @endif
            </summary>

            <div class="tw-menu-panel">
                <button type="button" class="tw-menu-item" data-tw-note>
                    @lang('teamwork::app.notes.modal-title')
                </button>

                <a href="{{ route('admin.teamwork.records.show', $twRecord) }}" class="tw-menu-item">
                    @lang('teamwork::app.records.team-activity')
                </a>

                @if ($twCanHandoff)
                    <button type="button" class="tw-menu-item" data-tw-handoff data-lead-id="{{ $twRecord[1] }}" data-owner="{{ $twLead->user_id }}" data-heading="{{ $twLead->title }}">
                        @lang('teamwork::app.handoff.modal-title')
                    </button>
                @endif

                <form method="POST" action="{{ route('admin.teamwork.records.follow', $twRecord) }}">
                    @csrf

                    <button type="submit" class="tw-menu-item">
                        {{ $twFollowing ? trans('teamwork::app.followers.unfollow') : trans('teamwork::app.followers.follow') }}
                        <span class="tw-meta" style="display: block;">{{ trans_choice('teamwork::app.followers.count', $twFollowerCount, ['count' => $twFollowerCount]) }}</span>
                    </button>
                </form>
            </div>
        </details>
    </div>
@endif

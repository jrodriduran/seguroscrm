<?php

namespace Webkul\Teamwork\Http\Controllers;

use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Teamwork\Models\Note;
use Webkul\Teamwork\Services\Entities;
use Webkul\Teamwork\Services\Followers;
use Webkul\Teamwork\Services\TeamScope;
use Webkul\Teamwork\Services\WorkQueue;

class FollowUpCenterController extends Controller
{
    public function __construct(
        protected WorkQueue $workQueue,
        protected TeamScope $teamScope,
    ) {}

    /**
     * "My work" and "Team" for everyone — teammates see each other's work
     * and can focus on one person with ?member=ID. The owner also sees
     * escalations.
     */
    public function index(): View
    {
        $user = auth()->guard('user')->user();

        $teamIds = $this->teamScope->teamUserIds($user);

        $tab = request('tab') === 'team' ? 'team' : 'mine';

        $followUps = $this->workQueue->openFollowUps([$user->id]);

        $isMaster = $this->teamScope->isMasterAgent($user);

        $data = [
            'tab' => $tab,
            'isMaster' => $isMaster,
            'urgent' => $followUps->filter->isUrgent()->values(),
            'urgentActivities' => $this->workQueue->urgentActivities([$user->id]),
            'followUps' => $followUps->reject->isUrgent()->values(),
            'delegated' => $this->workQueue->openFollowUps([], $user->id)->where('assigned_to', '!=', $user->id)->values(),
            'cases' => $this->workQueue->openCases([$user->id], 100),
            'members' => $this->teamScope->assignableUsers($user),
            'member' => null,
            'unreadNotes' => Note::with('sender:id,name')->where('to_user_id', $user->id)->whereNull('read_at')->latest('id')->get(),
            'sentUnread' => Note::with('recipient:id,name')->where('from_user_id', $user->id)->whereNull('read_at')->latest('id')->limit(20)->get(),
            'following' => app(Followers::class)->followedBy($user->id, 30)
                ->map(function ($row) {
                    $record = app(Entities::class)->describe($row->entity_type, (int) $row->entity_id);

                    return $record ? (object) array_merge($record, ['type' => $row->entity_type, 'id' => (int) $row->entity_id, 'auto' => (bool) $row->is_auto]) : null;
                })
                ->filter()
                ->values(),
        ];

        if ($tab === 'team') {
            // Focus on one teammate, or the whole team.
            $member = (int) request('member');
            $scopeIds = $member && in_array($member, $teamIds, true) ? [$member] : $teamIds;

            $data['member'] = $member && $scopeIds === [$member] ? $data['members']->firstWhere('id', $member) : null;
            $data['team'] = $this->workQueue->teamSummary($teamIds);
            $data['teamCases'] = $this->workQueue->openCases($scopeIds, 300)
                ->filter(fn ($case) => $data['member'] || $case->state !== WorkQueue::OK)
                ->values();
            $data['teamFollowUps'] = $this->workQueue->openFollowUps($scopeIds)
                ->filter(fn ($followUp) => $data['member'] || $followUp->state !== WorkQueue::OK || $followUp->isUrgent())
                ->values();
            $data['teamUrgentActivities'] = $this->workQueue->urgentActivities($scopeIds);
            $data['escalated'] = $isMaster ? $this->workQueue->escalatedLeads($scopeIds) : collect();
        }

        return view('teamwork::center.index', $data);
    }
}

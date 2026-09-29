<?php

namespace Webkul\Teamwork\Http\Controllers;

use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Teamwork\Models\FollowUp;
use Webkul\Teamwork\Models\Note;
use Webkul\Teamwork\Services\Entities;
use Webkul\Teamwork\Services\TeamScope;
use Webkul\Teamwork\Services\WorkQueue;

class RecordController extends Controller
{
    public function __construct(
        protected Entities $entities,
        protected TeamScope $teamScope,
        protected WorkQueue $workQueue,
    ) {}

    /**
     * Team activity on one record: notes between teammates and follow-ups, newest first.
     */
    public function show(string $type, int $id): View
    {
        $record = $this->entities->describe($type, $id);

        abort_unless($record, 404);

        $user = auth()->guard('user')->user();
        $isMaster = $this->teamScope->isMasterAgent($user);
        $team = $this->teamScope->teamUserIds($user);

        // Notes are private between sender and recipient (the owner sees the team's).
        $notes = Note::with(['sender:id,name', 'recipient:id,name'])
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->where(function ($query) use ($user, $isMaster, $team) {
                $query->where('from_user_id', $user->id)->orWhere('to_user_id', $user->id);

                if ($isMaster) {
                    $query->orWhereIn('to_user_id', $team);
                }
            })
            ->get();

        $followUps = FollowUp::with(['assignee:id,name', 'creator:id,name'])
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->whereIn('assigned_to', $team)
            ->get()
            ->each(fn ($followUp) => $followUp->state = $followUp->isOpen() ? $this->workQueue->followUpState($followUp) : 'ok');

        $feed = $notes->map(fn ($note) => ['kind' => 'note', 'at' => $note->created_at, 'item' => $note])
            ->merge($followUps->map(fn ($followUp) => ['kind' => 'follow_up', 'at' => $followUp->created_at, 'item' => $followUp]))
            ->sortByDesc('at')
            ->values();

        return view('teamwork::records.show', [
            'type' => $type,
            'id' => $id,
            'record' => $record,
            'feed' => $feed,
            'openFollowUps' => $followUps->filter->isOpen()->count(),
        ]);
    }
}

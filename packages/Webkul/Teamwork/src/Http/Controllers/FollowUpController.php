<?php

namespace Webkul\Teamwork\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Teamwork\Models\FollowUp;
use Webkul\Teamwork\Models\FollowUpComment;
use Webkul\Teamwork\Services\Entities;
use Webkul\Teamwork\Services\Followers;
use Webkul\Teamwork\Services\Mentions;
use Webkul\Teamwork\Services\Notifier;
use Webkul\Teamwork\Services\TeamScope;
use Webkul\Teamwork\Services\WorkQueue;
use Webkul\User\Models\User;

class FollowUpController extends Controller
{
    public function __construct(
        protected Entities $entities,
        protected TeamScope $teamScope,
        protected WorkQueue $workQueue,
        protected Notifier $notifier,
        protected Followers $followers,
        protected Mentions $mentions,
    ) {}

    /**
     * Flag a record for follow-up: for myself, for a teammate, or as urgent for someone I supervise.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->guard('user')->user();

        $data = $request->validate([
            'entity_type' => ['required', Rule::in(array_keys(Entities::TYPES))],
            'entity_id' => ['required', 'integer'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['nullable', Rule::in([FollowUp::PRIORITY_NORMAL, FollowUp::PRIORITY_URGENT])],
            'due_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = $this->entities->describe($data['entity_type'], (int) $data['entity_id']);

        abort_unless($record, 404);

        $assignee = User::findOrFail($data['assigned_to'] ?? $user->id);

        // Collaboration stays inside the agency; within it, anyone can ask anyone.
        abort_unless($this->teamScope->isTeammate($user, $assignee->id), 403);

        $priority = $data['priority'] ?? FollowUp::PRIORITY_NORMAL;

        $followUp = FollowUp::create([
            'agency_id' => $assignee->agency_id,
            'entity_type' => $data['entity_type'],
            'entity_id' => (int) $data['entity_id'],
            'title' => $record['title'],
            'url' => $record['url'],
            'assigned_to' => $assignee->id,
            'created_by' => $user->id,
            'priority' => $priority,
            'note' => $data['note'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'status' => FollowUp::STATUS_OPEN,
            'last_activity_at' => now(),
        ]);

        $isUrgent = $priority === FollowUp::PRIORITY_URGENT;

        $this->notifier->notify(
            $assignee->id,
            $isUrgent ? Notifier::FOLLOW_UP_URGENT : Notifier::FOLLOW_UP_ASSIGNED,
            trans($isUrgent ? 'teamwork::app.notifications.urgent' : 'teamwork::app.notifications.assigned', ['name' => $user->name, 'record' => $record['title']]),
            $followUp->note,
            route('admin.teamwork.follow_ups.show', $followUp->id, false),
            $isUrgent,
        );

        // Both people involved follow the record from now on.
        $this->followers->follow($user->id, $followUp->entity_type, $followUp->entity_id, true);
        $this->followers->follow($assignee->id, $followUp->entity_type, $followUp->entity_id, true);

        $mentioned = $this->mentions->extract((string) $followUp->note);
        $followUrl = route('admin.teamwork.follow_ups.show', $followUp->id, false);

        $this->notifier->notify(array_diff($mentioned, [$assignee->id]), Notifier::MENTION,
            trans('teamwork::app.notifications.mention', ['name' => $user->name, 'record' => $record['title']]), $followUp->note, $followUrl);

        $this->followers->notify($followUp->entity_type, $followUp->entity_id, Notifier::RECORD_FOLLOW_UP,
            trans('teamwork::app.notifications.record-follow-up', ['name' => $user->name, 'to' => $assignee->name, 'record' => $record['title']]),
            $followUp->note, $followUrl, array_merge([$assignee->id], $mentioned), $isUrgent);

        session()->flash('success', trans($assignee->id === $user->id
            ? 'teamwork::app.follow-up.created-self'
            : 'teamwork::app.follow-up.created-other', ['name' => $assignee->name]));

        return back();
    }

    /**
     * Follow-up with its shared thread.
     */
    public function show(int $id): View
    {
        $followUp = $this->findVisible($id);

        $followUp->load(['assignee:id,name', 'creator:id,name', 'resolver:id,name', 'comments.user:id,name']);

        $followUp->state = $this->workQueue->followUpState($followUp);

        return view('teamwork::follow-ups.show', [
            'followUp' => $followUp,
            'history' => FollowUp::with('assignee:id,name')
                ->where('entity_type', $followUp->entity_type)
                ->where('entity_id', $followUp->entity_id)
                ->where('id', '!=', $followUp->id)
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function comment(Request $request, int $id): RedirectResponse
    {
        $followUp = $this->findVisible($id);

        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        FollowUpComment::create([
            'follow_up_id' => $followUp->id,
            'user_id' => auth()->guard('user')->id(),
            'body' => $data['body'],
        ]);

        $followUp->update(['last_activity_at' => now()]);

        $author = auth()->guard('user')->user();
        $mentioned = $this->mentions->extract($data['body']);

        $this->followers->follow($author->id, $followUp->entity_type, $followUp->entity_id, true);

        // @mentioned teammates are pulled into the conversation.
        $this->notifier->notify($mentioned, Notifier::MENTION,
            trans('teamwork::app.notifications.mention', ['name' => $author->name, 'record' => $followUp->title]),
            $data['body'], route('admin.teamwork.follow_ups.show', $followUp->id, false), $followUp->isUrgent());

        // Everyone in the conversation: owner, requester and anyone who commented.
        $this->notifier->notify(
            $followUp->comments()->pluck('user_id')->push($followUp->assigned_to, $followUp->created_by)->diff($mentioned)->all(),
            Notifier::FOLLOW_UP_COMMENT,
            trans('teamwork::app.notifications.comment', ['name' => auth()->guard('user')->user()->name, 'record' => $followUp->title]),
            $data['body'],
            route('admin.teamwork.follow_ups.show', $followUp->id, false),
            $followUp->isUrgent(),
        );

        return redirect()->route('admin.teamwork.follow_ups.show', $followUp->id);
    }

    /**
     * Hand a lead over to a teammate: new owner, a note on the lead's
     * timeline, and a follow-up for the new owner so nothing gets lost.
     */
    public function handoff(Request $request): RedirectResponse
    {
        $user = auth()->guard('user')->user();

        $data = $request->validate([
            'lead_id' => ['required', 'integer', 'exists:leads,id'],
            'to_user' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless($user->role?->permission_type === 'all' || bouncer()->hasPermission('leads.edit'), 401);

        $target = User::findOrFail($data['to_user']);

        abort_unless($this->teamScope->isTeammate($user, $target->id), 403);

        $lead = DB::table('leads')->where('id', $data['lead_id'])->first(['id', 'title', 'user_id']);

        abort_if($lead->user_id && ! $this->teamScope->isTeammate($user, (int) $lead->user_id), 404);

        $previous = $lead->user_id ? User::find($lead->user_id) : null;

        DB::transaction(function () use ($lead, $target, $previous, $user, $data) {
            DB::table('leads')->where('id', $lead->id)->update(['user_id' => $target->id, 'updated_at' => now()]);

            $comment = trans('teamwork::app.handoff.timeline', [
                'from' => $previous?->name ?? '—',
                'to' => $target->name,
                'by' => $user->name,
            ]).(! empty($data['note']) ? "\n".$data['note'] : '');

            $activityId = DB::table('activities')->insertGetId([
                'title' => trans('teamwork::app.handoff.title'),
                'type' => 'note',
                'comment' => $comment,
                'is_done' => 1,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('lead_activities')->insert(['activity_id' => $activityId, 'lead_id' => $lead->id]);

            if ($target->id !== $user->id) {
                $followUp = FollowUp::create([
                    'agency_id' => $target->agency_id,
                    'entity_type' => 'lead',
                    'entity_id' => $lead->id,
                    'title' => $lead->title,
                    'url' => route('admin.leads.view', $lead->id, false),
                    'assigned_to' => $target->id,
                    'created_by' => $user->id,
                    'priority' => FollowUp::PRIORITY_NORMAL,
                    'note' => $comment,
                    'status' => FollowUp::STATUS_OPEN,
                    'last_activity_at' => now(),
                ]);

                $handoffTitle = trans('teamwork::app.notifications.handoff', ['name' => $user->name, 'record' => $lead->title, 'to' => $target->name]);

                $this->notifier->notify(
                    [$target->id, $previous?->id],
                    Notifier::HANDOFF,
                    $handoffTitle,
                    $data['note'] ?? null,
                    route('admin.teamwork.follow_ups.show', $followUp->id, false),
                );

                $this->followers->follow($target->id, 'lead', $lead->id, true);

                $this->followers->notify('lead', $lead->id, Notifier::HANDOFF, $handoffTitle, $data['note'] ?? null,
                    route('admin.leads.view', $lead->id, false), [$target->id, $previous?->id]);
            }
        });

        session()->flash('success', trans('teamwork::app.handoff.done', ['name' => $target->name]));

        return back();
    }

    public function resolve(Request $request, int $id): RedirectResponse
    {
        $followUp = $this->findVisible($id);

        $user = auth()->guard('user')->user();

        // Closing is for whoever owns it, whoever asked, or the agency owner; teammates can still comment.
        abort_unless(
            in_array($user->id, [$followUp->assigned_to, $followUp->created_by], true) || $this->teamScope->isMasterAgent($user),
            403
        );

        if ($followUp->isOpen()) {
            if ($comment = trim((string) $request->input('body'))) {
                FollowUpComment::create([
                    'follow_up_id' => $followUp->id,
                    'user_id' => auth()->guard('user')->id(),
                    'body' => $comment,
                ]);
            }

            $followUp->update([
                'status' => FollowUp::STATUS_DONE,
                'resolved_at' => now(),
                'resolved_by' => auth()->guard('user')->id(),
                'last_activity_at' => now(),
            ]);

            $this->notifier->notify(
                [$followUp->created_by, $followUp->assigned_to],
                Notifier::FOLLOW_UP_RESOLVED,
                trans('teamwork::app.notifications.resolved', ['name' => $user->name, 'record' => $followUp->title]),
                $comment ?: null,
                route('admin.teamwork.follow_ups.show', $followUp->id, false),
            );

            session()->flash('success', trans('teamwork::app.follow-up.resolved'));
        }

        return $request->input('return') === 'center'
            ? redirect()->route('admin.teamwork.center')
            : redirect()->route('admin.teamwork.follow_ups.show', $followUp->id);
    }

    public function reopen(int $id): RedirectResponse
    {
        $followUp = $this->findVisible($id);

        $followUp->update([
            'status' => FollowUp::STATUS_OPEN,
            'resolved_at' => null,
            'resolved_by' => null,
            'last_activity_at' => now(),
        ]);

        return redirect()->route('admin.teamwork.follow_ups.show', $followUp->id);
    }

    /**
     * Visible to the whole team (same agency): anyone can read and chip in.
     */
    protected function findVisible(int $id): FollowUp
    {
        $followUp = FollowUp::findOrFail($id);

        abort_unless($this->teamScope->isTeammate(auth()->guard('user')->user(), $followUp->assigned_to), 404);

        return $followUp;
    }
}

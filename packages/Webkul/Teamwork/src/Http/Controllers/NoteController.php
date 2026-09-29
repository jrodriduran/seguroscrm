<?php

namespace Webkul\Teamwork\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Teamwork\Models\FollowUp;
use Webkul\Teamwork\Models\Note;
use Webkul\Teamwork\Services\Entities;
use Webkul\Teamwork\Services\Notifier;
use Webkul\Teamwork\Services\TeamScope;
use Webkul\User\Models\User;

class NoteController extends Controller
{
    public function __construct(
        protected Entities $entities,
        protected TeamScope $teamScope,
        protected Notifier $notifier,
    ) {}

    /**
     * Send a note about a record to a teammate (or reply to one).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->guard('user')->user();

        $data = $request->validate([
            'entity_type' => ['required', Rule::in(array_keys(Entities::TYPES))],
            'entity_id' => ['required', 'integer'],
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:4000'],
            'reply_to_id' => ['nullable', 'integer', 'exists:teamwork_notes,id'],
        ]);

        $record = $this->entities->describe($data['entity_type'], (int) $data['entity_id']);

        abort_unless($record, 404);

        $recipient = User::findOrFail($data['to_user_id']);

        abort_unless($this->teamScope->isTeammate($user, $recipient->id), 403);

        $note = Note::create([
            'agency_id' => $recipient->agency_id,
            'entity_type' => $data['entity_type'],
            'entity_id' => (int) $data['entity_id'],
            'title' => $record['title'],
            'url' => $record['url'],
            'from_user_id' => $user->id,
            'to_user_id' => $recipient->id,
            'body' => $data['body'],
            'reply_to_id' => $data['reply_to_id'] ?? null,
        ]);

        $this->notifier->notify(
            $recipient->id,
            $note->reply_to_id ? Notifier::NOTE_REPLY : Notifier::NOTE,
            trans($note->reply_to_id ? 'teamwork::app.notifications.note-reply' : 'teamwork::app.notifications.note', ['name' => $user->name, 'record' => $record['title']]),
            Str::limit($note->body, 200),
            route('admin.teamwork.notes.show', $note->id, false),
        );

        session()->flash('success', trans('teamwork::app.notes.sent', ['name' => $recipient->name]));

        return $note->reply_to_id
            ? redirect()->route('admin.teamwork.notes.show', $note->reply_to_id)
            : back();
    }

    /**
     * Read a note. Opening it as the recipient records the read receipt.
     */
    public function show(int $id): View
    {
        $note = $this->findVisible($id);

        $user = auth()->guard('user')->user();

        if ($note->to_user_id === $user->id && ! $note->read_at) {
            $note->update(['read_at' => now()]);
        }

        $note->load(['sender:id,name', 'recipient:id,name', 'followUp']);

        // The whole exchange this note belongs to, oldest first.
        $rootId = $note->reply_to_id ?? $note->id;

        $thread = Note::with(['sender:id,name', 'recipient:id,name'])
            ->where(fn ($query) => $query->where('id', $rootId)->orWhere('reply_to_id', $rootId))
            ->oldest('id')
            ->get();

        return view('teamwork::notes.show', compact('note', 'thread', 'rootId'));
    }

    /**
     * "Turn into a follow-up": the recipient keeps it on their plate.
     */
    public function convert(Request $request, int $id): RedirectResponse
    {
        $note = $this->findVisible($id);

        $user = auth()->guard('user')->user();

        abort_unless($note->to_user_id === $user->id, 403);

        $data = $request->validate(['due_at' => ['nullable', 'date']]);

        if (! $note->follow_up_id) {
            $followUp = FollowUp::create([
                'agency_id' => $user->agency_id,
                'entity_type' => $note->entity_type,
                'entity_id' => $note->entity_id,
                'title' => $note->title,
                'url' => $note->url,
                'assigned_to' => $user->id,
                'created_by' => $note->from_user_id,
                'priority' => FollowUp::PRIORITY_NORMAL,
                'note' => $note->body,
                'due_at' => $data['due_at'] ?? null,
                'status' => FollowUp::STATUS_OPEN,
                'last_activity_at' => now(),
            ]);

            $note->update(['follow_up_id' => $followUp->id, 'read_at' => $note->read_at ?? now()]);

            $this->notifier->notify(
                $note->from_user_id,
                Notifier::FOLLOW_UP_ASSIGNED,
                trans('teamwork::app.notifications.note-converted', ['name' => $user->name, 'record' => $note->title]),
                null,
                route('admin.teamwork.follow_ups.show', $followUp->id, false),
            );
        }

        session()->flash('success', trans('teamwork::app.notes.converted'));

        return redirect()->route('admin.teamwork.follow_ups.show', $note->follow_up_id);
    }

    /**
     * Sender, recipient, or the agency owner.
     */
    protected function findVisible(int $id): Note
    {
        $note = Note::findOrFail($id);

        $user = auth()->guard('user')->user();

        abort_unless(
            in_array($user->id, [$note->from_user_id, $note->to_user_id], true)
            || ($this->teamScope->isMasterAgent($user) && $this->teamScope->isTeammate($user, $note->to_user_id)),
            404
        );

        return $note;
    }
}

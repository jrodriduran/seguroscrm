<?php

namespace Webkul\Teamwork\Services;

use Illuminate\Support\Str;
use Webkul\Teamwork\Models\Notification;

/**
 * Drops a notification in teammates' bells. Never notifies the person who
 * did the action.
 */
class Notifier
{
    const FOLLOW_UP_ASSIGNED = 'follow_up_assigned';

    const FOLLOW_UP_URGENT = 'follow_up_urgent';

    const FOLLOW_UP_COMMENT = 'follow_up_comment';

    const FOLLOW_UP_RESOLVED = 'follow_up_resolved';

    const HANDOFF = 'handoff';

    const NOTE = 'note';

    const NOTE_REPLY = 'note_reply';

    const MENTION = 'mention';

    const RECORD_ACTIVITY = 'record_activity';

    const RECORD_STAGE = 'record_stage';

    const RECORD_FOLLOW_UP = 'record_follow_up';

    const AUTOMATION = 'automation';

    /**
     * @param  int|array  $userIds
     */
    public function notify($userIds, string $type, string $title, ?string $body = null, ?string $url = null, bool $urgent = false): void
    {
        $actorId = auth()->guard('user')->id();

        foreach (array_unique(array_filter((array) $userIds)) as $userId) {
            if ((int) $userId === (int) $actorId) {
                continue;
            }

            Notification::create([
                'user_id' => $userId,
                'actor_id' => $actorId,
                'type' => $type,
                'title' => Str::limit($title, 250, '…'),
                'body' => $body ? Str::limit($body, 480, '…') : null,
                'url' => $url,
                'is_urgent' => $urgent,
            ]);
        }
    }

    public function unreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->whereNull('read_at')->count();
    }

    public function latest(int $userId, int $limit = 12)
    {
        return Notification::with('actor:id,name')
            ->where('user_id', $userId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}

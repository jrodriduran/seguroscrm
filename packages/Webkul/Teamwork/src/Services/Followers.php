<?php

namespace Webkul\Teamwork\Services;

use Illuminate\Support\Facades\DB;

/**
 * Record followers. People follow a record by hand, or automatically when
 * they get involved (flag it, are assigned, receive a hand-over, comment).
 */
class Followers
{
    const TABLE = 'teamwork_record_followers';

    public function __construct(protected Notifier $notifier) {}

    public function follow(int $userId, string $type, int $id, bool $auto = false): void
    {
        DB::table(self::TABLE)->insertOrIgnore([
            'user_id' => $userId,
            'entity_type' => $type,
            'entity_id' => $id,
            'is_auto' => $auto,
            'created_at' => now(),
        ]);
    }

    public function unfollow(int $userId, string $type, int $id): void
    {
        DB::table(self::TABLE)->where(['user_id' => $userId, 'entity_type' => $type, 'entity_id' => $id])->delete();
    }

    public function isFollowing(int $userId, string $type, int $id): bool
    {
        return DB::table(self::TABLE)->where(['user_id' => $userId, 'entity_type' => $type, 'entity_id' => $id])->exists();
    }

    public function followerIds(string $type, int $id): array
    {
        return DB::table(self::TABLE)
            ->where(['entity_type' => $type, 'entity_id' => $id])
            ->pluck('user_id')
            ->map(fn ($userId) => (int) $userId)
            ->all();
    }

    /**
     * Records a user follows, most recent first.
     */
    public function followedBy(int $userId, int $limit = 50)
    {
        return DB::table(self::TABLE)->where('user_id', $userId)->latest('id')->limit($limit)->get();
    }

    /**
     * Tell a record's followers something happened on it, except the people
     * in $except (usually already notified more specifically).
     */
    public function notify(string $type, int $id, string $notificationType, string $title, ?string $body = null, ?string $url = null, array $except = [], bool $urgent = false): void
    {
        $recipients = array_diff($this->followerIds($type, $id), array_map('intval', array_filter($except)));

        if ($recipients) {
            $this->notifier->notify($recipients, $notificationType, $title, $body, $url, $urgent);
        }
    }
}

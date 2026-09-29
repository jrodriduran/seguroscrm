<?php

namespace Webkul\Teamwork\Services;

use Illuminate\Support\Facades\DB;
use Webkul\User\Models\User;

/**
 * Team membership and supervision.
 *
 * - Everyone in an agency is one team: they can see each other's work,
 *   hand cases over and ask each other for help (teamUserIds).
 * - The master agent (agency owner: full-permission role, nobody above
 *   them in the hierarchy) supervises everyone in the agency.
 * - For a future broker view, anyone with people under them in the
 *   hierarchy supervises their whole downline.
 */
class TeamScope
{
    protected array $cache = [];

    /**
     * Ids of the users this user supervises (never includes themselves).
     */
    public function supervisedUserIds(User $user): array
    {
        if (isset($this->cache[$user->id])) {
            return $this->cache[$user->id];
        }

        if ($this->isMasterAgent($user)) {
            $ids = User::query()
                ->when($user->agency_id, fn ($query) => $query->where('agency_id', $user->agency_id))
                ->where('id', '!=', $user->id)
                ->where('status', 1)
                ->pluck('id')
                ->all();
        } else {
            $ids = $this->downline($user->id);
        }

        return $this->cache[$user->id] = array_values(array_unique(array_map('intval', $ids)));
    }

    public function isSupervisor(User $user): bool
    {
        return ! empty($this->supervisedUserIds($user));
    }

    /**
     * Everyone the user works with, themselves included: the whole agency.
     * Teamwork is open — anyone can look at a teammate's cases and help.
     */
    public function teamUserIds(User $user): array
    {
        return $this->cache['team:'.$user->id] ??= User::query()
            ->when($user->agency_id, fn ($query) => $query->where('agency_id', $user->agency_id))
            ->where('status', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Same agency (or no agencies configured at all).
     */
    public function isTeammate(User $user, int $otherUserId): bool
    {
        return in_array($otherUserId, $this->teamUserIds($user), true);
    }

    public function isMasterAgent(User $user): bool
    {
        if ($user->role?->permission_type !== 'all') {
            return false;
        }

        return ! DB::table('agency_hierarchies')
            ->where('user_id', $user->id)
            ->whereNotNull('parent_user_id')
            ->exists();
    }

    /**
     * Whether $user may see / act on work assigned to $assigneeId.
     */
    public function canOversee(User $user, int $assigneeId): bool
    {
        return $assigneeId === $user->id || in_array($assigneeId, $this->supervisedUserIds($user), true);
    }

    /**
     * People a user can hand a follow-up to: themselves, their team, and
     * (for collaboration between agents) everyone active in their agency.
     */
    public function assignableUsers(User $user)
    {
        return User::query()
            ->when($user->agency_id, fn ($query) => $query->where('agency_id', $user->agency_id))
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Every user below $userId in the hierarchy, breadth first.
     */
    protected function downline(int $userId): array
    {
        $found = [];
        $frontier = [$userId];

        for ($depth = 0; $frontier && $depth < 10; $depth++) {
            $children = DB::table('agency_hierarchies')
                ->whereIn('parent_user_id', $frontier)
                ->where('status', 'active')
                ->pluck('user_id')
                ->all();

            $frontier = array_diff($children, $found, [$userId]);

            $found = array_merge($found, $frontier);
        }

        return $found;
    }
}

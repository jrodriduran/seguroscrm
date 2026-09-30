<?php

namespace Webkul\Communications\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Communications\Models\CallOutcome;
use Webkul\Communications\Services\ConsentRegistry;

/**
 * Saves the outcome picked when a call is logged or edited. Outcomes such as
 * "do not call" also withdraw consent, and every outcome is announced so
 * communication sequences can react to it.
 */
class ActivityOutcomeListener
{
    public function __construct(protected ConsentRegistry $consents) {}

    public function saved($activity): void
    {
        if (! $activity || ! request()->has('outcome')) {
            return;
        }

        $code = request('outcome') ?: null;
        $outcome = $code ? CallOutcome::forAgency()->firstWhere('code', $code) : null;

        if ($code && ! $outcome) {
            return;
        }

        if ($activity->outcome === $code) {
            return;
        }

        DB::table('activities')->where('id', $activity->id)->update(['outcome' => $code]);

        $activity->outcome = $code;

        if (! $outcome) {
            return;
        }

        $personIds = $this->personIds($activity->id);

        if ($outcome->revokes) {
            $channels = $outcome->revokes === 'all' ? ConsentRegistry::CHANNELS : [$outcome->revokes];

            foreach ($personIds as $personId) {
                $this->consents->record($personId, $channels, ConsentRegistry::REVOKED, 'call_outcome', $outcome->label);
            }
        }

        Event::dispatch('communications.call.outcome', [$activity, $outcome, $personIds]);
    }

    /**
     * Clients linked to the activity directly or through its lead.
     */
    protected function personIds(int $activityId): array
    {
        $direct = DB::table('person_activities')->where('activity_id', $activityId)->pluck('person_id');

        $viaLead = DB::table('lead_activities')
            ->join('leads', 'leads.id', '=', 'lead_activities.lead_id')
            ->where('lead_activities.activity_id', $activityId)
            ->whereNotNull('leads.person_id')
            ->pluck('leads.person_id');

        return $direct->merge($viaLead)->map(fn ($id) => (int) $id)->unique()->values()->all();
    }
}

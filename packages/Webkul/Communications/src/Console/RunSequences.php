<?php

namespace Webkul\Communications\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Communications\Models\Trigger;
use Webkul\Communications\Services\CampaignRunner;
use Webkul\Communications\Services\SequenceEngine;
use Webkul\Teamwork\Http\Middleware\ApplyTimezone;

/**
 * Scheduled every few minutes: starts date-based sequences (birthdays,
 * renewals, leads idle in a stage), runs the steps that are due and sends
 * the next batch of scheduled campaigns.
 */
class RunSequences extends Command
{
    protected $signature = 'communications:run-sequences';

    protected $description = 'Start date-based communication sequences and run due steps';

    public function handle(SequenceEngine $engine, CampaignRunner $campaigns): int
    {
        // Same clock as the web app: the agency time zone.
        if (class_exists('Webkul\\Teamwork\\Http\\Middleware\\ApplyTimezone')) {
            $zone = ApplyTimezone::resolve();
            config(['app.timezone' => $zone]);
            date_default_timezone_set($zone);
        }

        $started = $this->birthdays($engine) + $this->renewals($engine) + $this->idleStages($engine);
        $ran = $engine->runDue();
        $sent = $campaigns->runDue();

        $this->info("Started {$started} enrollment(s), ran {$ran} step(s), {$sent} campaign message(s).");

        return self::SUCCESS;
    }

    protected function birthdays(SequenceEngine $engine): int
    {
        if (! $this->hasTrigger('birthday')) {
            return 0;
        }

        $today = now();

        return DB::table('attribute_values')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->where('attributes.entity_type', 'persons')
            ->where('attributes.code', 'dob')
            ->whereMonth('attribute_values.date_value', $today->month)
            ->whereDay('attribute_values.date_value', $today->day)
            ->pluck('attribute_values.entity_id')
            ->sum(fn ($personId) => $engine->fire('birthday', [], (int) $personId, null, null, 'birthday:'.$today->year));
    }

    protected function renewals(SequenceEngine $engine): int
    {
        $started = 0;

        foreach ($this->triggers('renewal_before') as $trigger) {
            $date = now()->addDays(max(0, (int) $trigger->option('days', 60)))->toDateString();

            $policies = DB::table('insurance_policies')
                ->whereDate('renewal_date', $date)
                ->whereNotNull('person_id')
                ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'cancelled'))
                ->get(['id', 'person_id', 'lead_id', 'renewal_date']);

            foreach ($policies as $policy) {
                $started += (int) (bool) $engine->enroll($trigger->sequence, (int) $policy->person_id, $policy->lead_id ? (int) $policy->lead_id : null, (int) $policy->id, $trigger->id, 'renewal:'.$policy->renewal_date);
            }
        }

        return $started;
    }

    protected function idleStages(SequenceEngine $engine): int
    {
        $started = 0;

        foreach ($this->triggers('stage_idle') as $trigger) {
            $entries = DB::table('communication_stage_entries')
                ->join('leads', 'leads.id', '=', 'communication_stage_entries.lead_id')
                ->where('communication_stage_entries.stage_id', (int) $trigger->option('stage_id'))
                ->whereNull('communication_stage_entries.exited_at')
                ->where('communication_stage_entries.entered_at', '<=', now()->subDays(max(1, (int) $trigger->option('days', 3))))
                ->whereNotNull('leads.person_id')
                ->get(['communication_stage_entries.id', 'leads.id as lead_id', 'leads.person_id']);

            foreach ($entries as $entry) {
                $started += (int) (bool) $engine->enroll($trigger->sequence, (int) $entry->person_id, (int) $entry->lead_id, null, $trigger->id, 'idle:'.$entry->id.':'.$trigger->id);
            }
        }

        return $started;
    }

    protected function triggers(string $event)
    {
        return Trigger::with('sequence')
            ->where('event', $event)
            ->where('is_active', true)
            ->whereHas('sequence', fn ($query) => $query->where('is_active', true))
            ->get();
    }

    protected function hasTrigger(string $event): bool
    {
        return $this->triggers($event)->isNotEmpty();
    }
}

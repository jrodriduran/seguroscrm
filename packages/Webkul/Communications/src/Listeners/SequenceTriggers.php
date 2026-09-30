<?php

namespace Webkul\Communications\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Communications\Services\SequenceEngine;

/**
 * CRM events that start or stop communication sequences.
 */
class SequenceTriggers
{
    /**
     * Stage of each lead before an update, keyed by lead id.
     */
    protected static array $stagesBefore = [];

    public function __construct(protected SequenceEngine $engine) {}

    public function leadCreated($lead): void
    {
        $this->safely(function () use ($lead) {
            if (! $lead?->lead_pipeline_stage_id) {
                return;
            }

            $this->openStage((int) $lead->id, (int) $lead->lead_pipeline_stage_id);

            if ($lead->person_id) {
                $this->engine->fire('stage_entered', ['stage_id' => $lead->lead_pipeline_stage_id], (int) $lead->person_id, (int) $lead->id, null, 'stage:'.$lead->lead_pipeline_stage_id);
            }
        });
    }

    public function leadUpdating($id): void
    {
        static::$stagesBefore[(int) $id] = DB::table('leads')->where('id', $id)->value('lead_pipeline_stage_id');
    }

    public function leadUpdated($lead): void
    {
        $this->safely(function () use ($lead) {
            $before = static::$stagesBefore[(int) $lead->id] ?? null;
            unset(static::$stagesBefore[(int) $lead->id]);

            $now = (int) $lead->lead_pipeline_stage_id;

            if (! $before || (int) $before === $now) {
                return;
            }

            DB::table('communication_stage_entries')->where('lead_id', $lead->id)->whereNull('exited_at')->update(['exited_at' => now()]);
            $this->engine->stageLeft((int) $lead->id, (int) $before);

            $this->openStage((int) $lead->id, $now);

            if ($lead->person_id) {
                // Entering the same stage again later is a new occasion.
                $this->engine->fire('stage_entered', ['stage_id' => $now], (int) $lead->person_id, (int) $lead->id, null, 'stage:'.$now.':'.now()->format('YmdHi'));
            }
        });
    }

    public function policyEffectuated($policy): void
    {
        $this->safely(fn () => $policy->person_id && $this->engine->fire('policy_effectuated', [], (int) $policy->person_id, $policy->lead_id ? (int) $policy->lead_id : null, (int) $policy->id, 'policy:'.$policy->id));
    }

    public function policyStatusChanged($policy, $from = null, $to = null): void
    {
        $this->safely(fn () => $policy->person_id && $to && $this->engine->fire('policy_status', ['status' => $to], (int) $policy->person_id, $policy->lead_id ? (int) $policy->lead_id : null, (int) $policy->id, 'policy:'.$policy->id.':'.$to.':'.now()->format('Ymd')));
    }

    public function callOutcome($activity, $outcome = null, $personIds = []): void
    {
        $this->safely(function () use ($activity, $outcome, $personIds) {
            $leadId = DB::table('lead_activities')->where('activity_id', $activity->id)->value('lead_id');

            foreach ((array) $personIds as $personId) {
                $this->engine->fire('call_outcome', ['outcome' => $outcome->code], (int) $personId, $leadId ? (int) $leadId : null, null, 'call:'.$activity->id);
            }
        });
    }

    public function messageReceived($message): void
    {
        $this->safely(function () use ($message) {
            if ($message->direction !== 'in' || ! $message->person_id) {
                return;
            }

            $paused = $this->engine->clientWrote((int) $message->person_id);
            $notifier = 'Webkul\\Teamwork\\Services\\Notifier';

            if ($paused && class_exists($notifier)) {
                $ownerId = DB::table('persons')->where('id', $message->person_id)->value('user_id');

                app($notifier)->notify(array_filter([$ownerId]), 'automation',
                    trans('communications::app.sequences.paused-on-reply', ['name' => DB::table('persons')->where('id', $message->person_id)->value('name'), 'count' => count($paused)]),
                    null, route('admin.communications.persons.show', $message->person_id, false));
            }
        });
    }

    protected function openStage(int $leadId, int $stageId): void
    {
        DB::table('communication_stage_entries')->insert(['lead_id' => $leadId, 'stage_id' => $stageId, 'entered_at' => now()]);
    }

    /**
     * Sequences must never break the CRM action that triggered them.
     */
    protected function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            Log::error('Communication sequence trigger failed: '.$e->getMessage());
        }
    }
}

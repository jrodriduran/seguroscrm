<?php

namespace Webkul\Teamwork\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Teamwork\Services\StagePlaybooks;

/**
 * Runs the stage playbook whenever a lead is created or changes stage.
 */
class StagePlaybookListener
{
    protected static array $stagesBefore = [];

    public function __construct(protected StagePlaybooks $playbooks) {}

    public function leadCreated($lead): void
    {
        $this->safely(fn () => $lead?->lead_pipeline_stage_id && $this->playbooks->stageChanged($lead, 0, (int) $lead->lead_pipeline_stage_id));
    }

    public function leadUpdating($id): void
    {
        static::$stagesBefore[(int) $id] = DB::table('leads')->where('id', $id)->value('lead_pipeline_stage_id');
    }

    public function leadUpdated($lead): void
    {
        $before = (int) (static::$stagesBefore[(int) $lead->id] ?? 0);
        unset(static::$stagesBefore[(int) $lead->id]);

        if ($before && $before !== (int) $lead->lead_pipeline_stage_id) {
            $this->safely(fn () => $this->playbooks->stageChanged($lead, $before, (int) $lead->lead_pipeline_stage_id));
        }
    }

    /**
     * The playbook must never break the move itself.
     */
    protected function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            Log::error('Stage playbook failed: '.$e->getMessage());
        }
    }
}

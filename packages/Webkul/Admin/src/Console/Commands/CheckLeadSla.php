<?php

namespace Webkul\Admin\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Lead\Services\SlaEscalationService;

class CheckLeadSla extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:check-sla
                            {--dry-run : List overdue and escalated leads without updating them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark leads as SLA-overdue and escalate breached cases to Master Agent.';

    /**
     * Execute the console command.
     */
    public function handle(SlaEscalationService $escalationService): int
    {
        $dryRun = $this->option('dry-run');
        $now = Carbon::now();

        // 1. Find leads that are still "pending" and whose assigned_at + sla_hours < now.
        $driver = DB::connection()->getDriverName();
        $rawCondition = $driver === 'pgsql'
            ? "(assigned_at + (sla_hours || ' hours')::interval) < ?"
            : 'DATE_ADD(assigned_at, INTERVAL sla_hours HOUR) < ?';

        $overdue = DB::table('leads')
            ->where('sla_status', 'pending')
            ->whereNotNull('assigned_at')
            ->whereRaw($rawCondition, [$now])
            ->select('id', 'title', 'user_id', 'assigned_at', 'sla_hours')
            ->get();

        if ($overdue->isNotEmpty()) {
            $this->info("[SLA] Found {$overdue->count()} overdue lead(s).");

            if ($dryRun) {
                $this->table(
                    ['ID', 'Title', 'Assigned Agent', 'Assigned At', 'SLA Hours'],
                    $overdue->map(fn ($r) => [
                        $r->id,
                        $r->title,
                        $r->user_id,
                        $r->assigned_at,
                        $r->sla_hours,
                    ])->toArray()
                );
            } else {
                $ids = $overdue->pluck('id')->toArray();

                DB::table('leads')
                    ->whereIn('id', $ids)
                    ->update(['sla_status' => 'overdue']);

                // Also mark the linked pending SLA activities as overdue.
                DB::table('activities')
                    ->join('lead_activities', 'activities.id', '=', 'lead_activities.activity_id')
                    ->whereIn('lead_activities.lead_id', $ids)
                    ->where('activities.sla_activity_status', 'pending')
                    ->where('activities.is_done', false)
                    ->update(['activities.sla_activity_status' => 'overdue']);

                $this->info("[SLA] Marked {$overdue->count()} lead(s) as overdue.");
            }
        } else {
            $this->info('[SLA] No overdue leads found.');
        }

        // 2. Check for escalation to Master Agent
        if ($dryRun) {
            $this->info('[SLA] Dry run mode — skipping escalation processing.');
        } else {
            $escalatedCount = $escalationService->checkAndEscalate();

            if ($escalatedCount > 0) {
                $this->warn("[SLA] Escalated {$escalatedCount} lead(s) to Master Agent.");
            } else {
                $this->info('[SLA] No leads required escalation.');
            }
        }

        return self::SUCCESS;
    }
}

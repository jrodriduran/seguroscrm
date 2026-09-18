<?php

namespace Webkul\Admin\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Lead\Services\PolicyRetentionService;

class CheckGracePeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:check-grace-periods
                            {--dry-run : Review overdue policies without altering records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active policies for missed payments and enforce the ACA 90-day grace period lifecycle.';

    /**
     * Execute the console command.
     */
    public function handle(PolicyRetentionService $retentionService): int
    {
        $this->info('[Grace Period Scanner] Evaluating policy payments and grace periods...');

        $stats = $retentionService->evaluateGracePeriods();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Policies Evaluated', $stats['total_evaluated']],
                ['Kept Active (Paid Up)', $stats['active_kept']],
                ['Entered Grace Month 1 (Day 1-30)', $stats['grace_1_entered']],
                ['Entered Critical Grace (Day 31-90)', $stats['grace_critical_entered']],
                ['Lapsed / Cancelled (>90d)', $stats['lapsed_cancelled']],
                ['Agent Alert Tasks Created', $stats['alerts_created']],
            ]
        );

        $this->info('[Grace Period Scanner] Execution complete.');

        return self::SUCCESS;
    }
}

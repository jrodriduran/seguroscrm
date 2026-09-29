<?php

namespace Webkul\Teamwork\Console;

use Illuminate\Console\Command;
use Webkul\Teamwork\Services\AutomationEngine;

class RunAutomations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teamwork:run-automations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run date-based team automations (upcoming renewals, overdue cases)';

    /**
     * Execute the console command.
     */
    public function handle(AutomationEngine $engine): int
    {
        $this->info('Actions taken: '.$engine->runScheduled());

        return self::SUCCESS;
    }
}

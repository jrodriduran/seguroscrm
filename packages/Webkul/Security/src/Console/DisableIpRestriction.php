<?php

namespace Webkul\Security\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DisableIpRestriction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:disable-ip-restriction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emergency switch: turn off the admin IP allowlist';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        DB::table('core_config')
            ->where('code', 'general.security.ip_restriction.enabled')
            ->update(['value' => '0']);

        $this->info('IP restriction disabled.');

        return self::SUCCESS;
    }
}

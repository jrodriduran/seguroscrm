<?php

namespace Webkul\Platform\Console;

use Illuminate\Console\Command;
use Webkul\Platform\Services\PlatformState;
use Webkul\Platform\Services\SupportAccess;

/**
 * Operator tasks from the server (the SaaS scripts call these):
 *
 *   php artisan platform status                 show the state and health (JSON)
 *   php artisan platform status suspended --message="..."
 *   php artisan platform support-link [email]   one-time login link (1 minute)
 *   php artisan platform reset-owner [email] [--disable-2fa]
 *   php artisan platform setup-owner owner@agency.com --name="Ana Pérez" --timezone=America/New_York
 */
class PlatformCommand extends Command
{
    protected $signature = 'platform {action : status | support-link | reset-owner | setup-owner} {value? : status to set, or user email}
        {--message= : Message shown with warning / read_only / suspended}
        {--reason= : Why support is signing in (kept with the link)}
        {--disable-2fa : Also clear two-factor (lost phone)}
        {--name= : Owner name (setup-owner)}
        {--timezone= : Agency time zone (setup-owner)}';

    protected $description = 'SaaS operator actions: subscription state, support login link, owner setup and emergency reset';

    public function handle(PlatformState $state, SupportAccess $support): int
    {
        $value = $this->argument('value');

        switch ($this->argument('action')) {
            case 'status':
                if ($value) {
                    if (! in_array($value, PlatformState::STATUSES, true)) {
                        $this->error('Status must be one of: '.implode(', ', PlatformState::STATUSES));

                        return self::INVALID;
                    }

                    $state->set($value, $this->option('message'));
                }

                $this->line(json_encode($support->health($state), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                return self::SUCCESS;

            case 'support-link':
                $user = $support->owner($value);

                if (! $user) {
                    $this->error('User not found.');

                    return self::FAILURE;
                }

                $this->line($support->createLink($user, $this->option('reason')));
                $this->comment('Valid for '.config('platform.support_link_ttl', 60).' seconds, once, as '.$user->email.'.');

                return self::SUCCESS;

            case 'reset-owner':
                $user = $support->owner($value);

                if (! $user) {
                    $this->error('User not found.');

                    return self::FAILURE;
                }

                $password = $support->resetPassword($user, (bool) $this->option('disable-2fa'));

                $this->info('Temporary password for '.$user->email.': '.$password);
                $this->comment('Share it privately; ask them to change it at once (My account).');

                return self::SUCCESS;

            case 'setup-owner':
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->error('setup-owner needs the owner email.');

                    return self::INVALID;
                }

                [$user, $password] = $support->setupOwner($value, $this->option('name'), $this->option('timezone'));

                $this->info('Owner '.$user->email.' · temporary password: '.$password);

                return self::SUCCESS;
        }

        $this->error('Unknown action.');

        return self::INVALID;
    }
}

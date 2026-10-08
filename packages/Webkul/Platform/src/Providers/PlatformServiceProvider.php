<?php

namespace Webkul\Platform\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Platform\Console\PlatformCommand;
use Webkul\Platform\Http\Middleware\EnforceSubscription;
use Webkul\Platform\Services\PlatformState;

/**
 * The instance side of the SaaS: subscription state (warning, read only,
 * suspended), support login links, emergency owner reset and health, for
 * the operator's scripts and panel.
 */
class PlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/platform.php', 'platform');

        $this->app->scoped(PlatformState::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'platform');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'platform');

        $this->loadRoutesFrom(__DIR__.'/../Routes/routes.php');

        $this->app['router']->pushMiddlewareToGroup('web', EnforceSubscription::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PlatformCommand::class]);
        }
    }
}

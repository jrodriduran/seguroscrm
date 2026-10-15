<?php

namespace Webkul\Insurance\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Admin\Http\Controllers\Insurance\HealthSherpaBridgeController;
use Webkul\Admin\Http\Controllers\Quote\QuoteController;
use Webkul\Insurance\Http\Controllers\HealthSherpaController;
use Webkul\Insurance\Http\Controllers\InsuranceQuoteController;
use Webkul\Insurance\Services\RetiredHealthSherpaBridge;
use Webkul\Lead\Services\HealthSherpaBridgeService;

class InsuranceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Keep the existing routes, middleware and ACL, extending only conversion.
        $this->app->bind(QuoteController::class, InsuranceQuoteController::class);
        $this->app->bind(HealthSherpaBridgeController::class, HealthSherpaController::class);
        $this->app->bind(HealthSherpaBridgeService::class, RetiredHealthSherpaBridge::class);
        $this->mergeConfigFrom(__DIR__.'/../Config/healthsherpa.php', 'healthsherpa');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
    }

    public function boot(): void
    {
        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path'))
            ->group(__DIR__.'/../Routes/admin.php');
    }
}

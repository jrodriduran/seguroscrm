<?php

namespace Webkul\Insurance\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Admin\Http\Controllers\Quote\QuoteController;
use Webkul\Insurance\Http\Controllers\InsuranceQuoteController;

class InsuranceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Keep the existing routes, middleware and ACL, extending only conversion.
        $this->app->bind(QuoteController::class, InsuranceQuoteController::class);
    }
}

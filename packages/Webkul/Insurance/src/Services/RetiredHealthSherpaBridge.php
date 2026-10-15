<?php

namespace Webkul\Insurance\Services;

use Illuminate\Validation\ValidationException;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Services\HealthSherpaBridgeService;

class RetiredHealthSherpaBridge extends HealthSherpaBridgeService
{
    public function generateBridgeData(Lead $lead): array
    {
        throw ValidationException::withMessages([
            'healthsherpa' => trans('admin::app.healthsherpa.bridge-retired'),
        ]);
    }
}

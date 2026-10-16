<?php

namespace Webkul\Insurance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Insurance\CrossSellController;

class InsuranceCrossSellController extends CrossSellController
{
    public function evaluate(int $leadId): JsonResponse
    {
        return $this->index($leadId);
    }
}

<?php

namespace Webkul\Insurance\Console;

use Illuminate\Console\Command;
use Webkul\Insurance\Services\HouseholdTransitions;

class ReviewHouseholdAges extends Command
{
    protected $signature = 'insurance:review-household-ages';

    protected $description = 'Create internal age-26 review tasks without ending any coverage';

    public function handle(HouseholdTransitions $transitions): int
    {
        $this->info((string) $transitions->reviewAges());

        return self::SUCCESS;
    }
}

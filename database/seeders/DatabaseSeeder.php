<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as KrayinDatabaseSeeder;

require_once __DIR__.'/InsuranceTypeSeeder.php';
require_once __DIR__.'/InsuranceAttributesSeeder.php';
require_once __DIR__.'/InsuranceCarriersSeeder.php';
require_once __DIR__.'/InsurancePipelineSeeder.php';
require_once __DIR__.'/InsuranceProductsSeeder.php';

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        ->call(KrayinDatabaseSeeder::class);
        ->call(InsuranceTypeSeeder::class);
        ->call(InsuranceAttributesSeeder::class);
        ->call(InsuranceCarriersSeeder::class);
        ->call(InsurancePipelineSeeder::class);
        ->call(InsuranceProductsSeeder::class);
    }
}

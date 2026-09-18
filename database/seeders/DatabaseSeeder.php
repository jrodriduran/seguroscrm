<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as KrayinDatabaseSeeder;

require_once __DIR__.'/InsuranceTypeSeeder.php';
require_once __DIR__.'/InsuranceAttributesSeeder.php';
require_once __DIR__.'/InsuranceCarriersSeeder.php';
require_once __DIR__.'/InsurancePipelineSeeder.php';
require_once __DIR__.'/InsuranceProductsSeeder.php';
require_once __DIR__.'/InsuranceSourcesSeeder.php';
require_once __DIR__.'/InsurancePolicyAttributesSeeder.php';
require_once __DIR__.'/InsuranceEmailTemplatesSeeder.php';
require_once __DIR__.'/InsuranceWorkflowsSeeder.php';
require_once __DIR__.'/TestEnvironmentSeeder.php';

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(KrayinDatabaseSeeder::class);
        $this->call(InsuranceTypeSeeder::class);
        $this->call(InsuranceAttributesSeeder::class);
        $this->call(InsuranceCarriersSeeder::class);
        $this->call(InsurancePipelineSeeder::class);
        $this->call(InsuranceProductsSeeder::class);
        $this->call(InsuranceSourcesSeeder::class);
        $this->call(InsurancePolicyAttributesSeeder::class);
        $this->call(InsuranceEmailTemplatesSeeder::class);
        $this->call(InsuranceWorkflowsSeeder::class);
        $this->call(TestEnvironmentSeeder::class);
    }
}

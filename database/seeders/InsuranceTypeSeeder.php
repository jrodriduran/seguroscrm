<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'ACA / Obamacare',
                'description' => 'Qualified health insurance with federal subsidy (APTC) for individuals and families.',
            ],
            [
                'name' => 'Medicare Advantage',
                'description' => 'All-in-one Medicare plans with dental, vision and prescription drugs.',
            ],
            [
                'name' => 'Medicare Supplement (Medigap)',
                'description' => 'Private insurance to cover out-of-pocket gaps in Original Medicare.',
            ],
            [
                'name' => 'Life Insurance (IUL / Term)',
                'description' => 'Life insurance protection with death benefit and cash value accumulation.',
            ],
            [
                'name' => 'Final Expense',
                'description' => 'Simplified whole life insurance to cover funeral costs and final medical bills.',
            ],
            [
                'name' => 'Dental & Vision',
                'description' => 'Individual or family plans for dental checkups, treatments, and vision care.',
            ],
            [
                'name' => 'Hospital Indemnity',
                'description' => 'Supplemental policies paying direct cash benefits for hospital stays or injuries.',
            ],
            [
                'name' => 'Private & International Health',
                'description' => 'Major medical insurance for expatriates, travel, or off-exchange private plans.',
            ],
        ];

        foreach ($types as $type) {
            DB::table('lead_types')->updateOrInsert(
                ['name' => $type['name']],
                [
                    'description' => $type['description'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}

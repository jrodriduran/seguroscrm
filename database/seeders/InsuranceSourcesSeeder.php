<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceSourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $sources = [
            'Facebook & Instagram Ads',
            'Google Search / Ads',
            'TikTok Ads',
            'Landing Page / Web Form',
            'Client Referral',
            'Partner / Broker Referral',
            'Inbound Call',
            'Health Fair / Community Event',
            'Office Walk-in',
            'Direct Mail',
            'Email',
            'Website',
            'Phone Call',
            'Direct',
        ];

        foreach ($sources as $sourceName) {
            $existing = DB::table('lead_sources')
                ->where('name', $sourceName)
                ->first();

            if (! $existing) {
                DB::table('lead_sources')->insert([
                    'name' => $sourceName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsurancePipelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // 1. Clean up & consolidate existing pipelines in database
        $existingPipelines = DB::table('lead_pipelines')->get();

        foreach ($existingPipelines as $p) {
            $lower = strtolower($p->name);
            $canonicalName = null;

            if (str_contains($lower, 'obamacare') || str_contains($lower, 'aca')) {
                $canonicalName = 'ACA / Obamacare (Health)';
            } elseif (str_contains($lower, 'medicare')) {
                $canonicalName = 'Medicare (Advantage & Supplement)';
            } elseif (str_contains($lower, 'vida') || str_contains($lower, 'final expense') || str_contains($lower, 'gastos finales') || str_contains($lower, 'life')) {
                $canonicalName = 'Life & Final Expense';
            } elseif (str_contains($lower, 'flujo') || str_contains($lower, 'general') || str_contains($lower, 'default')) {
                $canonicalName = 'General Insurance';
            }

            if ($canonicalName && $canonicalName !== $p->name) {
                $targetExists = DB::table('lead_pipelines')->where('name', $canonicalName)->first();
                if (! $targetExists) {
                    DB::table('lead_pipelines')->where('id', $p->id)->update([
                        'name' => $canonicalName,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // 2. Remove redundant empty duplicate pipelines if any
        $uniquePipelines = [
            'ACA / Obamacare (Health)',
            'Medicare (Advantage & Supplement)',
            'Life & Final Expense',
            'General Insurance',
        ];

        foreach ($uniquePipelines as $uName) {
            $duplicates = DB::table('lead_pipelines')->where('name', $uName)->orderBy('id')->get();
            if ($duplicates->count() > 1) {
                $keepId = $duplicates->first()->id;
                foreach ($duplicates->slice(1) as $dup) {
                    $leadsCount = DB::table('leads')->where('lead_pipeline_id', $dup->id)->count();
                    if ($leadsCount === 0) {
                        DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $dup->id)->delete();
                        DB::table('lead_pipelines')->where('id', $dup->id)->delete();
                    }
                }
            }
        }

        // 3. Define standard pipelines and stages with canonical English names
        $pipelines = [
            [
                'name' => 'ACA / Obamacare (Health)',
                'is_default' => 1,
                'rotten_days' => 30,
                'stages' => [
                    [
                        'code' => 'new',
                        'name' => 'New Lead',
                        'probability' => 10,
                        'sort_order' => 1,
                    ],
                    [
                        'code' => 'census',
                        'name' => 'Income & Household Qualified',
                        'probability' => 25,
                        'sort_order' => 2,
                    ],
                    [
                        'code' => 'quoted',
                        'name' => 'Quoted & Plan Selected',
                        'probability' => 50,
                        'sort_order' => 3,
                    ],
                    [
                        'code' => 'consent_docs',
                        'name' => 'Consent & Documents',
                        'probability' => 75,
                        'sort_order' => 4,
                    ],
                    [
                        'code' => 'won',
                        'name' => 'Policy Issued (Won)',
                        'probability' => 100,
                        'sort_order' => 5,
                    ],
                    [
                        'code' => 'lost',
                        'name' => 'Lost',
                        'probability' => 0,
                        'sort_order' => 6,
                    ],
                ],
            ],
            [
                'name' => 'Medicare (Advantage & Supplement)',
                'is_default' => 0,
                'rotten_days' => 45,
                'stages' => [
                    [
                        'code' => 'new',
                        'name' => 'New Lead',
                        'probability' => 10,
                        'sort_order' => 1,
                    ],
                    [
                        'code' => 'soa',
                        'name' => 'Scope of Appointment (SOA) Signed',
                        'probability' => 30,
                        'sort_order' => 2,
                    ],
                    [
                        'code' => 'needs',
                        'name' => 'Needs Assessment',
                        'probability' => 50,
                        'sort_order' => 3,
                    ],
                    [
                        'code' => 'presentation',
                        'name' => 'Plan Presentation',
                        'probability' => 70,
                        'sort_order' => 4,
                    ],
                    [
                        'code' => 'won',
                        'name' => 'Enrolled (Won)',
                        'probability' => 100,
                        'sort_order' => 5,
                    ],
                    [
                        'code' => 'lost',
                        'name' => 'Lost',
                        'probability' => 0,
                        'sort_order' => 6,
                    ],
                ],
            ],
            [
                'name' => 'Life & Final Expense',
                'is_default' => 0,
                'rotten_days' => 45,
                'stages' => [
                    [
                        'code' => 'new',
                        'name' => 'New Lead',
                        'probability' => 10,
                        'sort_order' => 1,
                    ],
                    [
                        'code' => 'needs',
                        'name' => 'Needs Assessment',
                        'probability' => 30,
                        'sort_order' => 2,
                    ],
                    [
                        'code' => 'underwriting',
                        'name' => 'Pre-Underwriting',
                        'probability' => 50,
                        'sort_order' => 3,
                    ],
                    [
                        'code' => 'proposal',
                        'name' => 'Proposal Presented',
                        'probability' => 70,
                        'sort_order' => 4,
                    ],
                    [
                        'code' => 'won',
                        'name' => 'Policy Issued (Won)',
                        'probability' => 100,
                        'sort_order' => 5,
                    ],
                    [
                        'code' => 'lost',
                        'name' => 'Lost',
                        'probability' => 0,
                        'sort_order' => 6,
                    ],
                ],
            ],
            [
                'name' => 'General Insurance',
                'is_default' => 0,
                'rotten_days' => 30,
                'stages' => [
                    [
                        'code' => 'new',
                        'name' => 'New Lead',
                        'probability' => 10,
                        'sort_order' => 1,
                    ],
                    [
                        'code' => 'contact',
                        'name' => 'Contact Made',
                        'probability' => 25,
                        'sort_order' => 2,
                    ],
                    [
                        'code' => 'quoted',
                        'name' => 'Quoted',
                        'probability' => 50,
                        'sort_order' => 3,
                    ],
                    [
                        'code' => 'closing',
                        'name' => 'Closing',
                        'probability' => 75,
                        'sort_order' => 4,
                    ],
                    [
                        'code' => 'won',
                        'name' => 'Won',
                        'probability' => 100,
                        'sort_order' => 5,
                    ],
                    [
                        'code' => 'lost',
                        'name' => 'Lost',
                        'probability' => 0,
                        'sort_order' => 6,
                    ],
                ],
            ],
        ];

        // Ensure ACA / Obamacare is set as the default pipeline
        DB::table('lead_pipelines')->update(['is_default' => 0]);

        foreach ($pipelines as $pData) {
            $stages = $pData['stages'];
            unset($pData['stages']);

            $existing = DB::table('lead_pipelines')->where('name', $pData['name'])->first();

            if ($existing) {
                DB::table('lead_pipelines')->where('id', $existing->id)->update([
                    'is_default' => $pData['is_default'],
                    'rotten_days' => $pData['rotten_days'],
                    'updated_at' => $now,
                ]);
                $pipelineId = $existing->id;
            } else {
                $pipelineId = DB::table('lead_pipelines')->insertGetId(array_merge($pData, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }

            foreach ($stages as $stg) {
                $stageExists = DB::table('lead_pipeline_stages')
                    ->where('lead_pipeline_id', $pipelineId)
                    ->where('code', $stg['code'])
                    ->first();

                if ($stageExists) {
                    DB::table('lead_pipeline_stages')
                        ->where('id', $stageExists->id)
                        ->update([
                            'name' => $stg['name'],
                            'probability' => $stg['probability'],
                            'sort_order' => $stg['sort_order'],
                        ]);
                } else {
                    DB::table('lead_pipeline_stages')->insert([
                        'code' => $stg['code'],
                        'name' => $stg['name'],
                        'probability' => $stg['probability'],
                        'sort_order' => $stg['sort_order'],
                        'lead_pipeline_id' => $pipelineId,
                    ]);
                }
            }
        }
    }
}

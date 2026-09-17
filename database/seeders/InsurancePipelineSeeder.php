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

        $pipelines = [
            [
                'name'        => 'ACA / Obamacare (Health)',
                'is_default'  => 1,
                'rotten_days' => 30,
                'stages'      => [
                    [
                        'code'        => 'new',
                        'name'        => 'New Lead',
                        'probability' => 10,
                        'sort_order'  => 1,
                    ],
                    [
                        'code'        => 'census',
                        'name'        => 'Income & Household Qualified',
                        'probability' => 25,
                        'sort_order'  => 2,
                    ],
                    [
                        'code'        => 'quoted',
                        'name'        => 'Quoted & Plan Selected',
                        'probability' => 50,
                        'sort_order'  => 3,
                    ],
                    [
                        'code'        => 'consent_docs',
                        'name'        => 'Consent & Documents',
                        'probability' => 75,
                        'sort_order'  => 4,
                    ],
                    [
                        'code'        => 'won',
                        'name'        => 'Policy Issued (Won)',
                        'probability' => 100,
                        'sort_order'  => 5,
                    ],
                    [
                        'code'        => 'lost',
                        'name'        => 'Lost',
                        'probability' => 0,
                        'sort_order'  => 6,
                    ],
                ],
            ],
            [
                'name'        => 'Medicare (Advantage & Supplement)',
                'is_default'  => 0,
                'rotten_days' => 45,
                'stages'      => [
                    [
                        'code'        => 'new',
                        'name'        => 'New Lead',
                        'probability' => 10,
                        'sort_order'  => 1,
                    ],
                    [
                        'code'        => 'soa',
                        'name'        => 'Scope of Appointment (SOA) Signed',
                        'probability' => 30,
                        'sort_order'  => 2,
                    ],
                    [
                        'code'        => 'needs',
                        'name'        => 'Needs Assessment',
                        'probability' => 50,
                        'sort_order'  => 3,
                    ],
                    [
                        'code'        => 'presentation',
                        'name'        => 'Plan Presentation',
                        'probability' => 70,
                        'sort_order'  => 4,
                    ],
                    [
                        'code'        => 'won',
                        'name'        => 'Enrolled (Won)',
                        'probability' => 100,
                        'sort_order'  => 5,
                    ],
                    [
                        'code'        => 'lost',
                        'name'        => 'Lost',
                        'probability' => 0,
                        'sort_order'  => 6,
                    ],
                ],
            ],
            [
                'name'        => 'Life & Final Expense',
                'is_default'  => 0,
                'rotten_days' => 45,
                'stages'      => [
                    [
                        'code'        => 'new',
                        'name'        => 'New Lead',
                        'probability' => 10,
                        'sort_order'  => 1,
                    ],
                    [
                        'code'        => 'needs',
                        'name'        => 'Needs Assessment',
                        'probability' => 30,
                        'sort_order'  => 2,
                    ],
                    [
                        'code'        => 'underwriting',
                        'name'        => 'Pre-Underwriting',
                        'probability' => 50,
                        'sort_order'  => 3,
                    ],
                    [
                        'code'        => 'proposal',
                        'name'        => 'Proposal Presented',
                        'probability' => 70,
                        'sort_order'  => 4,
                    ],
                    [
                        'code'        => 'won',
                        'name'        => 'Policy Issued (Won)',
                        'probability' => 100,
                        'sort_order'  => 5,
                    ],
                    [
                        'code'        => 'lost',
                        'name'        => 'Lost',
                        'probability' => 0,
                        'sort_order'  => 6,
                    ],
                ],
            ],
            [
                'name'        => 'General Insurance',
                'is_default'  => 0,
                'rotten_days' => 30,
                'stages'      => [
                    [
                        'code'        => 'new',
                        'name'        => 'New Lead',
                        'probability' => 10,
                        'sort_order'  => 1,
                    ],
                    [
                        'code'        => 'contact',
                        'name'        => 'Contact Made',
                        'probability' => 25,
                        'sort_order'  => 2,
                    ],
                    [
                        'code'        => 'quoted',
                        'name'        => 'Quoted',
                        'probability' => 50,
                        'sort_order'  => 3,
                    ],
                    [
                        'code'        => 'closing',
                        'name'        => 'Closing',
                        'probability' => 75,
                        'sort_order'  => 4,
                    ],
                    [
                        'code'        => 'won',
                        'name'        => 'Won',
                        'probability' => 100,
                        'sort_order'  => 5,
                    ],
                    [
                        'code'        => 'lost',
                        'name'        => 'Lost',
                        'probability' => 0,
                        'sort_order'  => 6,
                    ],
                ],
            ],
        ];

        // Clean up previously seeded bilingual names if they exist
        $cleanupMap = [
            'ACA / Obamacare (Salud Individual y Familiar)' => 'ACA / Obamacare (Health)',
            'Medicare (Advantage & Suplementario)'          => 'Medicare (Advantage & Supplement)',
            'Seguros de Vida y Gastos Finales (Life & Annuities)' => 'Life & Final Expense',
            'Flujo General de Seguros / General Insurance'  => 'General Insurance',
            'Default Pipeline'                             => 'General Insurance',
        ];

        foreach ($cleanupMap as $oldName => $newName) {
            DB::table('lead_pipelines')->where('name', $oldName)->update([
                'name'       => $newName,
                'updated_at' => $now,
            ]);
        }

        foreach ($pipelines as $pData) {
            $stages = $pData['stages'];
            unset($pData['stages']);

            $existing = DB::table('lead_pipelines')->where('name', $pData['name'])->first();

            if ($existing) {
                DB::table('lead_pipelines')->where('id', $existing->id)->update([
                    'is_default'  => $pData['is_default'],
                    'rotten_days' => $pData['rotten_days'],
                    'updated_at'  => $now,
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
                            'name'        => $stg['name'],
                            'probability' => $stg['probability'],
                            'sort_order'  => $stg['sort_order'],
                        ]);
                } else {
                    DB::table('lead_pipeline_stages')->insert([
                        'code'             => $stg['code'],
                        'name'             => $stg['name'],
                        'probability'      => $stg['probability'],
                        'sort_order'       => $stg['sort_order'],
                        'lead_pipeline_id' => $pipelineId,
                    ]);
                }
            }
        }
    }
}

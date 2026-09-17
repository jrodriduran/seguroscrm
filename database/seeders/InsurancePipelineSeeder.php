<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsurancePipelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $pipelines = [
            [
                'name' => 'ACA / Obamacare (Salud Individual y Familiar)',
                'is_default' => 1,
                'rotten_days' => 30,
                'stages' => [
                    [
                        'code' => 'new',
                        'name' => 'Nuevo Prospecto / New Lead',
                        'probability' => 10,
                        'sort_order' => 1,
                    ],
                    [
                        'code' => 'contact',
                        'name' => 'Contacto Realizado / Contact Made',
                        'probability' => 25,
                        'sort_order' => 2,
                    ],
                    [
                        'code' => 'census',
                        'name' => 'Calificación y Censo Familiar / Income & Household',
                        'probability' => 45,
                        'sort_order' => 3,
                    ],
                    [
                        'code' => 'quoted',
                        'name' => 'Cotizado y Plan Elegido / Quoted & Plan Selected',
                        'probability' => 65,
                        'sort_order' => 4,
                    ],
                    [
                        'code' => 'consent_docs',
                        'name' => 'Consentimiento y Documentos / Consent & Docs',
                        'probability' => 80,
                        'sort_order' => 5,
                    ],
                    [
                        'code' => 'won',
                        'name' => 'Póliza Emitida / Enrolled (Won)',
                        'probability' => 100,
                        'sort_order' => 6,
                    ],
                    [
                        'code' => 'lost',
                        'name' => 'No Calificado / Perdido (Lost)',
                        'probability' => 0,
                        'sort_order' => 7,
                    ],
                ],
            ],
            [
                'name' => 'Medicare (Advantage & Suplementario)',
                'is_default' => 0,
                'rotten_days' => 45,
                'stages' => [
                    [
                        'code' => 'new',
                        'name' => 'Nuevo Prospecto / New Lead (T65 / AEP)',
                        'probability' => 10,
                        'sort_order' => 1,
                    ],
                    [
                        'code' => 'soa',
                        'name' => 'Scope of Appointment (SOA) Firmado',
                        'probability' => 30,
                        'sort_order' => 2,
                    ],
                    [
                        'code' => 'needs',
                        'name' => 'Revisión MBI, Médicos y Medicinas / Needs',
                        'probability' => 50,
                        'sort_order' => 3,
                    ],
                    [
                        'code' => 'presentation',
                        'name' => 'Presentación de Plan (HMO/PPO/Medigap)',
                        'probability' => 70,
                        'sort_order' => 4,
                    ],
                    [
                        'code' => 'won',
                        'name' => 'Inscripción Sometida / Enrolled (Won)',
                        'probability' => 100,
                        'sort_order' => 5,
                    ],
                    [
                        'code' => 'lost',
                        'name' => 'No Elegible / Perdido (Lost)',
                        'probability' => 0,
                        'sort_order' => 6,
                    ],
                ],
            ],
            [
                'name' => 'Seguros de Vida y Gastos Finales (Life & Annuities)',
                'is_default' => 0,
                'rotten_days' => 45,
                'stages' => [
                    [
                        'code' => 'new',
                        'name' => 'Nuevo Prospecto / New Lead',
                        'probability' => 10,
                        'sort_order' => 1,
                    ],
                    [
                        'code' => 'needs',
                        'name' => 'Análisis Financiero de Necesidades / Needs Analysis',
                        'probability' => 30,
                        'sort_order' => 2,
                    ],
                    [
                        'code' => 'underwriting',
                        'name' => 'Pre-Suscripción Médica / Health Screening',
                        'probability' => 50,
                        'sort_order' => 3,
                    ],
                    [
                        'code' => 'proposal',
                        'name' => 'Propuesta y Cotización / Proposal Presented',
                        'probability' => 70,
                        'sort_order' => 4,
                    ],
                    [
                        'code' => 'won',
                        'name' => 'Póliza Aprobada y Emitida / Policy Issued (Won)',
                        'probability' => 100,
                        'sort_order' => 5,
                    ],
                    [
                        'code' => 'lost',
                        'name' => 'Declinado / Perdido (Lost)',
                        'probability' => 0,
                        'sort_order' => 6,
                    ],
                ],
            ],
            [
                'name' => 'Flujo General de Seguros / General Insurance',
                'is_default' => 0,
                'rotten_days' => 30,
                'stages' => [
                    [
                        'code' => 'new',
                        'name' => 'Nuevo Prospecto / New Lead',
                        'probability' => 10,
                        'sort_order' => 1,
                    ],
                    [
                        'code' => 'contact',
                        'name' => 'Contacto Inicial / Contact Made',
                        'probability' => 25,
                        'sort_order' => 2,
                    ],
                    [
                        'code' => 'quoted',
                        'name' => 'Calificado y Cotizado / Quoted',
                        'probability' => 50,
                        'sort_order' => 3,
                    ],
                    [
                        'code' => 'closing',
                        'name' => 'En Trámite de Cierre / Closing & Docs',
                        'probability' => 75,
                        'sort_order' => 4,
                    ],
                    [
                        'code' => 'won',
                        'name' => 'Póliza Emitida / Won',
                        'probability' => 100,
                        'sort_order' => 5,
                    ],
                    [
                        'code' => 'lost',
                        'name' => 'Perdido / Cancelado (Lost)',
                        'probability' => 0,
                        'sort_order' => 6,
                    ],
                ],
            ],
        ];

        // Check if there is an initial generic pipeline with ID 1
        $firstPipeline = DB::table('lead_pipelines')->where('id', 1)->first();
        if ($firstPipeline && in_array(strtolower($firstPipeline->name), ['default', 'por defecto', 'predeterminado', 'padrão'])) {
            DB::table('lead_pipelines')->where('id', 1)->update([
                'name' => 'Flujo General de Seguros / General Insurance',
                'updated_at' => $now,
            ]);
        }

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

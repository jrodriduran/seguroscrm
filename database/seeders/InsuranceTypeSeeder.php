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
                'name'        => 'Obamacare (ACA)',
                'description' => 'Seguro de salud bajo la Ley de Cuidado de Salud a Bajo Precio con subsidio federal (APTC).',
            ],
            [
                'name'        => 'Medicare Advantage (Parte C)',
                'description' => 'Planes de salud privados aprobados por Medicare con cobertura médica y de medicamentos.',
            ],
            [
                'name'        => 'Medicare Suplementario (Medigap)',
                'description' => 'Pólizas privadas que cubren los costos compartidos y deducibles de Medicare Original.',
            ],
            [
                'name'        => 'Seguro de Vida (IUL / Término)',
                'description' => 'Protección financiera familiar por fallecimiento y acumulación de valor en efectivo.',
            ],
            [
                'name'        => 'Gastos Finales (Final Expense)',
                'description' => 'Pólizas de vida entera simplificadas para cubrir costos de funeral y deudas médicas.',
            ],
            [
                'name'        => 'Dental y Visión',
                'description' => 'Planes individuales o familiares para servicios odontológicos, limpiezas y lentes.',
            ],
            [
                'name'        => 'Indemnización Hospitalaria / Accidentes',
                'description' => 'Pólizas suplementarias con pagos directos en efectivo por hospitalización o lesiones.',
            ],
            [
                'name'        => 'Seguro Privado / Internacional',
                'description' => 'Planes médicos mayores para viajes, nómadas digitales o fuera del marketplace ACA.',
            ],
        ];

        foreach ($types as $type) {
            DB::table('lead_types')->updateOrInsert(
                ['name' => $type['name']],
                [
                    'description' => $type['description'],
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }
    }
}
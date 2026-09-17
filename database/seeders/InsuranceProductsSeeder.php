<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         = Carbon::now();

        // 1. Define custom attributes for products (Planes / Paquetes de Pólizas)
         = [
            [
                'code'            => 'carrier_id',
                'name'            => 'Aseguradora / Carrier',
                'type'            => 'lookup',
                'entity_type'     => 'products',
                'lookup_type'     => 'organizations',
                'validation'      => null,
                'sort_order'      => 6,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'insurance_line',
                'name'            => 'Ramo de Seguro / Insurance Line',
                'type'            => 'select',
                'entity_type'     => 'products',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 7,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [
                    'ACA / Obamacare (Salud)',
                    'Medicare Advantage (Part C)',
                    'Medicare Supplement (Medigap)',
                    'Medicare Prescription (Part D)',
                    'Vida IUL / Universal Life',
                    'Vida Término / Term Life',
                    'Gastos Finales / Final Expense',
                    'Dental y Visión',
                    'Indemnización Hospitalaria',
                    'Accidentes y Enfermedades Críticas',
                ],
            ],
            [
                'code'            => 'metal_tier',
                'name'            => 'Nivel de Cobertura / Metal Tier',
                'type'            => 'select',
                'entity_type'     => 'products',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 8,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [
                    'Bronce / Bronze',
                    'Plata / Silver (CSR)',
                    'Oro / Gold',
                    'Platino / Platinum',
                    'Catastrófico / Catastrophic',
                    'No Aplica / N/A',
                ],
            ],
            [
                'code'            => 'network_type',
                'name'            => 'Red Médica / Network',
                'type'            => 'select',
                'entity_type'     => 'products',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 9,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [
                    'HMO (Health Maintenance Org)',
                    'PPO (Preferred Provider Org)',
                    'EPO (Exclusive Provider Org)',
                    'POS (Point of Service)',
                    'Indemnización / FFS',
                    'No Aplica / N/A',
                ],
            ],
            [
                'code'            => 'deductible',
                'name'            => 'Deducible Individual ($) / Deductible',
                'type'            => 'price',
                'entity_type'     => 'products',
                'lookup_type'     => null,
                'validation'      => 'decimal',
                'sort_order'      => 10,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'max_out_of_pocket',
                'name'            => 'Máximo de Bolsillo ($) / MOOP',
                'type'            => 'price',
                'entity_type'     => 'products',
                'lookup_type'     => null,
                'validation'      => 'decimal',
                'sort_order'      => 11,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'primary_care_copay',
                'name'            => 'Copago Médico Primario (PCP)',
                'type'            => 'text',
                'entity_type'     => 'products',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 12,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'specialist_copay',
                'name'            => 'Copago Especialista',
                'type'            => 'text',
                'entity_type'     => 'products',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 13,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'plan_year',
                'name'            => 'Año del Plan / Plan Year',
                'type'            => 'text',
                'entity_type'     => 'products',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 14,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [],
            ],
        ];

         = [];
         = [];

        foreach ( as ) {
             = ['options'];
            unset(['options']);

             = DB::table('attributes')
                ->where('code', ['code'])
                ->where('entity_type', ['entity_type'])
                ->first();

            if () {
                DB::table('attributes')
                    ->where('id', ->id)
                    ->update(array_merge(, ['updated_at' => ]));
                 = ->id;
            } else {
                 = DB::table('attributes')->insertGetId(
                    array_merge(, ['created_at' => , 'updated_at' => ])
                );
            }

            [['code']] = ;

            if (! empty() && in_array(['type'], ['select', 'multiselect'])) {
                 = 1;
                foreach ( as ) {
                     = DB::table('attribute_options')
                        ->where('attribute_id', )
                        ->where('name', )
                        ->first();

                    if (! ) {
                         = DB::table('attribute_options')->insertGetId([
                            'attribute_id' => ,
                            'name'         => ,
                            'sort_order'   => ++,
                        ]);
                    } else {
                         = ->id;
                    }

                    [['code']][] = ;
                }
            }
        }

        // Helper map of carriers
         = DB::table('organizations')->pluck('id', 'name');

        // 2. Real-World Insurance Plans / Policy Packages
         = [
            [
                'sku'                => 'FB-SLV-1410',
                'name'               => 'Florida Blue - BlueOptions Silver 1410',
                'description'        => 'Plan ACA Plata con Reducción de Costos Compartidos (CSR). Excelente cobertura médica con copagos bajos y deducible .',
                'price'              => 450.00,
                'carrier_name'       => 'Florida Blue (BCBS Florida)',
                'line'               => 'ACA / Obamacare (Salud)',
                'tier'               => 'Plata / Silver (CSR)',
                'network'            => 'PPO (Preferred Provider Org)',
                'deductible'         => 0.00,
                'moop'               => 3000.00,
                'pcp'                => ' Copago',
                'specialist'         => ' Copago',
                'year'               => '2026',
            ],
            [
                'sku'                => 'FB-BRZ-1422',
                'name'               => 'Florida Blue - BlueCare Bronze 1422',
                'description'        => 'Plan ACA Bronce económico diseñado para protección ante gastos médicos mayores con prima mensual reducida.',
                'price'              => 380.00,
                'carrier_name'       => 'Florida Blue (BCBS Florida)',
                'line'               => 'ACA / Obamacare (Salud)',
                'tier'               => 'Bronce / Bronze',
                'network'            => 'HMO (Health Maintenance Org)',
                'deductible'         => 7500.00,
                'moop'               => 9100.00,
                'pcp'                => ' Copago',
                'specialist'         => ' Copago',
                'year'               => '2026',
            ],
            [
                'sku'                => 'AMB-SLV-STD',
                'name'               => 'Ambetter - Clear Silver (Standard CSR)',
                'description'        => 'Plan ACA Plata de Ambetter con cobertura integral de medicamentos recetados y red de clínicas preferidas.',
                'price'              => 420.00,
                'carrier_name'       => 'Ambetter (Centene Corporation)',
                'line'               => 'ACA / Obamacare (Salud)',
                'tier'               => 'Plata / Silver (CSR)',
                'network'            => 'EPO (Exclusive Provider Org)',
                'deductible'         => 500.00,
                'moop'               => 2900.00,
                'pcp'                => ' Copago',
                'specialist'         => ' Copago',
                'year'               => '2026',
            ],
            [
                'sku'                => 'OSC-SLV-NXT',
                'name'               => 'Oscar Health - Classic Silver Next',
                'description'        => 'Plan tecnológico con telemedicina 24/7 sin costo, red de especialistas amplia y gestión fácil desde la app.',
                'price'              => 435.00,
                'carrier_name'       => 'Oscar Health',
                'line'               => 'ACA / Obamacare (Salud)',
                'tier'               => 'Plata / Silver (CSR)',
                'network'            => 'EPO (Exclusive Provider Org)',
                'deductible'         => 0.00,
                'moop'               => 2500.00,
                'pcp'                => ' Virtual /  PCP',
                'specialist'         => ' Copago',
                'year'               => '2026',
            ],
            [
                'sku'                => 'HUM-MA-HMO1',
                'name'               => 'Humana - Gold Plus HMO (H1036-001)',
                'description'        => 'Medicare Advantage HMO con prima mensual de , beneficios dentales completos, lentes, audífonos y tarjeta de comida/OTC.',
                'price'              => 0.00,
                'carrier_name'       => 'Humana',
                'line'               => 'Medicare Advantage (Part C)',
                'tier'               => 'No Aplica / N/A',
                'network'            => 'HMO (Health Maintenance Org)',
                'deductible'         => 0.00,
                'moop'               => 3400.00,
                'pcp'                => ' Copago',
                'specialist'         => ' Copago',
                'year'               => '2026',
            ],
            [
                'sku'                => 'UHC-MA-PPO1',
                'name'               => 'UnitedHealthcare - AARP Medicare Advantage Choice (PPO)',
                'description'        => 'Medicare Advantage con red PPO flexible que permite ver doctores fuera de red sin referidos y crédito OTC mensual.',
                'price'              => 0.00,
                'carrier_name'       => 'UnitedHealthcare (UHC / Golden Rule)',
                'line'               => 'Medicare Advantage (Part C)',
                'tier'               => 'No Aplica / N/A',
                'network'            => 'PPO (Preferred Provider Org)',
                'deductible'         => 0.00,
                'moop'               => 3900.00,
                'pcp'                => ' Copago',
                'specialist'         => ' Copago',
                'year'               => '2026',
            ],
            [
                'sku'                => 'MOO-MED-PLANG',
                'name'               => 'Mutual of Omaha - Medicare Supplement Plan G',
                'description'        => 'Póliza Medigap Plan G. Cubre el 100% de los gastos hospitalarios y médicos no cubiertos por Medicare Original (solo paga deducible Parte B).',
                'price'              => 165.00,
                'carrier_name'       => 'Mutual of Omaha',
                'line'               => 'Medicare Supplement (Medigap)',
                'tier'               => 'No Aplica / N/A',
                'network'            => 'Indemnización / FFS',
                'deductible'         => 240.00,
                'moop'               => 0.00,
                'pcp'                => '100% Cubierto tras deducible B',
                'specialist'         => '100% Cubierto',
                'year'               => '2026',
            ],
            [
                'sku'                => 'MOO-LIFE-250K',
                'name'               => 'Mutual of Omaha - Term Life Answers ( / 20 Años)',
                'description'        => 'Seguro de vida a término por 20 años con suma asegurada de ,000 y cláusula de beneficios en vida por enfermedad terminal.',
                'price'              => 38.50,
                'carrier_name'       => 'Mutual of Omaha',
                'line'               => 'Vida Término / Term Life',
                'tier'               => 'No Aplica / N/A',
                'network'            => 'No Aplica / N/A',
                'deductible'         => 0.00,
                'moop'               => 0.00,
                'pcp'                => 'No Aplica',
                'specialist'         => 'No Aplica',
                'year'               => '2026',
            ],
            [
                'sku'                => 'AME-FE-15K',
                'name'               => 'Americo - Eagle Premier Series (Gastos Finales ,000)',
                'description'        => 'Seguro de Gastos Finales de emisión simplificada sin examen médico para adultos mayores (50-85 años).',
                'price'              => 55.00,
                'carrier_name'       => 'Americo Financial Life',
                'line'               => 'Gastos Finales / Final Expense',
                'tier'               => 'No Aplica / N/A',
                'network'            => 'No Aplica / N/A',
                'deductible'         => 0.00,
                'moop'               => 0.00,
                'pcp'                => 'No Aplica',
                'specialist'         => 'No Aplica',
                'year'               => '2026',
            ],
            [
                'sku'                => 'DD-DENT-PREM',
                'name'               => 'Delta Dental - Premium Individual & Family',
                'description'        => 'Plan dental preferente con 100% de cobertura en limpiezas preventivas y 80% en tratamientos básicos con red nacional Delta.',
                'price'              => 45.00,
                'carrier_name'       => 'Delta Dental',
                'line'               => 'Dental y Visión',
                'tier'               => 'No Aplica / N/A',
                'network'            => 'PPO (Preferred Provider Org)',
                'deductible'         => 50.00,
                'moop'               => 2000.00,
                'pcp'                => ' Limpiezas / Preventivo',
                'specialist'         => '20% Básico / 50% Mayor',
                'year'               => '2026',
            ],
            [
                'sku'                => 'NAT-HOSP-IND',
                'name'               => 'National General - Foundation Health (Hospital Indemnity)',
                'description'        => 'Póliza suplementaria de indemnización hospitalaria. Paga directamente al asegurado  por día de internación médica o quirúrgica.',
                'price'              => 49.00,
                'carrier_name'       => 'National General (Allstate Health Solutions)',
                'line'               => 'Indemnización Hospitalaria',
                'tier'               => 'No Aplica / N/A',
                'network'            => 'No Aplica / N/A',
                'deductible'         => 0.00,
                'moop'               => 0.00,
                'pcp'                => 'Pago directo al asegurado /día',
                'specialist'         => 'No Aplica',
                'year'               => '2026',
            ],
        ];

        foreach ( as ) {
             = DB::table('products')->where('sku', ['sku'])->first();

             = [
                'sku'         => ['sku'],
                'name'        => ['name'],
                'description' => ['description'],
                'quantity'    => 9999,
                'price'       => ['price'],
                'updated_at'  => ,
            ];

            if () {
                DB::table('products')->where('id', ->id)->update();
                 = ->id;
            } else {
                 = DB::table('products')->insertGetId(array_merge(, [
                    'created_at' => ,
                ]));
            }

            // Save custom attributes
            // 1. carrier_id
             = [['carrier_name']] ?? null;
            if ( && isset(['carrier_id'])) {
                ->saveAttributeValue(['carrier_id'], , 'products', 'integer_value', );
            }

            // 2. insurance_line
            if (isset(['insurance_line']) && isset(['insurance_line'][['line']])) {
                ->saveAttributeValue(['insurance_line'], , 'products', 'integer_value', ['insurance_line'][['line']]);
            }

            // 3. metal_tier
            if (isset(['metal_tier']) && isset(['metal_tier'][['tier']])) {
                ->saveAttributeValue(['metal_tier'], , 'products', 'integer_value', ['metal_tier'][['tier']]);
            }

            // 4. network_type
            if (isset(['network_type']) && isset(['network_type'][['network']])) {
                ->saveAttributeValue(['network_type'], , 'products', 'integer_value', ['network_type'][['network']]);
            }

            // 5. deductible
            if (isset(['deductible'])) {
                ->saveAttributeValue(['deductible'], , 'products', 'float_value', ['deductible']);
            }

            // 6. max_out_of_pocket
            if (isset(['max_out_of_pocket'])) {
                ->saveAttributeValue(['max_out_of_pocket'], , 'products', 'float_value', ['moop']);
            }

            // 7. primary_care_copay
            if (isset(['primary_care_copay'])) {
                ->saveAttributeValue(['primary_care_copay'], , 'products', 'text_value', ['pcp']);
            }

            // 8. specialist_copay
            if (isset(['specialist_copay'])) {
                ->saveAttributeValue(['specialist_copay'], , 'products', 'text_value', ['specialist']);
            }

            // 9. plan_year
            if (isset(['plan_year'])) {
                ->saveAttributeValue(['plan_year'], , 'products', 'text_value', ['year']);
            }
        }
    }

    /**
     * Helper to save or update attribute value.
     */
    private function saveAttributeValue(int , int , string , string , ): void
    {
         = DB::table('attribute_values')
            ->where('attribute_id', )
            ->where('entity_id', )
            ->where('entity_type', )
            ->first();

        if () {
            DB::table('attribute_values')
                ->where('id', ->id)
                ->update([ => ]);
        } else {
            DB::table('attribute_values')->insert([
                'attribute_id' => ,
                'entity_id'    => ,
                'entity_type'  => ,
                        => ,
            ]);
        }
    }
}

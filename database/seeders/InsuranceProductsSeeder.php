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
        $now = Carbon::now();

        // 1. Define custom attributes for products (Planes / Paquetes de Pólizas)
        $attributes = [
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

        $createdAttributes = [];
        $attributeOptionsMap = [];

        foreach ($attributes as $attrData) {
            $options = $attrData['options'];
            unset($attrData['options']);

            $existing = DB::table('attributes')
                ->where('code', $attrData['code'])
                ->where('entity_type', $attrData['entity_type'])
                ->first();

            if ($existing) {
                DB::table('attributes')
                    ->where('id', $existing->id)
                    ->update(array_merge($attrData, ['updated_at' => $now]));
                $attributeId = $existing->id;
            } else {
                $attributeId = DB::table('attributes')->insertGetId(
                    array_merge($attrData, ['created_at' => $now, 'updated_at' => $now])
                );
            }

            $createdAttributes[$attrData['code']] = $attributeId;

            if (! empty($options) && in_array($attrData['type'], ['select', 'multiselect'])) {
                $sort = 1;
                foreach ($options as $optName) {
                    $optRow = DB::table('attribute_options')
                        ->where('attribute_id', $attributeId)
                        ->where('name', $optName)
                        ->first();

                    if (! $optRow) {
                        $optId = DB::table('attribute_options')->insertGetId([
                            'attribute_id' => $attributeId,
                            'name'         => $optName,
                            'sort_order'   => $sort++,
                        ]);
                    } else {
                        $optId = $optRow->id;
                    }

                    $attributeOptionsMap[$attrData['code']][$optName] = $optId;
                }
            }
        }

        // Helper map of carriers
        $carriers = DB::table('organizations')->pluck('id', 'name');

        // 2. Real-World Insurance Plans / Policy Packages
        $plans = [
            [
                'sku'          => 'FB-SLV-1410',
                'name'         => 'Florida Blue - BlueOptions Silver 1410',
                'description'  => 'Plan ACA Plata con Reducción de Costos Compartidos (CSR). Excelente cobertura médica con copagos bajos y deducible $0.',
                'price'        => 450.00,
                'carrier_name' => 'Florida Blue (BCBS Florida)',
                'line'         => 'ACA / Obamacare (Salud)',
                'tier'         => 'Plata / Silver (CSR)',
                'network'      => 'PPO (Preferred Provider Org)',
                'deductible'   => 0.00,
                'moop'         => 3000.00,
                'pcp'          => '$0 Copago',
                'specialist'   => '$30 Copago',
                'year'         => '2026',
            ],
            [
                'sku'          => 'FB-BRZ-1422',
                'name'         => 'Florida Blue - BlueCare Bronze 1422',
                'description'  => 'Plan ACA Bronce económico diseñado para protección ante gastos médicos mayores con prima mensual reducida.',
                'price'        => 380.00,
                'carrier_name' => 'Florida Blue (BCBS Florida)',
                'line'         => 'ACA / Obamacare (Salud)',
                'tier'         => 'Bronce / Bronze',
                'network'      => 'HMO (Health Maintenance Org)',
                'deductible'   => 7500.00,
                'moop'         => 9100.00,
                'pcp'          => '$40 Copago',
                'specialist'   => '$80 Copago',
                'year'         => '2026',
            ],
            [
                'sku'          => 'AMB-SLV-STD',
                'name'         => 'Ambetter - Clear Silver (Standard CSR)',
                'description'  => 'Plan ACA Plata de Ambetter con cobertura integral de medicamentos recetados y red de clínicas preferidas.',
                'price'        => 420.00,
                'carrier_name' => 'Ambetter (Centene Corporation)',
                'line'         => 'ACA / Obamacare (Salud)',
                'tier'         => 'Plata / Silver (CSR)',
                'network'      => 'EPO (Exclusive Provider Org)',
                'deductible'   => 500.00,
                'moop'         => 2900.00,
                'pcp'          => '$5 Copago',
                'specialist'   => '$25 Copago',
                'year'         => '2026',
            ],
            [
                'sku'          => 'OSC-SLV-NXT',
                'name'         => 'Oscar Health - Classic Silver Next',
                'description'  => 'Plan tecnológico con telemedicina 24/7 sin costo, red de especialistas amplia y gestión fácil desde la app.',
                'price'        => 435.00,
                'carrier_name' => 'Oscar Health',
                'line'         => 'ACA / Obamacare (Salud)',
                'tier'         => 'Plata / Silver (CSR)',
                'network'      => 'EPO (Exclusive Provider Org)',
                'deductible'   => 0.00,
                'moop'         => 2500.00,
                'pcp'          => '$0 Virtual / $15 PCP',
                'specialist'   => '$40 Copago',
                'year'         => '2026',
            ],
            [
                'sku'          => 'HUM-MA-HMO1',
                'name'         => 'Humana - Gold Plus HMO (H1036-001)',
                'description'  => 'Medicare Advantage HMO con prima mensual de $0, beneficios dentales completos, lentes, audífonos y tarjeta de comida/OTC.',
                'price'        => 0.00,
                'carrier_name' => 'Humana',
                'line'         => 'Medicare Advantage (Part C)',
                'tier'         => 'No Aplica / N/A',
                'network'      => 'HMO (Health Maintenance Org)',
                'deductible'   => 0.00,
                'moop'         => 3400.00,
                'pcp'          => '$0 Copago',
                'specialist'   => '$20 Copago',
                'year'         => '2026',
            ],
            [
                'sku'          => 'UHC-MA-PPO1',
                'name'         => 'UnitedHealthcare - AARP Medicare Advantage Choice (PPO)',
                'description'  => 'Medicare Advantage con red PPO flexible que permite ver doctores fuera de red sin referidos y crédito OTC mensual.',
                'price'        => 0.00,
                'carrier_name' => 'UnitedHealthcare (UHC / Golden Rule)',
                'line'         => 'Medicare Advantage (Part C)',
                'tier'         => 'No Aplica / N/A',
                'network'      => 'PPO (Preferred Provider Org)',
                'deductible'   => 0.00,
                'moop'         => 3900.00,
                'pcp'          => '$0 Copago',
                'specialist'   => '$35 Copago',
                'year'         => '2026',
            ],
            [
                'sku'          => 'MOO-MED-PLANG',
                'name'         => 'Mutual of Omaha - Medicare Supplement Plan G',
                'description'  => 'Póliza Medigap Plan G. Cubre el 100% de los gastos hospitalarios y médicos no cubiertos por Medicare Original (solo paga deducible Parte B).',
                'price'        => 165.00,
                'carrier_name' => 'Mutual of Omaha',
                'line'         => 'Medicare Supplement (Medigap)',
                'tier'         => 'No Aplica / N/A',
                'network'      => 'Indemnización / FFS',
                'deductible'   => 240.00,
                'moop'         => 0.00,
                'pcp'          => '100% Cubierto tras deducible B',
                'specialist'   => '100% Cubierto',
                'year'         => '2026',
            ],
            [
                'sku'          => 'MOO-LIFE-250K',
                'name'         => 'Mutual of Omaha - Term Life Answers ($250k / 20 Años)',
                'description'  => 'Seguro de vida a término por 20 años con suma asegurada de $250,000 y cláusula de beneficios en vida por enfermedad terminal.',
                'price'        => 38.50,
                'carrier_name' => 'Mutual of Omaha',
                'line'         => 'Vida Término / Term Life',
                'tier'         => 'No Aplica / N/A',
                'network'      => 'No Aplica / N/A',
                'deductible'   => 0.00,
                'moop'         => 0.00,
                'pcp'          => 'No Aplica',
                'specialist'   => 'No Aplica',
                'year'         => '2026',
            ],
            [
                'sku'          => 'AME-FE-15K',
                'name'         => 'Americo - Eagle Premier Series (Gastos Finales $15,000)',
                'description'  => 'Seguro de Gastos Finales de emisión simplificada sin examen médico para adultos mayores (50-85 años).',
                'price'        => 55.00,
                'carrier_name' => 'Americo Financial Life',
                'line'         => 'Gastos Finales / Final Expense',
                'tier'         => 'No Aplica / N/A',
                'network'      => 'No Aplica / N/A',
                'deductible'   => 0.00,
                'moop'         => 0.00,
                'pcp'          => 'No Aplica',
                'specialist'   => 'No Aplica',
                'year'         => '2026',
            ],
            [
                'sku'          => 'DD-DENT-PREM',
                'name'         => 'Delta Dental - Premium Individual & Family',
                'description'  => 'Plan dental preferente con 100% de cobertura en limpiezas preventivas y 80% en tratamientos básicos con red nacional Delta.',
                'price'        => 45.00,
                'carrier_name' => 'Delta Dental',
                'line'         => 'Dental y Visión',
                'tier'         => 'No Aplica / N/A',
                'network'      => 'PPO (Preferred Provider Org)',
                'deductible'   => 50.00,
                'moop'         => 2000.00,
                'pcp'          => '$0 Limpiezas / Preventivo',
                'specialist'   => '20% Básico / 50% Mayor',
                'year'         => '2026',
            ],
            [
                'sku'          => 'NAT-HOSP-IND',
                'name'         => 'National General - Foundation Health (Hospital Indemnity)',
                'description'  => 'Póliza suplementaria de indemnización hospitalaria. Paga directamente al asegurado $500 por día de internación médica o quirúrgica.',
                'price'        => 49.00,
                'carrier_name' => 'National General (Allstate Health Solutions)',
                'line'         => 'Indemnización Hospitalaria',
                'tier'         => 'No Aplica / N/A',
                'network'      => 'No Aplica / N/A',
                'deductible'   => 0.00,
                'moop'         => 0.00,
                'pcp'          => 'Pago directo al asegurado $500/día',
                'specialist'   => 'No Aplica',
                'year'         => '2026',
            ],
        ];

        foreach ($plans as $p) {
            $existing = DB::table('products')->where('sku', $p['sku'])->first();

            $productData = [
                'sku'         => $p['sku'],
                'name'        => $p['name'],
                'description' => $p['description'],
                'quantity'    => 9999,
                'price'       => $p['price'],
                'updated_at'  => $now,
            ];

            if ($existing) {
                DB::table('products')->where('id', $existing->id)->update($productData);
                $productId = $existing->id;
            } else {
                $productId = DB::table('products')->insertGetId(array_merge($productData, [
                    'created_at' => $now,
                ]));
            }

            // Save custom attributes
            $carrierId = $carriers[$p['carrier_name']] ?? null;
            if ($carrierId && isset($createdAttributes['carrier_id'])) {
                $this->saveAttributeValue($createdAttributes['carrier_id'], $productId, 'products', 'integer_value', $carrierId);
            }

            if (isset($createdAttributes['insurance_line']) && isset($attributeOptionsMap['insurance_line'][$p['line']])) {
                $this->saveAttributeValue($createdAttributes['insurance_line'], $productId, 'products', 'integer_value', $attributeOptionsMap['insurance_line'][$p['line']]);
            }

            if (isset($createdAttributes['metal_tier']) && isset($attributeOptionsMap['metal_tier'][$p['tier']])) {
                $this->saveAttributeValue($createdAttributes['metal_tier'], $productId, 'products', 'integer_value', $attributeOptionsMap['metal_tier'][$p['tier']]);
            }

            if (isset($createdAttributes['network_type']) && isset($attributeOptionsMap['network_type'][$p['network']])) {
                $this->saveAttributeValue($createdAttributes['network_type'], $productId, 'products', 'integer_value', $attributeOptionsMap['network_type'][$p['network']]);
            }

            if (isset($createdAttributes['deductible'])) {
                $this->saveAttributeValue($createdAttributes['deductible'], $productId, 'products', 'float_value', $p['deductible']);
            }

            if (isset($createdAttributes['max_out_of_pocket'])) {
                $this->saveAttributeValue($createdAttributes['max_out_of_pocket'], $productId, 'products', 'float_value', $p['moop']);
            }

            if (isset($createdAttributes['primary_care_copay'])) {
                $this->saveAttributeValue($createdAttributes['primary_care_copay'], $productId, 'products', 'text_value', $p['pcp']);
            }

            if (isset($createdAttributes['specialist_copay'])) {
                $this->saveAttributeValue($createdAttributes['specialist_copay'], $productId, 'products', 'text_value', $p['specialist']);
            }

            if (isset($createdAttributes['plan_year'])) {
                $this->saveAttributeValue($createdAttributes['plan_year'], $productId, 'products', 'text_value', $p['year']);
            }
        }
    }

    /**
     * Helper to save or update attribute value.
     */
    private function saveAttributeValue(int $attributeId, int $entityId, string $entityType, string $column, $value): void
    {
        $existing = DB::table('attribute_values')
            ->where('attribute_id', $attributeId)
            ->where('entity_id', $entityId)
            ->where('entity_type', $entityType)
            ->first();

        if ($existing) {
            DB::table('attribute_values')
                ->where('id', $existing->id)
                ->update([$column => $value]);
        } else {
            DB::table('attribute_values')->insert([
                'attribute_id' => $attributeId,
                'entity_id'    => $entityId,
                'entity_type'  => $entityType,
                $column        => $value,
            ]);
        }
    }
}
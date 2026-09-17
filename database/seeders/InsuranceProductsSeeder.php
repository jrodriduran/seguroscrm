<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         = now();

         = [
            [
                'code' => 'carrier_id',
                'name' => 'Insurance Carrier',
                'type' => 'lookup',
                'entity_type' => 'products',
                'lookup_type' => 'organizations',
                'validation' => null,
                'sort_order' => 1,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'insurance_line',
                'name' => 'Line of Insurance',
                'type' => 'select',
                'entity_type' => 'products',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 2,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [
                    'ACA / Obamacare',
                    'Medicare Advantage',
                    'Medicare Supplement (Medigap)',
                    'Term Life',
                    'Indexed Universal Life (IUL)',
                    'Final Expense',
                    'Dental & Vision',
                    'Hospital Indemnity',
                ],
            ],
            [
                'code' => 'metal_tier',
                'name' => 'Metal Tier',
                'type' => 'select',
                'entity_type' => 'products',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 3,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [
                    'Bronze',
                    'Silver (CSR)',
                    'Gold',
                    'Platinum',
                    'Catastrophic',
                    'N/A',
                ],
            ],
            [
                'code' => 'network_type',
                'name' => 'Provider Network',
                'type' => 'select',
                'entity_type' => 'products',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 4,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [
                    'HMO (Health Maintenance Org)',
                    'EPO (Exclusive Provider Org)',
                    'PPO (Preferred Provider Org)',
                    'Fee For Service (FFS)',
                    'N/A',
                ],
            ],
            [
                'code' => 'deductible',
                'name' => 'Individual Deductible',
                'type' => 'price',
                'entity_type' => 'products',
                'lookup_type' => null,
                'validation' => 'decimal',
                'sort_order' => 5,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'max_out_of_pocket',
                'name' => 'Maximum Out-of-Pocket',
                'type' => 'price',
                'entity_type' => 'products',
                'lookup_type' => null,
                'validation' => 'decimal',
                'sort_order' => 6,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'primary_care_copay',
                'name' => 'Primary Care Copay (PCP)',
                'type' => 'text',
                'entity_type' => 'products',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 7,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'specialist_copay',
                'name' => 'Specialist Copay',
                'type' => 'text',
                'entity_type' => 'products',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 8,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'plan_year',
                'name' => 'Plan Year',
                'type' => 'text',
                'entity_type' => 'products',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 9,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [],
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

            if (! empty()) {
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

         = DB::table('organizations')->pluck('id', 'name')->toArray();

         = [
            [
                'sku'          => 'FLB-BC-SILV1455',
                'name'         => 'Florida Blue - BlueCare Silver 1455',
                'description'  => 'Qualified ACA Silver Plan with CSR eligible for cost reduction. Zero primary care copay.',
                'price'        => 0.00,
                'carrier_name' => 'Florida Blue',
                'line'         => 'ACA / Obamacare',
                'tier'         => 'Silver (CSR)',
                'network'      => 'HMO (Health Maintenance Org)',
                'deductible'   => 0.00,
                'moop'         => 1500.00,
                'pcp'          => ' Copay',
                'specialist'   => ' Copay',
                'year'         => '2026',
            ],
            [
                'sku'          => 'AMB-BC-SILV94',
                'name'         => 'Ambetter - Balanced Care 11 (Silver 94)',
                'description'  => 'High subsidy ACA silver plan with  deductible and low copays for medical consultations and generic drugs.',
                'price'        => 0.00,
                'carrier_name' => 'Ambetter (Sunshine Health)',
                'line'         => 'ACA / Obamacare',
                'tier'         => 'Silver (CSR)',
                'network'      => 'EPO (Exclusive Provider Org)',
                'deductible'   => 0.00,
                'moop'         => 1200.00,
                'pcp'          => ' Copay',
                'specialist'   => ' Copay',
                'year'         => '2026',
            ],
            [
                'sku'          => 'OSC-CL-BRONZE',
                'name'         => 'Oscar - Classic Bronze Next',
                'description'  => 'Low premium bronze plan with integrated telemedicine at  copay.',
                'price'        => 15.00,
                'carrier_name' => 'Oscar Health',
                'line'         => 'ACA / Obamacare',
                'tier'         => 'Bronze',
                'network'      => 'EPO (Exclusive Provider Org)',
                'deductible'   => 7500.00,
                'moop'         => 9100.00,
                'pcp'          => ' Copay',
                'specialist'   => ' Copay',
                'year'         => '2026',
            ],
            [
                'sku'          => 'UHC-AARP-PPO',
                'name'         => 'UnitedHealthcare - AARP Medicare Advantage Choice (PPO)',
                'description'  => 'Medicare Advantage PPO plan with comprehensive dental, vision, hearing allowance and gym membership.',
                'price'        => 0.00,
                'carrier_name' => 'UnitedHealthcare',
                'line'         => 'Medicare Advantage',
                'tier'         => 'N/A',
                'network'      => 'PPO (Preferred Provider Org)',
                'deductible'   => 0.00,
                'moop'         => 3400.00,
                'pcp'          => ' Copay',
                'specialist'   => ' Copay',
                'year'         => '2026',
            ],
            [
                'sku'          => 'HUM-GOLD-HMO',
                'name'         => 'Humana - Gold Plus HMO (Medicare Advantage)',
                'description'  => 'Medicare Advantage HMO plan with  monthly premium, OTC allowance and comprehensive wellness benefits.',
                'price'        => 0.00,
                'carrier_name' => 'Humana',
                'line'         => 'Medicare Advantage',
                'tier'         => 'N/A',
                'network'      => 'HMO (Health Maintenance Org)',
                'deductible'   => 0.00,
                'moop'         => 2900.00,
                'pcp'          => ' Copay',
                'specialist'   => ' Copay',
                'year'         => '2026',
            ],
            [
                'sku'          => 'MOO-MED-PLANG',
                'name'         => 'Mutual of Omaha - Medicare Supplement Plan G',
                'description'  => 'Medigap Plan G covering 100% of out-of-pocket costs for Part A and Part B coinsurance after Part B deductible.',
                'price'        => 165.00,
                'carrier_name' => 'Mutual of Omaha',
                'line'         => 'Medicare Supplement (Medigap)',
                'tier'         => 'N/A',
                'network'      => 'Fee For Service (FFS)',
                'deductible'   => 240.00,
                'moop'         => 0.00,
                'pcp'          => '100% Covered after Part B deductible',
                'specialist'   => '100% Covered',
                'year'         => '2026',
            ],
            [
                'sku'          => 'MOO-LIFE-250K',
                'name'         => 'Mutual of Omaha - Term Life Answers ( / 20 Years)',
                'description'  => '20-year term life insurance with ,000 death benefit and accelerated death benefit living riders.',
                'price'        => 38.50,
                'carrier_name' => 'Mutual of Omaha',
                'line'         => 'Term Life',
                'tier'         => 'N/A',
                'network'      => 'N/A',
                'deductible'   => 0.00,
                'moop'         => 0.00,
                'pcp'          => 'N/A',
                'specialist'   => 'N/A',
                'year'         => '2026',
            ],
            [
                'sku'          => 'AME-FE-15K',
                'name'         => 'Americo - Eagle Premier Series (Final Expense ,000)',
                'description'  => 'Simplified issue whole life insurance without medical exam designed for seniors.',
                'price'        => 55.00,
                'carrier_name' => 'Americo Financial Life',
                'line'         => 'Final Expense',
                'tier'         => 'N/A',
                'network'      => 'N/A',
                'deductible'   => 0.00,
                'moop'         => 0.00,
                'pcp'          => 'N/A',
                'specialist'   => 'N/A',
                'year'         => '2026',
            ],
            [
                'sku'          => 'DD-DENT-PREM',
                'name'         => 'Delta Dental - Premium Individual & Family',
                'description'  => 'Individual and family dental plan with 100% preventive coverage and extensive nationwide Delta network.',
                'price'        => 45.00,
                'carrier_name' => 'Delta Dental',
                'line'         => 'Dental & Vision',
                'tier'         => 'N/A',
                'network'      => 'PPO (Preferred Provider Org)',
                'deductible'   => 50.00,
                'moop'         => 2000.00,
                'pcp'          => ' Cleanings / Preventive',
                'specialist'   => '20% Basic / 50% Major',
                'year'         => '2026',
            ],
            [
                'sku'          => 'NAT-HOSP-IND',
                'name'         => 'National General - Foundation Health (Hospital Indemnity)',
                'description'  => 'Hospital indemnity policy paying direct daily cash benefits (/day) during medical or surgical hospitalizations.',
                'price'        => 49.00,
                'carrier_name' => 'National General (Allstate Health Solutions)',
                'line'         => 'Hospital Indemnity',
                'tier'         => 'N/A',
                'network'      => 'N/A',
                'deductible'   => 0.00,
                'moop'         => 0.00,
                'pcp'          => 'Direct cash payment to insured (/day)',
                'specialist'   => 'N/A',
                'year'         => '2026',
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
             = [['carrier_name']] ?? null;
            if ( && isset(['carrier_id'])) {
                ->saveAttributeValue(['carrier_id'], , 'products', 'integer_value', );
            }

            if (isset(['insurance_line']) && isset(['insurance_line'][['line']])) {
                ->saveAttributeValue(['insurance_line'], , 'products', 'integer_value', ['insurance_line'][['line']]);
            }

            if (isset(['metal_tier']) && isset(['metal_tier'][['tier']])) {
                ->saveAttributeValue(['metal_tier'], , 'products', 'integer_value', ['metal_tier'][['tier']]);
            }

            if (isset(['network_type']) && isset(['network_type'][['network']])) {
                ->saveAttributeValue(['network_type'], , 'products', 'integer_value', ['network_type'][['network']]);
            }

            if (isset(['deductible'])) {
                ->saveAttributeValue(['deductible'], , 'products', 'float_value', ['deductible']);
            }

            if (isset(['max_out_of_pocket'])) {
                ->saveAttributeValue(['max_out_of_pocket'], , 'products', 'float_value', ['moop']);
            }

            if (isset(['primary_care_copay'])) {
                ->saveAttributeValue(['primary_care_copay'], , 'products', 'text_value', ['pcp']);
            }

            if (isset(['specialist_copay'])) {
                ->saveAttributeValue(['specialist_copay'], , 'products', 'text_value', ['specialist']);
            }

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

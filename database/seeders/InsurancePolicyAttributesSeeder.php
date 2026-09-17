<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsurancePolicyAttributesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $attributes = [
            [
                'code'            => 'policy_number',
                'name'            => 'Policy Number',
                'type'            => 'text',
                'entity_type'     => 'leads',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 30,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 0,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'effective_date',
                'name'            => 'Effective Date',
                'type'            => 'date',
                'entity_type'     => 'leads',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 31,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 0,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'renewal_date',
                'name'            => 'Renewal Date',
                'type'            => 'date',
                'entity_type'     => 'leads',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 32,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 0,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'issued_premium',
                'name'            => 'Issued Monthly Premium',
                'type'            => 'price',
                'entity_type'     => 'leads',
                'lookup_type'     => null,
                'validation'      => 'decimal',
                'sort_order'      => 33,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 0,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'payment_method',
                'name'            => 'Payment Method',
                'type'            => 'select',
                'entity_type'     => 'leads',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 34,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 0,
                'is_user_defined' => 1,
                'options'         => [
                    'EFT (Bank Auto-Debit)',
                    'Credit / Debit Card',
                    '100% Federal Subsidy ($0 Premium)',
                    'Direct Client Pay (Monthly Invoice)',
                ],
            ],
            [
                'code'            => 'marketplace_app_id',
                'name'            => 'Marketplace App ID / NPN',
                'type'            => 'text',
                'entity_type'     => 'leads',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 35,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 0,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'policy_status',
                'name'            => 'Policy Status',
                'type'            => 'select',
                'entity_type'     => 'leads',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 36,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 0,
                'is_user_defined' => 1,
                'options'         => [
                    'Active / Bound',
                    'Pending First Payment',
                    'Under Review',
                    'Lapsed / Cancelled',
                    'Renewed',
                ],
            ],
        ];

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
                    array_merge($attrData, [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                );
            }

            if (! empty($options)) {
                $sort = 1;
                foreach ($options as $optName) {
                    $opt = DB::table('attribute_options')
                        ->where('attribute_id', $attributeId)
                        ->where('name', $optName)
                        ->first();

                    if (! $opt) {
                        DB::table('attribute_options')->insert([
                            'attribute_id' => $attributeId,
                            'name'         => $optName,
                            'sort_order'   => $sort++,
                        ]);
                    }
                }
            }
        }
    }
}
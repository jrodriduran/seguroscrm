<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceAttributesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $attributes = [
            /**
             * Persons Attributes
             */
            [
                'code' => 'dob',
                'name' => 'Date of Birth',
                'type' => 'date',
                'entity_type' => 'persons',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 10,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'gender',
                'name' => 'Gender',
                'type' => 'select',
                'entity_type' => 'persons',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 11,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [
                    'Male',
                    'Female',
                    'Other',
                ],
            ],
            [
                'code' => 'marital_status',
                'name' => 'Marital Status',
                'type' => 'select',
                'entity_type' => 'persons',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 12,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [
                    'Single',
                    'Married',
                    'Divorced',
                    'Widowed',
                ],
            ],
            [
                'code' => 'ssn_itin',
                'name' => 'SSN / ITIN / ID',
                'type' => 'text',
                'entity_type' => 'persons',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 13,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'immigration_status',
                'name' => 'Immigration Status',
                'type' => 'select',
                'entity_type' => 'persons',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 14,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [
                    'US Citizen',
                    'Permanent Resident (Green Card)',
                    'Work Authorization (EAD)',
                    'Valid Visa',
                    'Humanitarian Parole / TPS / Asylum',
                    'Other Status',
                ],
            ],
            [
                'code' => 'preferred_language',
                'name' => 'Preferred Language',
                'type' => 'select',
                'entity_type' => 'persons',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 15,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [
                    'English',
                    'Spanish',
                    'Portuguese',
                ],
            ],
            [
                'code' => 'tobacco_user',
                'name' => 'Tobacco / Smoker',
                'type' => 'boolean',
                'entity_type' => 'persons',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 16,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [],
            ],

            /**
             * Leads Attributes
             */
            [
                'code' => 'zip_code',
                'name' => 'ZIP Code',
                'type' => 'text',
                'entity_type' => 'leads',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 20,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'county_state',
                'name' => 'County / State',
                'type' => 'text',
                'entity_type' => 'leads',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 21,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'household_size',
                'name' => 'Household Size',
                'type' => 'text',
                'entity_type' => 'leads',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 22,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'annual_income',
                'name' => 'Estimated Annual Income',
                'type' => 'price',
                'entity_type' => 'leads',
                'lookup_type' => null,
                'validation' => 'decimal',
                'sort_order' => 23,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'current_carrier',
                'name' => 'Current Carrier',
                'type' => 'text',
                'entity_type' => 'leads',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 24,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'mbi_number',
                'name' => 'Medicare Beneficiary ID (MBI)',
                'type' => 'text',
                'entity_type' => 'leads',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 25,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'medicare_part_a_date',
                'name' => 'Medicare Part A Effective Date',
                'type' => 'date',
                'entity_type' => 'leads',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 26,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'medicare_part_b_date',
                'name' => 'Medicare Part B Effective Date',
                'type' => 'date',
                'entity_type' => 'leads',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 27,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [],
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
                    array_merge($attrData, ['created_at' => $now, 'updated_at' => $now])
                );
            }

            if (! empty($options) && $attrData['type'] === 'select') {
                $sort = 1;
                foreach ($options as $optName) {
                    $optExists = DB::table('attribute_options')
                        ->where('attribute_id', $attributeId)
                        ->where('name', $optName)
                        ->exists();

                    if (! $optExists) {
                        DB::table('attribute_options')->insert([
                            'attribute_id' => $attributeId,
                            'name' => $optName,
                            'sort_order' => $sort++,
                        ]);
                    }
                }
            }
        }
    }
}

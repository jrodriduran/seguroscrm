<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceCarriersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $adminUser = DB::table('users')->first();
        $adminUserId = $adminUser ? $adminUser->id : null;

        // 1. Define custom attributes for organizations (Aseguradoras / Carriers)
        $attributes = [
            [
                'code'            => 'carrier_code',
                'name'            => 'Código NAIC / Carrier Code',
                'type'            => 'text',
                'entity_type'     => 'organizations',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 4,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'carrier_type',
                'name'            => 'Tipo de Aseguradora / Carrier Type',
                'type'            => 'select',
                'entity_type'     => 'organizations',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 5,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [
                    'Aseguradora Médica / Health Carrier',
                    'Aseguradora de Vida / Life Carrier',
                    'Aseguradora Integral (Salud y Vida) / Health & Life',
                    'Agencia General / FMO / MGA',
                    'Agencia Aliada / Partner Agency',
                ],
            ],
            [
                'code'            => 'lines_of_business',
                'name'            => 'Ramos / Líneas de Negocio',
                'type'            => 'multiselect',
                'entity_type'     => 'organizations',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 6,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [
                    'ACA / Obamacare',
                    'Medicare Advantage (Part C)',
                    'Medicare Supplement (Medigap)',
                    'Medicare Rx (Part D)',
                    'Vida IUL / Universal Life',
                    'Vida Término / Term Life',
                    'Gastos Finales / Final Expense',
                    'Dental y Visión / Dental & Vision',
                    'Accidentes y Enfermedades Críticas / Critical Illness',
                    'Indemnización Hospitalaria / Hospital Indemnity',
                ],
            ],
            [
                'code'            => 'broker_portal_url',
                'name'            => 'Portal de Agentes / Broker Portal URL',
                'type'            => 'text',
                'entity_type'     => 'organizations',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 7,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'agent_support_phone',
                'name'            => 'Teléfono Soporte Brokers / Agent Phone',
                'type'            => 'text',
                'entity_type'     => 'organizations',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 8,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'agent_support_email',
                'name'            => 'Email Soporte Brokers / Agent Email',
                'type'            => 'text',
                'entity_type'     => 'organizations',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 9,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [],
            ],
            [
                'code'            => 'carrier_status',
                'name'            => 'Estado de Contratación / Contracting Status',
                'type'            => 'select',
                'entity_type'     => 'organizations',
                'lookup_type'     => null,
                'validation'      => null,
                'sort_order'      => 10,
                'is_required'     => 0,
                'is_unique'       => 0,
                'quick_add'       => 1,
                'is_user_defined' => 1,
                'options'         => [
                    'Activo / Contratado (Active / Contracted)',
                    'En Proceso de Contratación (Pending Contracting)',
                    'Inactivo (Inactive)',
                    'No Contratado / Solo Referencia (Reference Only)',
                ],
            ],
        ];

        // Also ensure carrier_id is available on leads for selecting a carrier
        $leadCarrierAttr = [
            'code'            => 'carrier_id',
            'name'            => 'Aseguradora / Carrier',
            'type'            => 'lookup',
            'entity_type'     => 'leads',
            'lookup_type'     => 'organizations',
            'validation'      => null,
            'sort_order'      => 23,
            'is_required'     => 0,
            'is_unique'       => 0,
            'quick_add'       => 1,
            'is_user_defined' => 1,
            'options'         => [],
        ];

        $allAttributes = array_merge($attributes, [$leadCarrierAttr]);

        $createdAttributes = [];
        $attributeOptionsMap = [];

        foreach ($allAttributes as $attrData) {
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

        // 2. Seed Real-World Carriers
        $carriers = [
            [
                'name'         => 'Florida Blue (BCBS Florida)',
                'city'         => 'Jacksonville',
                'state'        => 'FL',
                'carrier_code' => '76090',
                'carrier_type' => 'Aseguradora Médica / Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                    'Medicare Advantage (Part C)',
                    'Medicare Supplement (Medigap)',
                    'Dental y Visión / Dental & Vision',
                ],
                'portal_url'   => 'https://www.floridablue.com/agents',
                'phone'        => '1-800-267-3156',
                'email'        => 'agency.services@floridablue.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'Ambetter (Centene Corporation)',
                'city'         => 'St. Louis',
                'state'        => 'MO',
                'carrier_code' => '60054',
                'carrier_type' => 'Aseguradora Médica / Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                    'Dental y Visión / Dental & Vision',
                ],
                'portal_url'   => 'https://broker.ambetterhealth.com',
                'phone'        => '1-855-700-7985',
                'email'        => 'brokers@centene.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'UnitedHealthcare (UHC / Golden Rule)',
                'city'         => 'Minnetonka',
                'state'        => 'MN',
                'carrier_code' => '87726',
                'carrier_type' => 'Aseguradora Integral (Salud y Vida) / Health & Life',
                'lines'        => [
                    'ACA / Obamacare',
                    'Medicare Advantage (Part C)',
                    'Medicare Supplement (Medigap)',
                    'Medicare Rx (Part D)',
                    'Dental y Visión / Dental & Vision',
                ],
                'portal_url'   => 'https://www.uhcjarvis.com',
                'phone'        => '1-888-381-8581',
                'email'        => 'broker.support@uhc.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'Humana',
                'city'         => 'Louisville',
                'state'        => 'KY',
                'carrier_code' => '73288',
                'carrier_type' => 'Aseguradora Médica / Health Carrier',
                'lines'        => [
                    'Medicare Advantage (Part C)',
                    'Medicare Supplement (Medigap)',
                    'Medicare Rx (Part D)',
                    'Dental y Visión / Dental & Vision',
                ],
                'portal_url'   => 'https://www.humana.com/agent',
                'phone'        => '1-800-309-8166',
                'email'        => 'agentsupport@humana.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'Aetna (CVS Health)',
                'city'         => 'Hartford',
                'state'        => 'CT',
                'carrier_code' => '60054',
                'carrier_type' => 'Aseguradora Médica / Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                    'Medicare Advantage (Part C)',
                    'Medicare Supplement (Medigap)',
                    'Dental y Visión / Dental & Vision',
                ],
                'portal_url'   => 'https://www.aetna.com/producers',
                'phone'        => '1-866-272-6630',
                'email'        => 'brokerhelp@aetna.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'Oscar Health',
                'city'         => 'New York',
                'state'        => 'NY',
                'carrier_code' => '15399',
                'carrier_type' => 'Aseguradora Médica / Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                ],
                'portal_url'   => 'https://business.hioscar.com/brokers',
                'phone'        => '1-855-672-2788',
                'email'        => 'brokers@hioscar.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'Molina Healthcare',
                'city'         => 'Long Beach',
                'state'        => 'CA',
                'carrier_code' => '16144',
                'carrier_type' => 'Aseguradora Médica / Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                    'Medicare Advantage (Part C)',
                ],
                'portal_url'   => 'https://broker.molinahealthcare.com',
                'phone'        => '1-866-448-6136',
                'email'        => 'broker.services@molinahealthcare.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'Cigna Healthcare',
                'city'         => 'Bloomfield',
                'state'        => 'CT',
                'carrier_code' => '67369',
                'carrier_type' => 'Aseguradora Médica / Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                    'Medicare Advantage (Part C)',
                    'Medicare Supplement (Medigap)',
                    'Dental y Visión / Dental & Vision',
                ],
                'portal_url'   => 'https://producers.cigna.com',
                'phone'        => '1-877-244-6215',
                'email'        => 'brokersupport@cigna.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'Mutual of Omaha',
                'city'         => 'Omaha',
                'state'        => 'NE',
                'carrier_code' => '71412',
                'carrier_type' => 'Aseguradora de Vida / Life Carrier',
                'lines'        => [
                    'Medicare Supplement (Medigap)',
                    'Vida IUL / Universal Life',
                    'Vida Término / Term Life',
                    'Gastos Finales / Final Expense',
                ],
                'portal_url'   => 'https://www.mutualofomaha.com/broker',
                'phone'        => '1-800-867-6878',
                'email'        => 'sales.support@mutualofomaha.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'Americo Financial Life',
                'city'         => 'Kansas City',
                'state'        => 'MO',
                'carrier_code' => '61999',
                'carrier_type' => 'Aseguradora de Vida / Life Carrier',
                'lines'        => [
                    'Vida IUL / Universal Life',
                    'Gastos Finales / Final Expense',
                ],
                'portal_url'   => 'https://www.americo.com/agent-portal',
                'phone'        => '1-800-231-0801',
                'email'        => 'agent.services@americo.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'National General (Allstate Health Solutions)',
                'city'         => 'Winston-Salem',
                'state'        => 'NC',
                'carrier_code' => '23728',
                'carrier_type' => 'Aseguradora Médica / Health Carrier',
                'lines'        => [
                    'Indemnización Hospitalaria / Hospital Indemnity',
                    'Dental y Visión / Dental & Vision',
                    'Accidentes y Enfermedades Críticas / Critical Illness',
                ],
                'portal_url'   => 'https://natgenhealth.com',
                'phone'        => '1-888-781-0585',
                'email'        => 'service@natgenhealth.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
            [
                'name'         => 'Delta Dental',
                'city'         => 'San Francisco',
                'state'        => 'CA',
                'carrier_code' => '54941',
                'carrier_type' => 'Aseguradora Médica / Health Carrier',
                'lines'        => [
                    'Dental y Visión / Dental & Vision',
                ],
                'portal_url'   => 'https://www.deltadentalins.com/brokers',
                'phone'        => '1-800-521-2651',
                'email'        => 'brokersupport@deltadental.com',
                'status'       => 'Activo / Contratado (Active / Contracted)',
            ],
        ];

        foreach ($carriers as $c) {
            $org = DB::table('organizations')->where('name', $c['name'])->first();

            $addressData = [
                'address'  => '',
                'city'     => $c['city'],
                'state'    => $c['state'],
                'country'  => 'US',
                'postcode' => '',
            ];

            if ($org) {
                $orgId = $org->id;
                DB::table('organizations')->where('id', $orgId)->update([
                    'address'    => json_encode($addressData),
                    'updated_at' => $now,
                ]);
            } else {
                $orgId = DB::table('organizations')->insertGetId([
                    'name'       => $c['name'],
                    'address'    => json_encode($addressData),
                    'user_id'    => $adminUserId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Save attribute values for this organization
            if (isset($createdAttributes['carrier_code'])) {
                $this->saveAttributeValue($createdAttributes['carrier_code'], $orgId, 'organizations', 'text_value', $c['carrier_code']);
            }

            if (isset($createdAttributes['carrier_type']) && isset($attributeOptionsMap['carrier_type'][$c['carrier_type']])) {
                $this->saveAttributeValue($createdAttributes['carrier_type'], $orgId, 'organizations', 'integer_value', $attributeOptionsMap['carrier_type'][$c['carrier_type']]);
            }

            if (isset($createdAttributes['lines_of_business'])) {
                $lineIds = [];
                foreach ($c['lines'] as $lineName) {
                    if (isset($attributeOptionsMap['lines_of_business'][$lineName])) {
                        $lineIds[] = $attributeOptionsMap['lines_of_business'][$lineName];
                    }
                }
                if (! empty($lineIds)) {
                    $this->saveAttributeValue($createdAttributes['lines_of_business'], $orgId, 'organizations', 'text_value', implode(',', $lineIds));
                }
            }

            if (isset($createdAttributes['broker_portal_url'])) {
                $this->saveAttributeValue($createdAttributes['broker_portal_url'], $orgId, 'organizations', 'text_value', $c['portal_url']);
            }

            if (isset($createdAttributes['agent_support_phone'])) {
                $this->saveAttributeValue($createdAttributes['agent_support_phone'], $orgId, 'organizations', 'text_value', $c['phone']);
            }

            if (isset($createdAttributes['agent_support_email'])) {
                $this->saveAttributeValue($createdAttributes['agent_support_email'], $orgId, 'organizations', 'text_value', $c['email']);
            }

            if (isset($createdAttributes['carrier_status']) && isset($attributeOptionsMap['carrier_status'][$c['status']])) {
                $this->saveAttributeValue($createdAttributes['carrier_status'], $orgId, 'organizations', 'integer_value', $attributeOptionsMap['carrier_status'][$c['status']]);
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
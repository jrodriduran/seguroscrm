<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceCarriersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         = now();

         = DB::table('users')->first();
         =  ? ->id : 1;

         = [
            [
                'code' => 'carrier_code',
                'name' => 'Carrier / NAIC Code',
                'type' => 'text',
                'entity_type' => 'organizations',
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
                'code' => 'carrier_type',
                'name' => 'Entity Type',
                'type' => 'select',
                'entity_type' => 'organizations',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 11,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [
                    'Health Carrier',
                    'Life Carrier',
                    'Health & Life Carrier',
                    'General Agency / FMO / MGA',
                    'Partner Agency',
                ],
            ],
            [
                'code' => 'lines_of_business',
                'name' => 'Lines of Business',
                'type' => 'multiselect',
                'entity_type' => 'organizations',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 12,
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
                'code' => 'broker_portal_url',
                'name' => 'Broker Portal URL',
                'type' => 'text',
                'entity_type' => 'organizations',
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
                'code' => 'agent_support_phone',
                'name' => 'Broker Support Phone',
                'type' => 'text',
                'entity_type' => 'organizations',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 14,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'agent_support_email',
                'name' => 'Broker Support Email',
                'type' => 'text',
                'entity_type' => 'organizations',
                'lookup_type' => null,
                'validation' => 'email',
                'sort_order' => 15,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
                'options' => [],
            ],
            [
                'code' => 'carrier_status',
                'name' => 'Contracting Status',
                'type' => 'select',
                'entity_type' => 'organizations',
                'lookup_type' => null,
                'validation' => null,
                'sort_order' => 16,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 1,
                'is_user_defined' => 1,
                'options' => [
                    'Active / Contracted',
                    'Pending Contracting',
                    'Inactive',
                    'Reference Only',
                ],
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

         = [
            [
                'name'         => 'Florida Blue',
                'city'         => 'Jacksonville',
                'state'        => 'FL',
                'carrier_code' => '54127',
                'carrier_type' => 'Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                    'Medicare Advantage',
                ],
                'portal_url'   => 'https://www.floridablue.com/agents',
                'phone'        => '1-800-267-3156',
                'email'        => 'agencyops@floridablue.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'Ambetter (Sunshine Health)',
                'city'         => 'Sunrise',
                'state'        => 'FL',
                'carrier_code' => '13189',
                'carrier_type' => 'Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                ],
                'portal_url'   => 'https://broker.ambetterhealth.com',
                'phone'        => '1-855-700-7985',
                'email'        => 'brokerservices@centene.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'Oscar Health',
                'city'         => 'New York',
                'state'        => 'NY',
                'carrier_code' => '15822',
                'carrier_type' => 'Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                ],
                'portal_url'   => 'https://business.hioscar.com/brokers',
                'phone'        => '1-855-672-2713',
                'email'        => 'brokers@hioscar.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'UnitedHealthcare',
                'city'         => 'Minnetonka',
                'state'        => 'MN',
                'carrier_code' => '79413',
                'carrier_type' => 'Health & Life Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                    'Medicare Advantage',
                    'Medicare Supplement (Medigap)',
                    'Dental & Vision',
                ],
                'portal_url'   => 'https://www.jarvis-uhc.com',
                'phone'        => '1-888-381-8581',
                'email'        => 'phd@uhc.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'Humana',
                'city'         => 'Louisville',
                'state'        => 'KY',
                'carrier_code' => '73288',
                'carrier_type' => 'Health Carrier',
                'lines'        => [
                    'Medicare Advantage',
                    'Medicare Supplement (Medigap)',
                    'Dental & Vision',
                ],
                'portal_url'   => 'https://www.humana.com/agent',
                'phone'        => '1-800-309-8166',
                'email'        => 'agenthelp@humana.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'Aetna / CVS Health',
                'city'         => 'Hartford',
                'state'        => 'CT',
                'carrier_code' => '60054',
                'carrier_type' => 'Health & Life Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                    'Medicare Advantage',
                    'Medicare Supplement (Medigap)',
                    'Hospital Indemnity',
                ],
                'portal_url'   => 'https://www.aetna.com/producers',
                'phone'        => '1-866-272-6630',
                'email'        => 'brokersupport@aetna.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'Molina Healthcare',
                'city'         => 'Long Beach',
                'state'        => 'CA',
                'carrier_code' => '16144',
                'carrier_type' => 'Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                    'Medicare Advantage',
                ],
                'portal_url'   => 'https://broker.molinahealthcare.com',
                'phone'        => '1-866-448-6136',
                'email'        => 'broker.services@molinahealthcare.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'Cigna Healthcare',
                'city'         => 'Bloomfield',
                'state'        => 'CT',
                'carrier_code' => '67369',
                'carrier_type' => 'Health Carrier',
                'lines'        => [
                    'ACA / Obamacare',
                    'Medicare Advantage',
                    'Medicare Supplement (Medigap)',
                    'Dental & Vision',
                ],
                'portal_url'   => 'https://producers.cigna.com',
                'phone'        => '1-877-244-6215',
                'email'        => 'brokersupport@cigna.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'Mutual of Omaha',
                'city'         => 'Omaha',
                'state'        => 'NE',
                'carrier_code' => '71412',
                'carrier_type' => 'Life Carrier',
                'lines'        => [
                    'Medicare Supplement (Medigap)',
                    'Indexed Universal Life (IUL)',
                    'Term Life',
                    'Final Expense',
                ],
                'portal_url'   => 'https://www.mutualofomaha.com/broker',
                'phone'        => '1-800-867-6878',
                'email'        => 'sales.support@mutualofomaha.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'Americo Financial Life',
                'city'         => 'Kansas City',
                'state'        => 'MO',
                'carrier_code' => '61999',
                'carrier_type' => 'Life Carrier',
                'lines'        => [
                    'Indexed Universal Life (IUL)',
                    'Final Expense',
                ],
                'portal_url'   => 'https://www.americo.com/agent-portal',
                'phone'        => '1-800-231-0801',
                'email'        => 'agent.services@americo.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'National General (Allstate Health Solutions)',
                'city'         => 'Winston-Salem',
                'state'        => 'NC',
                'carrier_code' => '23728',
                'carrier_type' => 'Health Carrier',
                'lines'        => [
                    'Hospital Indemnity',
                    'Dental & Vision',
                ],
                'portal_url'   => 'https://natgenhealth.com',
                'phone'        => '1-888-781-0585',
                'email'        => 'service@natgenhealth.com',
                'status'       => 'Active / Contracted',
            ],
            [
                'name'         => 'Delta Dental',
                'city'         => 'San Francisco',
                'state'        => 'CA',
                'carrier_code' => '54941',
                'carrier_type' => 'Health Carrier',
                'lines'        => [
                    'Dental & Vision',
                ],
                'portal_url'   => 'https://www.deltadentalins.com/brokers',
                'phone'        => '1-800-521-2651',
                'email'        => 'brokersupport@deltadental.com',
                'status'       => 'Active / Contracted',
            ],
        ];

        foreach ( as ) {
             = DB::table('organizations')->where('name', ['name'])->first();

             = [
                'address'  => '',
                'city'     => ['city'],
                'state'    => ['state'],
                'country'  => 'US',
                'postcode' => '',
            ];

            if () {
                 = ->id;
                DB::table('organizations')->where('id', )->update([
                    'address'    => json_encode(),
                    'updated_at' => ,
                ]);
            } else {
                 = DB::table('organizations')->insertGetId([
                    'name'       => ['name'],
                    'address'    => json_encode(),
                    'user_id'    => ,
                    'created_at' => ,
                    'updated_at' => ,
                ]);
            }

            // Save attribute values for this organization
            if (isset(['carrier_code'])) {
                ->saveAttributeValue(['carrier_code'], , 'organizations', 'text_value', ['carrier_code']);
            }

            if (isset(['carrier_type']) && isset(['carrier_type'][['carrier_type']])) {
                ->saveAttributeValue(['carrier_type'], , 'organizations', 'integer_value', ['carrier_type'][['carrier_type']]);
            }

            if (isset(['lines_of_business'])) {
                 = [];
                foreach (['lines'] as ) {
                    if (isset(['lines_of_business'][])) {
                        [] = ['lines_of_business'][];
                    }
                }
                if (! empty()) {
                    ->saveAttributeValue(['lines_of_business'], , 'organizations', 'text_value', implode(',', ));
                }
            }

            if (isset(['broker_portal_url'])) {
                ->saveAttributeValue(['broker_portal_url'], , 'organizations', 'text_value', ['portal_url']);
            }

            if (isset(['agent_support_phone'])) {
                ->saveAttributeValue(['agent_support_phone'], , 'organizations', 'text_value', ['phone']);
            }

            if (isset(['agent_support_email'])) {
                ->saveAttributeValue(['agent_support_email'], , 'organizations', 'text_value', ['email']);
            }

            if (isset(['carrier_status']) && isset(['carrier_status'][['status']])) {
                ->saveAttributeValue(['carrier_status'], , 'organizations', 'integer_value', ['carrier_status'][['status']]);
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

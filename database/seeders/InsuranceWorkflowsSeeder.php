<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceWorkflowsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // 1. Resolve ACA / Healthcare Pipeline (default)
        $pipeline = DB::table('lead_pipelines')
            ->where('name', 'ACA / Individual Health (Obamacare)')
            ->first();

        if (! $pipeline) {
            $pipeline = DB::table('lead_pipelines')->where('is_default', 1)->first()
                ?? DB::table('lead_pipelines')->first();
        }

        if (! $pipeline) {
            return;
        }

        // 2. Resolve Pipeline Stages for this pipeline
        $consentStage = DB::table('lead_pipeline_stages')
            ->where('lead_pipeline_id', $pipeline->id)
            ->where(function ($query) {
                $query->where('code', 'consent_docs')
                    ->orWhere('name', 'like', '%Consent%');
            })
            ->first();

        $censusStage = DB::table('lead_pipeline_stages')
            ->where('lead_pipeline_id', $pipeline->id)
            ->where(function ($query) {
                $query->where('code', 'census')
                    ->orWhere('name', 'like', '%Income%');
            })
            ->first();

        $wonStage = DB::table('lead_pipeline_stages')
            ->where('lead_pipeline_id', $pipeline->id)
            ->where(function ($query) {
                $query->where('code', 'won')
                    ->orWhere('name', 'like', '%Won%')
                    ->orWhere('name', 'like', '%Issued%');
            })
            ->first();

        // 3. Resolve Email Templates
        $consentTemplate = DB::table('email_templates')
            ->where('name', 'Insurance: CMS Consent Form Confirmation')
            ->first();

        $dmiTemplate = DB::table('email_templates')
            ->where('name', 'Insurance: Eligibility Documents Request (DMI)')
            ->first();

        $welcomeTemplate = DB::table('email_templates')
            ->where('name', 'Insurance: Policy Issued & Welcome Packet')
            ->first();

        $workflows = [];

        // Workflow 1: CMS Consent Compliance Automation
        if ($consentStage && $consentTemplate) {
            $workflows[] = [
                'name'           => 'Insurance: CMS Consent Compliance Automation',
                'description'    => 'Automatically sends CMS Consent Form to applicant and logs compliance activity note when lead reaches Signed Consent & Docs.',
                'entity_type'    => 'leads',
                'event'          => 'lead.update.after',
                'condition_type' => 'and',
                'conditions'     => json_encode([
                    [
                        'attribute'      => 'lead_pipeline_stage_id',
                        'operator'       => '==',
                        'attribute_type' => 'lookup',
                        'value'          => (string) $consentStage->id,
                    ],
                ]),
                'actions'        => json_encode([
                    [
                        'id'    => 'send_email_to_person',
                        'value' => (string) $consentTemplate->id,
                    ],
                    [
                        'id'    => 'add_tag',
                        'value' => 'CMS Consent Pending',
                    ],
                    [
                        'id'    => 'add_note_as_activity',
                        'value' => 'Automated Insurance Workflow: Sent CMS Consent confirmation and compliance packet to applicant.',
                    ],
                ]),
            ];
        }

        // Workflow 2: DMI & Income Verification Request Automation
        if ($censusStage && $dmiTemplate) {
            $workflows[] = [
                'name'           => 'Insurance: DMI & Proof of Income Automation',
                'description'    => 'Automatically sends Data Matching Issue (DMI) income & identity verification request to applicant when lead reaches Census / Income & APTC stage.',
                'entity_type'    => 'leads',
                'event'          => 'lead.update.after',
                'condition_type' => 'and',
                'conditions'     => json_encode([
                    [
                        'attribute'      => 'lead_pipeline_stage_id',
                        'operator'       => '==',
                        'attribute_type' => 'lookup',
                        'value'          => (string) $censusStage->id,
                    ],
                ]),
                'actions'        => json_encode([
                    [
                        'id'    => 'send_email_to_person',
                        'value' => (string) $dmiTemplate->id,
                    ],
                    [
                        'id'    => 'add_tag',
                        'value' => 'DMI Pending',
                    ],
                    [
                        'id'    => 'add_note_as_activity',
                        'value' => 'Automated Insurance Workflow: Proof of Income & Identity (DMI) document request sent to applicant.',
                    ],
                ]),
            ];
        }

        // Workflow 3: Policy Bound & Welcome Packet Automation
        if ($wonStage && $welcomeTemplate) {
            $workflows[] = [
                'name'           => 'Insurance: Policy Bound & Welcome Packet Automation',
                'description'    => 'Automatically sends Welcome Packet with Member ID instructions, carrier portal link, and binder confirmation when policy is issued / Won.',
                'entity_type'    => 'leads',
                'event'          => 'lead.update.after',
                'condition_type' => 'and',
                'conditions'     => json_encode([
                    [
                        'attribute'      => 'lead_pipeline_stage_id',
                        'operator'       => '==',
                        'attribute_type' => 'lookup',
                        'value'          => (string) $wonStage->id,
                    ],
                ]),
                'actions'        => json_encode([
                    [
                        'id'    => 'send_email_to_person',
                        'value' => (string) $welcomeTemplate->id,
                    ],
                    [
                        'id'    => 'add_tag',
                        'value' => 'Policy Active',
                    ],
                    [
                        'id'    => 'add_note_as_activity',
                        'value' => 'Automated Insurance Workflow: Policy bound successfully! Member Welcome Packet sent via email.',
                    ],
                ]),
            ];
        }

        foreach ($workflows as $wf) {
            $existing = DB::table('workflows')->where('name', $wf['name'])->first();

            if ($existing) {
                DB::table('workflows')->where('id', $existing->id)->update(array_merge($wf, [
                    'updated_at' => $now,
                ]));
            } else {
                DB::table('workflows')->insert(array_merge($wf, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }
}

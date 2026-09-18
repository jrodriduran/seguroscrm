<?php

namespace Webkul\Lead\Services;

use Webkul\Lead\Models\Lead;

class HealthSherpaBridgeService
{
    /**
     * Build pre-filled HealthSherpa / ACA Marketplace payload and deep-link URL.
     */
    public function generateBridgeData(Lead $lead): array
    {
        $lead->loadMissing(['person', 'user', 'householdMembers', 'quotes']);

        $person = $lead->person;
        $user = $lead->user;
        $household = $lead->householdMembers;
        $primaryQuote = $lead->quotes?->first();

        // 1. Demographics & Address
        $zipCode = null;
        $state = null;

        // Extract ZIP code from person or title
        if ($person && ! empty($person->address)) {
            if (preg_match('/\b\d{5}\b/', $person->address, $matches)) {
                $zipCode = $matches[0];
            }
        }

        if (! $zipCode && preg_match('/\b\d{5}\b/', $lead->title, $matches)) {
            $zipCode = $matches[0];
        }

        $zipCode = $zipCode ?: '33101'; // Default Florida Miami-Dade if not specified

        // 2. Household members mapping
        $applicants = [];

        // Primary Subscriber
        $primaryDob = $person?->date_of_birth ?: '1985-05-15';
        $applicants[] = [
            'role' => 'primary',
            'first_name' => explode(' ', $person?->name ?: $lead->title)[0] ?? 'Cliente',
            'last_name' => explode(' ', $person?->name ?: $lead->title)[1] ?? 'Asegurado',
            'dob' => $primaryDob,
            'gender' => 'male',
            'applying_for_coverage' => true,
        ];

        // Dependents from Household
        if ($household && $household->isNotEmpty()) {
            foreach ($household as $member) {
                $applicants[] = [
                    'role' => $member->relationship,
                    'first_name' => explode(' ', $member->name)[0] ?? 'Dependiente',
                    'last_name' => explode(' ', $member->name)[1] ?? '',
                    'dob' => $member->date_of_birth,
                    'gender' => $member->gender ?: 'female',
                    'applying_for_coverage' => (bool) $member->is_applying_coverage,
                ];
            }
        }

        // 3. Financials
        $annualIncome = 28000.00; // Standard benchmark for high Silver CSR subsidy
        if ($primaryQuote && $primaryQuote->annual_income) {
            $annualIncome = (float) $primaryQuote->annual_income;
        }

        $householdSize = count($applicants);

        // 4. Agent Identifiers
        $agentNpn = $user?->npn ?: '19827364'; // Default/configured NPN
        $healthSherpaAgentId = config('services.healthsherpa.agent_id', 'AG-USA-CRM');

        // 5. Generate Query String for HealthSherpa Marketplace
        $queryParams = http_build_query([
            'zip_code' => $zipCode,
            'income' => $annualIncome,
            'household_size' => $householdSize,
            'client_name' => $person?->name ?: $lead->title,
            'client_phone' => collect($person?->contact_numbers ?? [])->first()['value'] ?? '',
            'client_email' => collect($person?->emails ?? [])->first()['value'] ?? '',
            'npn' => $agentNpn,
            'source' => 'krayin_crm',
        ]);

        $deepLinkUrl = "https://www.healthsherpa.com/insurance-plans/quote?{$queryParams}";

        return [
            'lead_id' => $lead->id,
            'client_name' => $person?->name ?: $lead->title,
            'zip_code' => $zipCode,
            'annual_income' => $annualIncome,
            'household_size' => $householdSize,
            'applicants' => $applicants,
            'agent_npn' => $agentNpn,
            'deep_link_url' => $deepLinkUrl,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}

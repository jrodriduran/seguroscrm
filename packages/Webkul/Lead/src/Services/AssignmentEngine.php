<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Repositories\AssignmentRuleRepository;
use Webkul\User\Repositories\UserRepository;

class AssignmentEngine
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected AssignmentRuleRepository $assignmentRuleRepository,
        protected UserRepository $userRepository
    ) {}

    /**
     * Determine and assign user_id for a lead based on pipeline configuration,
     * state licensing compliance, and preferred language matching.
     *
     * @param  array|Lead  $leadData
     * @return int|null Assigned user ID, or null if unassigned / manual
     */
    public function assign(array|object &$leadData): ?int
    {
        $pipelineId = is_array($leadData)
            ? ($leadData['lead_pipeline_id'] ?? null)
            : ($leadData->lead_pipeline_id ?? null);

        $rule = $this->assignmentRuleRepository->getRuleForPipeline($pipelineId);

        if (! $rule || ! $rule->is_active) {
            return null;
        }

        $strategy = $rule->strategy ?? 'round_robin';

        if ($strategy === 'manual') {
            return null;
        }

        // Get pool of agents
        $poolIds = $rule->agent_ids ?? [];

        if (empty($poolIds)) {
            $poolIds = $this->userRepository->findWhere(['status' => 1])->pluck('id')->toArray();
        }

        if (empty($poolIds)) {
            $this->recordFailureReason($leadData, 'no_active_agents_configured');

            return null;
        }

        // 1. Mandatory State License Gate (CMS & State DOI Compliance)
        $stateCode = $this->extractStateCode($leadData);
        if ($stateCode) {
            $licensedAgentIds = $this->filterByStateLicense($poolIds, $stateCode, 'health');

            if (empty($licensedAgentIds)) {
                $this->recordFailureReason($leadData, 'no_licensed_agents_in_state');

                return null;
            }

            $poolIds = $licensedAgentIds;
        }

        // 2. Language Matching Filter (Preferred Bilingual Routing)
        $language = $this->extractLanguage($leadData);
        if ($language) {
            $languageAgentIds = $this->filterByLanguage($poolIds, $language);

            // Prioritize language-matched agents if available; fallback to licensed pool if none
            if (! empty($languageAgentIds)) {
                $poolIds = $languageAgentIds;
            }
        }

        // 3. Capacity Filter
        $maxCapacity = (int) ($rule->max_capacity ?? 0);
        $eligibleAgents = $this->filterByCapacity($poolIds, $maxCapacity);

        if (empty($eligibleAgents)) {
            $this->recordFailureReason($leadData, 'max_capacity_exceeded');

            return null;
        }

        // 4. Distribution Strategy
        $selectedUserId = $strategy === 'least_loaded'
            ? $this->selectLeastLoaded($eligibleAgents)
            : $this->selectRoundRobin($rule, $eligibleAgents);

        // Clear any previous failure reason upon successful assignment
        $this->clearFailureReason($leadData);

        return $selectedUserId;
    }

    /**
     * Filter agents who hold an active, non-expired insurance license in the given state
     * with authority to sell health/ACA products.
     */
    public function filterByStateLicense(array $agentIds, string $stateCode, string $lineOfAuthority = 'health'): array
    {
        if (empty($agentIds) || empty($stateCode)) {
            return $agentIds;
        }

        $stateCode = strtoupper(trim($stateCode));
        $today = Carbon::today()->toDateString();

        $licenses = DB::table('user_agent_licenses')
            ->whereIn('user_id', $agentIds)
            ->where('state_code', $stateCode)
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', $today)
            ->get(['user_id', 'lines_of_authority']);

        $validUserIds = [];

        foreach ($licenses as $lic) {
            $lines = $lic->lines_of_authority;

            if (is_string($lines)) {
                $lines = json_decode($lines, true);
            }

            // If empty or null, license covers general authorities
            if (empty($lines) || ! is_array($lines)) {
                $validUserIds[] = (int) $lic->user_id;

                continue;
            }

            $normalizedLines = array_map('strtolower', $lines);
            $targetLine = strtolower($lineOfAuthority);

            if (
                in_array($targetLine, $normalizedLines)
                || in_array('health', $normalizedLines)
                || in_array('aca', $normalizedLines)
                || in_array('salud', $normalizedLines)
            ) {
                $validUserIds[] = (int) $lic->user_id;
            }
        }

        return array_values(array_intersect($agentIds, array_unique($validUserIds)));
    }

    /**
     * Filter agents who speak the customer's preferred language (e.g. 'es', 'en').
     */
    public function filterByLanguage(array $agentIds, string $language): array
    {
        if (empty($agentIds) || empty($language)) {
            return $agentIds;
        }

        $targetLang = strtolower(trim($language));

        $users = DB::table('users')
            ->whereIn('id', $agentIds)
            ->select('id', 'spoken_languages')
            ->get();

        $matchedIds = [];

        foreach ($users as $user) {
            $spoken = $user->spoken_languages;

            if (is_string($spoken)) {
                $spoken = json_decode($spoken, true);
            }

            // Default to bilingual ['es', 'en'] if unspecified
            if (empty($spoken) || ! is_array($spoken)) {
                $matchedIds[] = (int) $user->id;

                continue;
            }

            $spokenNormalized = array_map('strtolower', $spoken);

            if (in_array($targetLang, $spokenNormalized)) {
                $matchedIds[] = (int) $user->id;
            }
        }

        return array_values(array_intersect($agentIds, $matchedIds));
    }

    /**
     * Filter agents who have not exceeded max active capacity.
     */
    public function filterByCapacity(array $agentIds, int $maxCapacity): array
    {
        if ($maxCapacity <= 0) {
            return $agentIds;
        }

        $activeCounts = DB::table('leads')
            ->whereIn('user_id', $agentIds)
            ->whereIn('sla_status', ['pending', 'active', 'overdue', 'escalated'])
            ->select('user_id', DB::raw('count(*) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->toArray();

        return array_values(array_filter($agentIds, function ($id) use ($activeCounts, $maxCapacity) {
            $count = $activeCounts[$id] ?? 0;

            return $count < $maxCapacity;
        }));
    }

    /**
     * Audit lead assignment factors: returns detailed diagnostics on eligibility.
     */
    public function auditLeadAssignment(array|object $leadData): array
    {
        $pipelineId = is_array($leadData)
            ? ($leadData['lead_pipeline_id'] ?? null)
            : ($leadData->lead_pipeline_id ?? null);

        $rule = $this->assignmentRuleRepository->getRuleForPipeline($pipelineId);

        $stateCode = $this->extractStateCode($leadData);
        $language = $this->extractLanguage($leadData);

        $poolIds = $rule?->agent_ids ?? [];
        if (empty($poolIds)) {
            $poolIds = $this->userRepository->findWhere(['status' => 1])->pluck('id')->toArray();
        }

        $licensedPool = $stateCode ? $this->filterByStateLicense($poolIds, $stateCode, 'health') : $poolIds;
        $languagePool = $language ? $this->filterByLanguage($licensedPool, $language) : $licensedPool;

        $maxCapacity = (int) ($rule?->max_capacity ?? 0);
        $finalPool = $this->filterByCapacity(! empty($languagePool) ? $languagePool : $licensedPool, $maxCapacity);

        return [
            'state_code' => $stateCode,
            'preferred_language' => $language,
            'initial_pool_count' => count($poolIds),
            'licensed_pool_count' => count($licensedPool),
            'language_pool_count' => count($languagePool),
            'capacity_eligible_count' => count($finalPool),
            'eligible_agent_ids' => $finalPool,
            'is_routable' => ! empty($finalPool),
            'failure_reason' => empty($licensedPool) && $stateCode
                ? 'no_licensed_agents_in_state'
                : (empty($finalPool) && $maxCapacity > 0 ? 'max_capacity_exceeded' : null),
        ];
    }

    /**
     * Reassign an existing lead applying compliance rules.
     */
    public function reassignWithCompliance(Lead $lead): ?int
    {
        $newUserId = $this->assign($lead);

        if ($newUserId) {
            $lead->update([
                'user_id' => $newUserId,
                'assigned_at' => Carbon::now(),
                'assignment_failure_reason' => null,
            ]);
        }

        return $newUserId;
    }

    /**
     * Extract 2-letter state code from leadData or related person.
     */
    protected function extractStateCode(array|object $leadData): ?string
    {
        $state = is_array($leadData)
            ? ($leadData['state_code'] ?? $leadData['state'] ?? null)
            : ($leadData->state_code ?? ($leadData->state ?? null));

        if (! empty($state)) {
            return strtoupper(substr(trim($state), 0, 2));
        }

        $personId = is_array($leadData) ? ($leadData['person_id'] ?? null) : ($leadData->person_id ?? null);
        if ($personId) {
            $personAddressState = DB::table('addresses')
                ->where('address_type', 'Webkul\\Contact\\Models\\Person')
                ->where('entity_id', $personId)
                ->value('state');

            if (! empty($personAddressState)) {
                return strtoupper(substr(trim($personAddressState), 0, 2));
            }
        }

        return null;
    }

    /**
     * Extract preferred customer language ('es', 'en', etc.).
     */
    protected function extractLanguage(array|object $leadData): ?string
    {
        $lang = is_array($leadData)
            ? ($leadData['preferred_language'] ?? $leadData['language'] ?? null)
            : ($leadData->preferred_language ?? ($leadData->language ?? null));

        if (! empty($lang)) {
            return strtolower(trim($lang));
        }

        return 'es';
    }

    /**
     * Record failure reason on lead data.
     */
    protected function recordFailureReason(array|object &$leadData, string $reason): void
    {
        if ($leadData instanceof Lead && $leadData->exists) {
            $leadData->update(['assignment_failure_reason' => $reason]);
        } elseif (is_array($leadData)) {
            $leadData['assignment_failure_reason'] = $reason;
        }
    }

    /**
     * Clear failure reason on lead data when assignment succeeds.
     */
    protected function clearFailureReason(array|object &$leadData): void
    {
        if ($leadData instanceof Lead && $leadData->exists) {
            if ($leadData->assignment_failure_reason) {
                $leadData->update(['assignment_failure_reason' => null]);
            }
        } elseif (is_array($leadData)) {
            $leadData['assignment_failure_reason'] = null;
        }
    }

    /**
     * Select agent with the least number of active leads.
     */
    protected function selectLeastLoaded(array $agentIds): int
    {
        $counts = DB::table('leads')
            ->whereIn('user_id', $agentIds)
            ->whereIn('sla_status', ['pending', 'active', 'overdue', 'escalated'])
            ->select('user_id', DB::raw('count(*) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->toArray();

        $selectedId = $agentIds[0];
        $minCount = $counts[$selectedId] ?? 0;

        foreach ($agentIds as $agentId) {
            $agentCount = $counts[$agentId] ?? 0;

            if ($agentCount < $minCount) {
                $minCount = $agentCount;
                $selectedId = $agentId;
            }
        }

        return (int) $selectedId;
    }

    /**
     * Select agent using round-robin rotation and increment the pointer.
     */
    protected function selectRoundRobin(object $rule, array $eligibleAgents): int
    {
        $count = count($eligibleAgents);
        $pointer = (int) ($rule->rr_pointer ?? 0);

        $selectedId = $eligibleAgents[$pointer % $count];

        if (isset($rule->id)) {
            $rule->update([
                'rr_pointer' => ($pointer + 1) % 1000000,
            ]);
        }

        return (int) $selectedId;
    }
}

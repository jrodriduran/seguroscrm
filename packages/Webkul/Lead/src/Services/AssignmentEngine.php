<?php

namespace Webkul\Lead\Services;

use Illuminate\Support\Facades\DB;
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
     * Determine and assign user_id for a lead based on pipeline configuration.
     *
     * @param  array|\Webkul\Lead\Models\Lead  $leadData
     * @return int|null Assigned user ID, or null if unassigned / manual
     */
    public function assign(array|object $leadData): ?int
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
            return null;
        }

        $maxCapacity = (int) ($rule->max_capacity ?? 0);

        // Filter by capacity if set
        $eligibleAgents = $this->filterByCapacity($poolIds, $maxCapacity);

        if (empty($eligibleAgents)) {
            // If all eligible agents are at max capacity, lead remains unassigned for MA triage
            return null;
        }

        if ($strategy === 'least_loaded') {
            return $this->selectLeastLoaded($eligibleAgents);
        }

        // Default: Round Robin
        return $this->selectRoundRobin($rule, $eligibleAgents);
    }

    /**
     * Filter agents who have not exceeded max active capacity.
     */
    protected function filterByCapacity(array $agentIds, int $maxCapacity): array
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
        $minCount   = $counts[$selectedId] ?? 0;

        foreach ($agentIds as $agentId) {
            $agentCount = $counts[$agentId] ?? 0;

            if ($agentCount < $minCount) {
                $minCount   = $agentCount;
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
        $count   = count($eligibleAgents);
        $pointer = (int) ($rule->rr_pointer ?? 0);

        $selectedId = $eligibleAgents[$pointer % $count];

        // Increment pointer
        if (isset($rule->id)) {
            $rule->update([
                'rr_pointer' => ($pointer + 1) % 1000000,
            ]);
        }

        return (int) $selectedId;
    }
}

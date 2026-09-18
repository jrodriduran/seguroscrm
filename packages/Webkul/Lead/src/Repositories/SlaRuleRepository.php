<?php

namespace Webkul\Lead\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Lead\Contracts\SlaRule;

class SlaRuleRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return SlaRule::class;
    }

    /**
     * Resolve the most specific active SLA rule for a given pipeline and lead type.
     *
     * Hierarchy:
     * 1. Pipeline + Lead Type
     * 2. Pipeline (any type)
     * 3. Global rule (pipeline_id is null)
     * 4. Safe fallback defaults (2h first contact, 24h follow up, 4h escalation)
     */
    public function getRuleForLead(?int $pipelineId, ?int $leadTypeId = null): object
    {
        // 1. Try matching pipeline + lead type
        if ($pipelineId && $leadTypeId) {
            $rule = $this->findOneWhere([
                'lead_pipeline_id' => $pipelineId,
                'lead_type_id' => $leadTypeId,
                'is_active' => 1,
            ]);

            if ($rule) {
                return $rule;
            }
        }

        // 2. Try matching pipeline only
        if ($pipelineId) {
            $rule = $this->findOneWhere([
                'lead_pipeline_id' => $pipelineId,
                'lead_type_id' => null,
                'is_active' => 1,
            ]);

            if ($rule) {
                return $rule;
            }
        }

        // 3. Global rule
        $globalRule = $this->findOneWhere([
            'lead_pipeline_id' => null,
            'is_active' => 1,
        ]);

        if ($globalRule) {
            return $globalRule;
        }

        // 4. Fallback in-memory dummy object
        return (object) [
            'id' => null,
            'lead_pipeline_id' => $pipelineId,
            'lead_type_id' => $leadTypeId,
            'first_contact_hours' => 2,
            'follow_up_hours' => 24,
            'escalation_hours' => 4,
            'is_active' => true,
        ];
    }
}

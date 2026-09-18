<?php

namespace Webkul\Lead\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Lead\Contracts\AssignmentRule;

class AssignmentRuleRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return AssignmentRule::class;
    }

    /**
     * Get the active assignment rule for a pipeline, or fallback to global rule.
     */
    public function getRuleForPipeline(?int $pipelineId): ?object
    {
        if ($pipelineId) {
            $rule = $this->findOneWhere([
                'lead_pipeline_id' => $pipelineId,
                'is_active' => 1,
            ]);

            if ($rule) {
                return $rule;
            }
        }

        // Global fallback (null pipeline_id)
        return $this->findOneWhere([
            'lead_pipeline_id' => null,
            'is_active' => 1,
        ]);
    }
}

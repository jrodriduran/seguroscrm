<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\LeadRxMedication as LeadRxMedicationContract;

class LeadRxMedication extends Model implements LeadRxMedicationContract
{
    protected $table = 'lead_rx_medications';

    protected $fillable = [
        'lead_id',
        'medication_name',
        'dosage',
        'frequency',
        'quantity_per_30_days',
        'drug_tier',
        'requires_prior_authorization',
        'requires_step_therapy',
        'has_quantity_limit',
        'estimated_copay_30d',
        'estimated_copay_90d_mail',
        'notes',
    ];

    protected $casts = [
        'requires_prior_authorization' => 'boolean',
        'requires_step_therapy' => 'boolean',
        'has_quantity_limit' => 'boolean',
        'estimated_copay_30d' => 'float',
        'estimated_copay_90d_mail' => 'float',
        'quantity_per_30_days' => 'integer',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass());
    }

    public function getTierColorAttribute(): string
    {
        return match (true) {
            str_contains($this->drug_tier, 'Tier 1') => 'emerald',
            str_contains($this->drug_tier, 'Tier 2') => 'teal',
            str_contains($this->drug_tier, 'Tier 3') => 'sky',
            str_contains($this->drug_tier, 'Tier 4') => 'amber',
            str_contains($this->drug_tier, 'Tier 5') => 'purple',
            default => 'gray',
        };
    }

    /**
     * Return array of active restriction codes (PA, ST, QL)
     */
    public function getRestrictionCodes(): array
    {
        $codes = [];

        if ($this->requires_prior_authorization) {
            $codes[] = ['code' => 'PA', 'label' => 'Prior Authorization Required', 'color' => 'amber'];
        }

        if ($this->requires_step_therapy) {
            $codes[] = ['code' => 'ST', 'label' => 'Step Therapy Required', 'color' => 'blue'];
        }

        if ($this->has_quantity_limit) {
            $codes[] = ['code' => 'QL', 'label' => 'Quantity Limit Enforced', 'color' => 'rose'];
        }

        return $codes;
    }
}

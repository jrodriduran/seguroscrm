<?php

namespace Webkul\Communications\Listeners;

use Illuminate\Support\Facades\Event;
use Webkul\Lead\Models\InsurancePolicy;

/**
 * Announces policy lifecycle changes (issued, effectuated, grace period,
 * cancelled, renewed…) so communication sequences can react to them.
 */
class PolicyLifecycleObserver
{
    public function created(InsurancePolicy $policy): void
    {
        Event::dispatch('communications.policy.created', [$policy]);
    }

    public function updated(InsurancePolicy $policy): void
    {
        if ($policy->wasChanged('status')) {
            Event::dispatch('communications.policy.status_changed', [$policy, $policy->getOriginal('status'), $policy->status]);
        }

        if ($policy->wasChanged('binder_payment_status')) {
            Event::dispatch('communications.policy.binder_changed', [$policy, $policy->getOriginal('binder_payment_status'), $policy->binder_payment_status]);
        }

        if ($policy->wasChanged('effectuation_date') && $policy->effectuation_date && ! $policy->getOriginal('effectuation_date')) {
            Event::dispatch('communications.policy.effectuated', [$policy]);
        }
    }
}

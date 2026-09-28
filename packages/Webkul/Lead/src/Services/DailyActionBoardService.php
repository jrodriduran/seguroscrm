<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadDmiDocument;
use Webkul\Lead\Models\SepQualification;

class DailyActionBoardService
{
    /**
     * Compile all actionable items and summary metrics for the agent's morning dashboard.
     */
    public function getActionBoardData(?int $userId = null): array
    {
        $binderUrgent = $this->getBinderPendingUrgent($userId);
        $dmiCritical = $this->getDmiCriticalUrgent($userId);
        $gracePeriods = $this->getGracePeriodUrgent($userId);
        $sepExpiring = $this->getSepExpiringUrgent($userId);

        $totalActions = count($binderUrgent) + count($dmiCritical) + count($gracePeriods) + count($sepExpiring);

        $revenueAtRisk = 0.0;
        foreach ($gracePeriods as $item) {
            $revenueAtRisk += (float) ($item['monthly_premium'] ?? 0);
        }
        foreach ($binderUrgent as $item) {
            $revenueAtRisk += (float) ($item['monthly_premium'] ?? 0);
        }

        return [
            'metrics' => [
                'total_urgent_actions' => $totalActions,
                'binder_pending_count' => count($binderUrgent),
                'dmi_critical_count' => count($dmiCritical),
                'grace_period_count' => count($gracePeriods),
                'sep_expiring_count' => count($sepExpiring),
                'revenue_at_risk_amount' => round($revenueAtRisk, 2),
            ],
            'binder_pending' => $binderUrgent,
            'dmi_critical' => $dmiCritical,
            'grace_periods' => $gracePeriods,
            'sep_expiring' => $sepExpiring,
        ];
    }

    /**
     * Get policies awaiting initial binder payment next to deadline.
     */
    public function getBinderPendingUrgent(?int $userId = null): array
    {
        $query = InsurancePolicy::with(['lead.person'])
            ->where(function ($q) {
                $q->where('status', 'binder_pending')
                    ->orWhere('binder_payment_status', 'pending');
            });

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $policies = $query->orderBy('binder_due_date', 'asc')->limit(15)->get();
        $today = Carbon::today();

        return $policies->map(function ($policy) use ($today) {
            $dueDate = $policy->binder_due_date ? Carbon::parse($policy->binder_due_date) : null;
            $daysRemaining = $dueDate ? (int) $today->diffInDays($dueDate, false) : 0;

            return [
                'id' => $policy->id,
                'lead_id' => $policy->lead_id,
                'policy_number' => $policy->policy_number,
                'carrier_name' => $policy->carrier_name,
                'plan_name' => $policy->plan_name,
                'client_name' => $policy->insured_name ?: ($policy->lead?->person?->name ?? 'Asegurado'),
                'client_phone' => $this->extractLeadPhone($policy->lead),
                'monthly_premium' => (float) ($policy->net_premium ?? $policy->premium_amount ?? 0),
                'binder_amount' => (float) ($policy->binder_amount ?? $policy->net_premium ?? 0),
                'due_date' => $dueDate?->format('Y-m-d'),
                'days_remaining' => $daysRemaining,
                'urgency' => $daysRemaining <= 3 ? 'critical' : ($daysRemaining <= 7 ? 'warning' : 'info'),
                'action_url' => route('admin.insurance.policies.index', ['policy_id' => $policy->id]),
            ];
        })->toArray();
    }

    /**
     * Get DMI documents requiring customer proof with <= 15 days before cancellation of subsidies.
     */
    public function getDmiCriticalUrgent(?int $userId = null): array
    {
        $query = LeadDmiDocument::with(['lead.person'])
            ->where('status', 'pending_upload')
            ->whereDate('deadline_date', '<=', Carbon::today()->addDays(15));

        if ($userId) {
            $query->whereHas('lead', fn ($q) => $q->where('user_id', $userId));
        }

        $docs = $query->orderBy('deadline_date', 'asc')->limit(15)->get();

        return $docs->map(function ($doc) {
            $days = (int) $doc->days_remaining;

            return [
                'id' => $doc->id,
                'lead_id' => $doc->lead_id,
                'dmi_type' => $doc->doc_type ?? $doc->dmi_type,
                'title' => $doc->title,
                'client_name' => $doc->lead?->person?->name ?? 'Asegurado',
                'client_phone' => $this->extractLeadPhone($doc->lead),
                'due_date' => $doc->deadline_date ? Carbon::parse($doc->deadline_date)->format('Y-m-d') : null,
                'days_remaining' => $days,
                'urgency' => $days <= 5 ? 'critical' : ($days <= 10 ? 'warning' : 'info'),
                'action_url' => route('admin.leads.view', $doc->lead_id).'?tab=dmi_documents',
            ];
        })->toArray();
    }

    /**
     * Get policies currently in grace period (Month 1 vs Months 2-3).
     */
    public function getGracePeriodUrgent(?int $userId = null): array
    {
        $query = InsurancePolicy::with(['lead.person'])
            ->whereIn('status', ['grace_period_1', 'grace_period_2_3']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $policies = $query->orderBy('grace_period_days', 'desc')->limit(15)->get();

        return $policies->map(function ($policy) {
            $isCritical = $policy->status === 'grace_period_2_3';

            return [
                'id' => $policy->id,
                'lead_id' => $policy->lead_id,
                'policy_number' => $policy->policy_number,
                'carrier_name' => $policy->carrier_name,
                'client_name' => $policy->insured_name ?: ($policy->lead?->person?->name ?? 'Asegurado'),
                'client_phone' => $this->extractLeadPhone($policy->lead),
                'monthly_premium' => (float) ($policy->premium_amount ?? 0),
                'status' => $policy->status,
                'grace_period_days' => (int) $policy->grace_period_days,
                'stage_label' => $isCritical ? 'Mes 2-3 (Reclamos Suspendidos / Riesgo Clawback)' : 'Mes 1 (Cobertura Activa)',
                'urgency' => $isCritical ? 'critical' : 'warning',
                'action_url' => route('admin.insurance.policies.index', ['policy_id' => $policy->id]),
            ];
        })->toArray();
    }

    /**
     * Get active SEP qualification periods expiring within 14 days.
     */
    public function getSepExpiringUrgent(?int $userId = null): array
    {
        $query = SepQualification::with(['lead.person'])
            ->where('status', 'active');

        if ($userId) {
            $query->whereHas('lead', fn ($q) => $q->where('user_id', $userId));
        }

        $today = Carbon::today();
        $seps = $query->get()->filter(function ($sep) use ($today) {
            $deadline = $sep->event_date ? Carbon::parse($sep->event_date)->addDays(60) : null;
            if (! $deadline) {
                return false;
            }
            $diff = (int) $today->diffInDays($deadline, false);

            return $diff >= 0 && $diff <= 14;
        })->values();

        return $seps->map(function ($sep) use ($today) {
            $deadline = Carbon::parse($sep->event_date)->addDays(60);
            $diff = (int) $today->diffInDays($deadline, false);

            return [
                'id' => $sep->id,
                'lead_id' => $sep->lead_id,
                'sep_type' => $sep->sep_type,
                'client_name' => $sep->lead?->person?->name ?? 'Prospecto',
                'client_phone' => $this->extractLeadPhone($sep->lead),
                'days_remaining' => $diff,
                'deadline_date' => $deadline->format('Y-m-d'),
                'urgency' => $diff <= 5 ? 'critical' : 'warning',
                'action_url' => route('admin.leads.view', $sep->lead_id),
            ];
        })->toArray();
    }

    /**
     * Extract normalized phone number from lead's attached person.
     */
    protected function extractLeadPhone(?Lead $lead): ?string
    {
        if (! $lead || ! $lead->person) {
            return null;
        }

        $numbers = $lead->person->contact_numbers;

        if (is_array($numbers)) {
            return $numbers[0]['value'] ?? null;
        }

        if (is_object($numbers) && method_exists($numbers, 'first')) {
            return $numbers->first()->value ?? null;
        }

        return null;
    }
}

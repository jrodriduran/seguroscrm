<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Webkul\Lead\Models\UserAgentLicense;
use Webkul\User\Models\User;

class AgentComplianceService
{
    /**
     * Get complete licensing, AHIP, and E&O compliance summary for an agent.
     */
    public function getAgentCompliance(int $userId): array
    {
        $user = User::findOrFail($userId);
        $licenses = UserAgentLicense::where('user_id', $userId)->get();

        $today = Carbon::today();
        $isEoActive = false;
        $daysUntilEoExpiration = null;

        if ($user->eo_expires_at) {
            $eoDate = Carbon::parse($user->eo_expires_at);
            $daysUntilEoExpiration = (int) $today->diffInDays($eoDate, false);
            $isEoActive = $daysUntilEoExpiration >= 0;
        }

        $currentYear = (int) date('Y');
        $isAhipCertified = ($user->ahip_certified_year && $user->ahip_certified_year >= $currentYear);

        // Check licenses status
        $expiredLicensesCount = $licenses->filter(fn ($l) => $l->isExpired())->count();
        $expiringSoonCount = $licenses->filter(fn ($l) => $l->isExpiringSoon())->count();

        $overallStatus = 'compliant';
        $statusLabel = 'Licencias y E&O al Día';
        $badgeClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300';

        if (! $isEoActive || $expiredLicensesCount > 0) {
            $overallStatus = 'non_compliant';
            $statusLabel = 'Licencia o E&O Vencida (No Elegible)';
            $badgeClass = 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300';
        } elseif ($expiringSoonCount > 0 || ($daysUntilEoExpiration !== null && $daysUntilEoExpiration <= 30)) {
            $overallStatus = 'warning';
            $statusLabel = 'Vencimiento Próximo (<30 días)';
            $badgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300';
        }

        return [
            'user_id' => $user->id,
            'agent_name' => $user->name,
            'npn' => $user->npn ?: 'No registrado',
            'eo_insurance' => [
                'carrier' => $user->eo_carrier ?: 'No especificado',
                'policy_number' => $user->eo_policy_number ?: 'N/A',
                'expires_at' => $user->eo_expires_at ? Carbon::parse($user->eo_expires_at)->toDateString() : null,
                'is_active' => $isEoActive,
                'days_remaining' => $daysUntilEoExpiration,
            ],
            'ahip_certification' => [
                'year' => $user->ahip_certified_year,
                'is_certified' => $isAhipCertified,
            ],
            'licenses' => $licenses,
            'status' => [
                'code' => $overallStatus,
                'label' => $statusLabel,
                'badge_class' => $badgeClass,
            ],
        ];
    }

    /**
     * Check if agent is licensed to sell in a specific state.
     */
    public function canSellInState(int $userId, string $stateCode): bool
    {
        $license = UserAgentLicense::where('user_id', $userId)
            ->where('state_code', strtoupper($stateCode))
            ->first();

        return $license && ! $license->isExpired();
    }
}

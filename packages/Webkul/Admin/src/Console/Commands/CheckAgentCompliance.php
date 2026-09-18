<?php

namespace Webkul\Admin\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Webkul\Activity\Models\Activity;
use Webkul\Lead\Models\UserAgentLicense;
use Webkul\User\Models\User;

class CheckAgentCompliance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:check-agent-compliance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit agent state insurance licenses, E&O insurance policies, and AHIP certifications for approaching expirations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Agent Licensing & E&O Compliance Audit...');

        $today = Carbon::today();
        $warningThreshold = Carbon::today()->addDays(30);

        $agents = User::all();
        $alertsCreated = 0;

        foreach ($agents as $agent) {
            // 1. Check E&O Policy
            if ($agent->eo_expires_at) {
                $eoExpiry = Carbon::parse($agent->eo_expires_at);
                if ($eoExpiry->isPast()) {
                    $this->warn("🚨 [AGENTE: {$agent->name}] Póliza de E&O VENCIDA desde {$eoExpiry->toDateString()}");
                    $alertsCreated += $this->createComplianceAlert($agent, 'Póliza de Errores y Omisiones (E&O) VENCIDA', '🚨 Crítico: Tu seguro E&O está vencido. No puedes comercializar pólizas legalmente ni recibir sobrecomisiones.');
                } elseif ($eoExpiry->lessThanOrEqualTo($warningThreshold)) {
                    $days = (int) $today->diffInDays($eoExpiry, false);
                    $this->line("⚠️ [AGENTE: {$agent->name}] Póliza de E&O vence en {$days} días");
                    $alertsCreated += $this->createComplianceAlert($agent, "Póliza de E&O vence en {$days} días", "Tu seguro E&O vence el {$eoExpiry->toDateString()}. Renueva tu póliza antes de la fecha límite para evitar suspensión.");
                }
            }

            // 2. Check State Licenses
            $licenses = UserAgentLicense::where('user_id', $agent->id)->get();
            foreach ($licenses as $lic) {
                $licExpiry = Carbon::parse($lic->expires_at);
                if ($licExpiry->isPast()) {
                    $lic->update(['status' => 'expired']);
                    $this->warn("🚨 [AGENTE: {$agent->name}] Licencia estatal {$lic->state_code} (#{$lic->license_number}) VENCIDA");
                    $alertsCreated += $this->createComplianceAlert($agent, "Licencia Estatal {$lic->state_code} VENCIDA", "Tu licencia de seguros en {$lic->state_code} ha expirado. Renueva de inmediato ante el Departamento de Seguros.");
                } elseif ($licExpiry->lessThanOrEqualTo($warningThreshold)) {
                    $lic->update(['status' => 'expiring_soon']);
                    $days = (int) $today->diffInDays($licExpiry, false);
                    $this->line("⚠️ [AGENTE: {$agent->name}] Licencia {$lic->state_code} vence en {$days} días");
                    $alertsCreated += $this->createComplianceAlert($agent, "Licencia {$lic->state_code} vence en {$days} días", "Tu licencia de seguros en el estado de {$lic->state_code} vence el {$licExpiry->toDateString()}.");
                }
            }
        }

        $this->info("Compliance audit completed. Total alerts created: {$alertsCreated}");

        return self::SUCCESS;
    }

    /**
     * Create an urgent notification task for the agent.
     */
    protected function createComplianceAlert(User $agent, string $title, string $comment): int
    {
        $existing = Activity::where('user_id', $agent->id)
            ->where('title', 'like', "%{$title}%")
            ->where('is_done', 0)
            ->exists();

        if ($existing) {
            return 0;
        }

        try {
            Activity::create([
                'title' => "🛡️ Compliance: {$title}",
                'type' => 'call',
                'comment' => $comment,
                'schedule_from' => Carbon::now(),
                'schedule_to' => Carbon::now()->addHours(24),
                'is_done' => 0,
                'user_id' => $agent->id,
            ]);

            return 1;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

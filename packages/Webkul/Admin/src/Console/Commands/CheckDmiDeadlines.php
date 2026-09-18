<?php

namespace Webkul\Admin\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Lead\Models\LeadDmiDocument;

class CheckDmiDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:check-dmi-deadlines
                            {--dry-run : List pending and overdue DMI documents without creating alert activities}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan Marketplace ACA 90-day DMI documents and create deadline alert activities for assigned agents.';

    /**
     * Execute the console command.
     */
    public function handle(ActivityRepository $activityRepository): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $today = Carbon::today();

        // 1. Fetch all documents that are not verified by CMS and have a deadline set
        $documents = LeadDmiDocument::with(['lead.person', 'lead.user'])
            ->where('status', '!=', 'verified_by_cms')
            ->whereNotNull('deadline_date')
            ->get();

        if ($documents->isEmpty()) {
            $this->info('[DMI Checker] No pending DMI documents found.');

            return self::SUCCESS;
        }

        $alertDocs = collect();

        foreach ($documents as $doc) {
            $days = (int) $today->diffInDays($doc->deadline_date, false);

            // We alert if document is expired (<0) or expiring within 30 days
            if ($days <= 30) {
                $urgency = match (true) {
                    $days < 0 => 'expired',
                    $days <= 5 => 'critical',
                    $days <= 15 => 'high',
                    default => 'warning',
                };

                $alertDocs->push([
                    'doc' => $doc,
                    'days' => $days,
                    'urgency' => $urgency,
                ]);
            }
        }

        if ($alertDocs->isEmpty()) {
            $this->info('[DMI Checker] All pending DMI documents are well within compliance (> 30 days).');

            return self::SUCCESS;
        }

        $this->warn("[DMI Checker] Found {$alertDocs->count()} document(s) requiring agent attention.");

        if ($dryRun) {
            $this->table(
                ['Doc ID', 'Lead ID', 'Client', 'Document Type', 'Deadline', 'Days Left', 'Urgency', 'Agent'],
                $alertDocs->map(fn ($item) => [
                    $item['doc']->id,
                    $item['doc']->lead_id,
                    $item['doc']->lead?->person?->name ?: $item['doc']->lead?->title ?: 'N/A',
                    $item['doc']->title ?: $item['doc']->doc_type_label,
                    $item['doc']->deadline_date?->format('Y-m-d'),
                    $item['days'] < 0 ? "Vencido ({$item['days']}d)" : "{$item['days']} días",
                    strtoupper($item['urgency']),
                    $item['doc']->lead?->user?->name ?: 'Sin Asignar',
                ])->toArray()
            );

            $this->info('[DMI Checker] Dry run complete. No activities created.');

            return self::SUCCESS;
        }

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($alertDocs as $item) {
            /** @var LeadDmiDocument $doc */
            $doc = $item['doc'];
            $days = $item['days'];
            $urgency = $item['urgency'];

            $lead = $doc->lead;
            if (! $lead) {
                continue;
            }

            // Anti-duplicate check: Avoid creating multiple activities for the same DMI doc within the same day
            $alreadyNotifiedToday = $lead->activities()
                ->where('activities.comment', 'LIKE', "%[DMI_DOC_ID:{$doc->id}]%")
                ->whereDate('activities.created_at', $today)
                ->exists();

            if ($alreadyNotifiedToday) {
                $skippedCount++;
                continue;
            }

            $docTitle = $doc->title ?: $doc->doc_type_label;
            $clientName = $lead->person?->name ?: $lead->title;
            $clientPhone = collect($lead->person?->contact_numbers ?? [])->first()['value'] ?? 'N/A';
            $deadlineStr = $doc->deadline_date ? $doc->deadline_date->format('d/m/Y') : 'N/A';

            $title = match ($urgency) {
                'expired' => "❌ [DMI VENCIDO] {$docTitle} - Subsidio en riesgo",
                'critical' => "🚨 [DMI CRÍTICO - {$days}d] {$docTitle} - {$clientName}",
                'high' => "⚠️ [DMI ALERTA - {$days}d] {$docTitle} - {$clientName}",
                default => "⏳ [DMI Recordatorio - {$days}d] {$docTitle} - {$clientName}",
            };

            $priority = match ($urgency) {
                'expired', 'critical' => 'urgent',
                'high' => 'high',
                default => 'normal',
            };

            $comment = "ALERTA AUTOMÁTICA DMI (Healthcare.gov)\n"
                ."----------------------------------------\n"
                ."Cliente: {$clientName}\n"
                ."Teléfono: {$clientPhone}\n"
                ."Documento Requerido: {$docTitle}\n"
                ."Fecha Límite Fatal: {$deadlineStr} (" . ($days < 0 ? "Venció hace ".abs($days)." días" : "Quedan {$days} días") . ")\n"
                ."Estatus Actual: {$doc->status_label}\n\n"
                ."ACCIÓN REQUERIDA:\n"
                ."Contactar al asegurado de inmediato para solicitar el documento y cargarlo en el portal antes del vencimiento para evitar la pérdida del subsidio APTC.\n\n"
                ."PLANTILLA WHATSAPP SUGERIDA:\n"
                ."{$doc->whatsapp_reminder_message}\n\n"
                ."[DMI_DOC_ID:{$doc->id}]";

            $now = Carbon::now();

            $activity = $activityRepository->create([
                'title' => $title,
                'type' => 'call',
                'comment' => $comment,
                'schedule_from' => $now,
                'schedule_to' => $now->copy()->addHours(2),
                'is_done' => false,
                'user_id' => $lead->user_id ?: 1,
                'priority' => $priority,
                'sla_activity_status' => 'pending',
            ]);

            $activity->leads()->attach($lead->id);
            $createdCount++;
        }

        $this->info("[DMI Checker] Processing completed. Created {$createdCount} activity alert(s). Skipped {$skippedCount} already notified today.");

        return self::SUCCESS;
    }
}

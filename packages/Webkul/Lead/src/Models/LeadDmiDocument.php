<?php

namespace Webkul\Lead\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Contracts\LeadDmiDocument as LeadDmiDocumentContract;

class LeadDmiDocument extends Model implements LeadDmiDocumentContract
{
    protected $table = 'lead_dmi_documents';

    protected $fillable = [
        'lead_id',
        'person_id',
        'doc_type',
        'title',
        'notice_date',
        'deadline_date',
        'status',
        'file_path',
        'file_name',
        'notes',
    ];

    protected $casts = [
        'notice_date' => 'date',
        'deadline_date' => 'date',
    ];

    protected $appends = [
        'days_remaining',
        'urgency_level',
        'doc_type_label',
        'status_label',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass(), 'person_id');
    }

    /**
     * Compute calendar days remaining to comply with Healthcare.gov
     */
    public function getDaysRemainingAttribute(): int
    {
        if (! $this->deadline_date) {
            return 0;
        }

        return (int) Carbon::today()->diffInDays($this->deadline_date, false);
    }

    /**
     * Urgency level:
     * - 'verified' (green check)
     * - 'critical' (<= 15 days or expired)
     * - 'warning' (16 - 45 days)
     * - 'normal' (> 45 days)
     */
    public function getUrgencyLevelAttribute(): string
    {
        if ($this->status === 'verified_by_cms') {
            return 'verified';
        }

        $days = $this->days_remaining;

        if ($days < 0) {
            return 'expired';
        }

        if ($days <= 15) {
            return 'critical';
        }

        if ($days <= 45) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Human readable document type label
     */
    public function getDocTypeLabelAttribute(): string
    {
        return match ($this->doc_type) {
            'income' => 'Prueba de Ingresos (W-2, Taxes, Paystubs)',
            'immigration' => 'Estatus Migratorio (Green Card, EAD I-766)',
            'citizenship' => 'Ciudadanía / Pasaporte / Certificado',
            'ssn' => 'Validación de SSN / Identidad',
            'incarceration' => 'Estatus de Encarcelamiento',
            'american_indian' => 'Tribus / Nativos Americanos',
            default => 'Otro Documento Solicitado',
        };
    }

    /**
     * Human readable status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_upload' => 'Pendiente de Subir',
            'uploaded_to_marketplace' => 'Subido a Healthcare.gov',
            'verified_by_cms' => 'Aprobado por CMS',
            'rejected' => 'Rechazado por CMS',
            default => 'Pendiente',
        };
    }

    /**
     * WhatsApp reminder text for consumer
     */
    public function getWhatsAppReminderMessageAttribute(): string
    {
        $clientName = $this->lead?->person?->name ?? 'Estimado/a cliente';
        $docTitle = $this->title ?: $this->doc_type_label;
        $deadline = $this->deadline_date ? $this->deadline_date->format('d/m/Y') : 'próximamente';
        $days = $this->days_remaining;

        $urgencyPrefix = $days <= 15
            ? "🚨 *URGENTE: RIESGO DE PÉRDIDA DE SUBSIDIO MÉDICO*\n\n"
            : "⚠️ *AVISO DE DOCUMENTACIÓN PENDIENTE (HEALTHCARE.GOV)*\n\n";

        return $urgencyPrefix
            ."Hola *{$clientName}*, le contactamos de su agencia de seguros.\n\n"
            ."El Mercado de Seguros (Healthcare.gov) tiene una inconsistencia abierta (DMI) y requiere su comprobante de:\n"
            ."📄 *{$docTitle}*\n\n"
            ."⏳ *Fecha límite fatal:* {$deadline} (Le quedan *{$days} días*).\n\n"
            ."Si este documento no se carga antes de la fecha límite, *el gobierno federal cancelará su subsidio APTC* y el precio de su seguro aumentará al costo total o se cancelará la póliza.\n\n"
            .'📲 *Por favor envíenos una foto clara o PDF de este documento por este mismo chat de WhatsApp lo antes posible.*';
    }
}

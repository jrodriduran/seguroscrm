<?php

namespace Webkul\Lead\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class LeadSepQualification extends Model
{
    protected $table = 'lead_sep_qualifications';

    protected $fillable = [
        'lead_id',
        'user_id',
        'event_type',
        'event_date',
        'sep_deadline',
        'effective_date',
        'is_eligible',
        'required_documents',
        'verified_documents',
        'notes',
        'verified_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'sep_deadline' => 'date',
        'effective_date' => 'date',
        'required_documents' => 'array',
        'verified_documents' => 'array',
        'is_eligible' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected $appends = [
        'days_remaining',
        'is_expired',
        'event_label',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Map of CMS Qualifying Life Events with labels and required verification documents.
     */
    public static function getEventDefinitions(): array
    {
        return [
            'loss_of_coverage' => [
                'label' => 'Pérdida de Cobertura Médica (Medicaid, Empleo, COBRA)',
                'category' => 'loss',
                'documents' => [
                    'Carta de terminación de empleo indicando fecha fin de cobertura',
                    'Notificación de cancelación o fin de elegibilidad de Medicaid / CHIP',
                    'Carta de finalización de período de cobertura COBRA',
                ],
            ],
            'marriage' => [
                'label' => 'Matrimonio Legal',
                'category' => 'household',
                'documents' => [
                    'Certificado de Matrimonio oficial registrado',
                    'Comprobante de cobertura mínima esencial de al menos un cónyuge en los últimos 60 días',
                ],
            ],
            'birth_adoption' => [
                'label' => 'Nacimiento, Adopción o Cuidado de Crianza',
                'category' => 'household',
                'documents' => [
                    'Certificado de Nacimiento del recién nacido',
                    'Orden judicial de adopción o colocación formal de acogida',
                ],
            ],
            'permanent_move' => [
                'label' => 'Mudanza Permanente a Nuevo Código Postal o Condado',
                'category' => 'move',
                'documents' => [
                    'Factura de servicio público, contrato de arrendamiento o escritura con nueva dirección',
                    'Prueba de haber tenido seguro calificado al menos 1 día en los 60 días previos a la mudanza',
                ],
            ],
            'immigration_status' => [
                'label' => 'Nuevo Estatus Migratorio Elegible (Permiso de Trabajo / Asilo / Residencia)',
                'category' => 'status',
                'documents' => [
                    'Tarjeta de Autorización de Empleo (Formulario I-766 / EAD)',
                    'Notificación de Acción del Servicio de Inmigración (Formulario I-797)',
                    'Tarjeta de Residente Permanente (Green Card I-551)',
                ],
            ],
            'income_change' => [
                'label' => 'Cambio de Ingresos (Paso de Medicaid a ACA o Subsidio Modificado)',
                'category' => 'income',
                'documents' => [
                    'Talones de pago o recibos de salario de los últimos 30 días',
                    'Carta de nuevo empleo o cambio salarial firmada por el empleador',
                    'Declaración de impuestos Formulario 1040 o Formulario W-2 reciente',
                ],
            ],
            'fema_emergency' => [
                'label' => 'Emergencia Declarada por FEMA o Circunstancia Excepcional CMS',
                'category' => 'exceptional',
                'documents' => [
                    'Comprobante de residencia en condado declarado bajo emergencia FEMA',
                    'Declaración formal de impedimento de inscripción durante ventana regular',
                ],
            ],
        ];
    }

    /**
     * Compute days remaining until the 60-day SEP window closes.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (! $this->sep_deadline) {
            return 0;
        }

        $now = Carbon::today();
        if ($now->greaterThan($this->sep_deadline)) {
            return 0;
        }

        return (int) $now->diffInDays($this->sep_deadline, false);
    }

    /**
     * Check if the 60-day window has expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if (! $this->sep_deadline) {
            return true;
        }

        return Carbon::today()->greaterThan($this->sep_deadline);
    }

    /**
     * Human-readable label of the qualifying event.
     */
    public function getEventLabelAttribute(): string
    {
        $defs = self::getEventDefinitions();

        return $defs[$this->event_type]['label'] ?? ucfirst(str_replace('_', ' ', $this->event_type));
    }

    /**
     * Determine system-wide federal enrollment period status (OEP vs SEP).
     */
    public static function getCurrentFederalEnrollmentStatus(?Carbon $date = null): array
    {
        $now = $date ?: Carbon::now();
        $year = $now->year;

        // ACA Open Enrollment Period (OEP): Nov 1 to Jan 15
        $isOep = false;
        $periodName = 'SEP (Período Especial de Inscripción)';
        $description = 'Se requiere un Evento Calificante de Vida (QLE) para inscribir.';
        $deadline = null;

        if ($now->month == 11 || $now->month == 12) {
            $isOep = true;
            $periodName = "OEP {$year}-".($year + 1).' (Inscripción Abierta Nacional)';
            $description = 'Cualquier persona puede inscribirse sin necesidad de evento calificante.';
            $deadline = Carbon::create($year + 1, 1, 15, 23, 59, 59);
        } elseif ($now->month == 1 && $now->day <= 15) {
            $isOep = true;
            $periodName = "OEP {$year} (Inscripción Abierta Nacional)";
            $description = 'Cualquier persona puede inscribirse sin necesidad de evento calificante.';
            $deadline = Carbon::create($year, 1, 15, 23, 59, 59);
        }

        $daysToOepDeadline = $deadline ? (int) ceil(Carbon::now()->diffInDays($deadline, false)) : null;

        return [
            'is_oep' => $isOep,
            'is_sep' => ! $isOep,
            'period_name' => $periodName,
            'description' => $description,
            'deadline' => $deadline?->toDateString(),
            'days_to_deadline' => $daysToOepDeadline,
        ];
    }
}

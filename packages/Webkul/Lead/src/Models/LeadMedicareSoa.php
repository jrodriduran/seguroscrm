<?php

namespace Webkul\Lead\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Contact\Models\PersonProxy;
use Webkul\User\Models\User;

class LeadMedicareSoa extends Model
{
    protected $table = 'lead_medicare_soas';

    protected $fillable = [
        'lead_id',
        'person_id',
        'user_id',
        'token',
        'status',
        'beneficiary_name',
        'beneficiary_phone',
        'beneficiary_address',
        'medicare_number',
        'agent_name',
        'agent_npn',
        'agent_phone',
        'agency_name',
        'discuss_medicare_advantage',
        'discuss_prescription_drug',
        'discuss_medigap',
        'discuss_dental_vision',
        'discuss_hospital_indemnity',
        'signed_at',
        'appointment_eligible_at',
        'appointment_scheduled_at',
        'exception_reason',
        'signature_data',
        'ip_address',
        'user_agent',
        'pdf_path',
        'notes',
    ];

    protected $casts = [
        'discuss_medicare_advantage' => 'boolean',
        'discuss_prescription_drug' => 'boolean',
        'discuss_medigap' => 'boolean',
        'discuss_dental_vision' => 'boolean',
        'discuss_hospital_indemnity' => 'boolean',
        'signed_at' => 'datetime',
        'appointment_eligible_at' => 'datetime',
        'appointment_scheduled_at' => 'datetime',
    ];

    protected $appends = [
        'public_url',
        'hours_remaining_until_eligible',
        'is_eligible_for_appointment',
        'products_list',
        'whatsapp_message',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass(), 'person_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getPublicUrlAttribute(): string
    {
        return route('medicare.soa.portal', $this->token);
    }

    /**
     * Compute hours remaining to fulfill the CMS 48-Hour waiting rule.
     */
    public function getHoursRemainingUntilEligibleAttribute(): int
    {
        if (! $this->appointment_eligible_at || $this->exception_reason !== 'none') {
            return 0;
        }

        $now = Carbon::now();
        if ($now->greaterThanOrEqualTo($this->appointment_eligible_at)) {
            return 0;
        }

        return (int) ceil($now->diffInMinutes($this->appointment_eligible_at, false) / 60);
    }

    /**
     * Is the agent legally permitted under CMS rules to conduct the appointment?
     */
    public function getIsEligibleForAppointmentAttribute(): bool
    {
        if ($this->status !== 'signed') {
            return false;
        }

        // Exception allowed by CMS (walk-in or end of enrollment period)
        if ($this->exception_reason && $this->exception_reason !== 'none') {
            return true;
        }

        if (! $this->appointment_eligible_at) {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo($this->appointment_eligible_at);
    }

    /**
     * Readable list of selected Medicare products.
     */
    public function getProductsListAttribute(): array
    {
        $products = [];
        if ($this->discuss_medicare_advantage) {
            $products[] = 'Medicare Advantage (Part C / HMO & PPO)';
        }
        if ($this->discuss_prescription_drug) {
            $products[] = 'Planes de Medicamentos Recetados (Part D)';
        }
        if ($this->discuss_medigap) {
            $products[] = 'Suplementario de Medicare (Medigap)';
        }
        if ($this->discuss_dental_vision) {
            $products[] = 'Planes Dentales, Visión y Audición';
        }
        if ($this->discuss_hospital_indemnity) {
            $products[] = 'Indemnización Hospitalaria / Beneficios Suplementarios';
        }

        return $products;
    }

    /**
     * Official CMS TPMO Disclaimer.
     */
    public static function getTpmoDisclaimer(): string
    {
        return 'No ofrecemos todos los planes disponibles en su área. Cualquier información que le proporcionemos se limita a los planes que sí ofrecemos en su área de residencia. Comuníquese con Medicare.gov o llame al 1-800-MEDICARE las 24 horas del día, los 7 días de la semana para obtener información sobre todas sus opciones.';
    }

    /**
     * Preformatted WhatsApp message for beneficiary to sign SOA.
     */
    public function getWhatsappMessageAttribute(): string
    {
        $beneficiary = $this->beneficiary_name ?: 'Estimado/a beneficiario/a';
        $agent = $this->agent_name;
        $url = $this->public_url;

        return "Hola *{$beneficiary}*, le saluda su agente certificado de Medicare, *{$agent}*.\n\n"
            ."Por regulación oficial federal de CMS (Medicare), antes de reunirnos para revisar sus opciones de cobertura, es requisito completar el formulario digital *Scope of Appointment (SOA)* donde usted autoriza qué tipos de planes desea evaluar.\n\n"
            ."📝 *Complete su firma en 1 minuto desde su celular aquí:*\n"
            ."👉 {$url}\n\n"
            ."*Importante:* La ley de Medicare exige firmar este documento con al menos 48 horas de anticipación a nuestra cita para que su cita sea válida.\n\n"
            .'¡Gracias por su confianza!';
    }
}

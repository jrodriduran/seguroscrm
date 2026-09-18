<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Contracts\LeadConsent as LeadConsentContract;
use Webkul\User\Models\UserProxy;

class LeadConsent extends Model implements LeadConsentContract
{
    protected $table = 'lead_consents';

    protected $fillable = [
        'lead_id',
        'person_id',
        'user_id',
        'token',
        'status',
        'client_name',
        'client_phone',
        'client_email',
        'agent_name',
        'agent_npn',
        'agency_name',
        'consent_text',
        'signature_data',
        'signed_at',
        'ip_address',
        'user_agent',
        'pdf_path',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    /**
     * Standard CMS Federal Consent Text template (45 CFR § 155.220)
     */
    public static function getDefaultConsentText(string $clientName, string $agentName, string $agentNpn, string $agencyName = 'Seguros CRM Agency'): string
    {
        return "AUTORIZACIÓN DEL CONSUMIDOR PARA ASISTENCIA CON EL MERCADO DE SEGUROS DE SALUD (CMS 45 CFR § 155.220)\n\n"
            ."Yo, {$clientName}, por medio de la presente autorizo a {$agentName} (NPN: {$agentNpn}) y a su agencia afiliada {$agencyName}, para actuar como mi Agente / Corredor de Seguros certificado ante el Mercado de Seguros de Salud (Healthcare.gov y/o intercambios estatales).\n\n"
            ."1. ALCANCE DE LA AUTORIZACIÓN: Autorizo al agente a:\n"
            ."   a) Buscar y verificar información existente de mi solicitud y elegibilidad en el Mercado.\n"
            ."   b) Completar y enviar una solicitud de cobertura médica calificada (QHP) y créditos fiscales de prima (APTC / Cost-Sharing Reductions) para mí y los dependientes de mi núcleo familiar.\n"
            ."   c) Asistirme con la selección del plan médico, inscripción continua y resolución de requerimientos de documentación (DMI).\n"
            ."   d) Notificar cambios relevantes en mis ingresos familiares, dirección, número de dependientes o estatus migratorio durante el año de cobertura.\n\n"
            ."2. VERACIDAD DE LA INFORMACIÓN: Confirmo que la información que proporciono sobre mis ingresos proyectados, composición del hogar y estatus legal es exacta y verídica a mi leal saber y entender.\n\n"
            ."3. CONSENTIMIENTO INFORMADO: Reconozco que no he sido obligado a adquirir ningún producto adicional y que esta autorización no transfiere el pago de primas ni modifica mi responsabilidad tributaria con el IRS.\n\n"
            .'4. VIGENCIA Y REVOCACIÓN: Esta autorización es válida por un (1) año calendario a partir de la fecha de firma electrónica, y puede ser revocada por mí en cualquier momento mediante notificación escrita a mi agente.';
    }

    /**
     * Lead relation
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    /**
     * Person relation
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass(), 'person_id');
    }

    /**
     * User (Agent) relation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'user_id');
    }

    /**
     * Public signing URL
     */
    public function getPublicUrlAttribute(): string
    {
        return url('/consent/'.$this->token);
    }

    /**
     * WhatsApp message generator
     */
    public function getWhatsAppMessageAttribute(): string
    {
        $name = $this->client_name ?: 'Estimado/a cliente';
        $agent = $this->agent_name ?: 'Su Agente de Seguros';
        $url = $this->public_url;

        return "📋 *Autorización de Asistencia ACA / Healthcare.gov*\n\n"
            ."Hola *{$name}*, le saluda *{$agent}*.\n\n"
            ."Conforme a la normativa federal de los Centros de Servicios de Medicare y Medicaid (CMS), requerimos su autorización electrónica para representarlo formalmente y gestionar su solicitud de subsidio y seguro médico.\n\n"
            ."✍️ *Por favor firme su consentimiento digital aquí:*\n"
            ."👉 {$url}\n\n"
            .'_Es un proceso rápido y 100% seguro que solo le tomará 30 segundos desde su celular._';
    }
}

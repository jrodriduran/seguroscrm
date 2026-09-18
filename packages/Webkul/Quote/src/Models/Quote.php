<?php

namespace Webkul\Quote\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Traits\CustomAttribute;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\LeadProxy;
use Webkul\Quote\Contracts\Quote as QuoteContract;
use Webkul\User\Models\UserProxy;

class Quote extends Model implements QuoteContract
{
    use CustomAttribute;

    protected $table = 'quotes';

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'expired_at' => 'datetime',
        'gross_premium' => 'float',
        'aptc_subsidy' => 'float',
        'net_premium' => 'float',
        'deductible' => 'float',
        'out_of_pocket_max' => 'float',
        'copay_primary_care' => 'float',
        'copay_specialist' => 'float',
        'copay_generic_drugs' => 'float',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subject',
        'description',
        'billing_address',
        'shipping_address',
        'discount_percent',
        'discount_amount',
        'tax_amount',
        'adjustment_amount',
        'sub_total',
        'grand_total',
        'expired_at',
        'user_id',
        'person_id',
        'carrier_name',
        'plan_name',
        'metal_tier',
        'gross_premium',
        'aptc_subsidy',
        'net_premium',
        'deductible',
        'out_of_pocket_max',
        'network_type',
        'copay_primary_care',
        'copay_specialist',
        'copay_generic_drugs',
        'quote_status',
    ];

    /**
     * Generate preformatted WhatsApp / SMS proposal message.
     */
    public function getWhatsAppSummary(): string
    {
        $clientName = $this->person?->name ?? 'Estimado(a) Cliente';
        $carrier = $this->carrier_name ?? 'Plan de Salud';
        $plan = $this->plan_name ?? $this->subject;
        $tier = $this->metal_tier ? strtoupper($this->metal_tier) : '';
        $gross = number_format((float) ($this->gross_premium ?? $this->grand_total ?? 0), 2);
        $subsidy = number_format((float) ($this->aptc_subsidy ?? 0), 2);
        $net = number_format((float) ($this->net_premium ?? $this->grand_total ?? 0), 2);
        $deductible = number_format((float) ($this->deductible ?? 0), 2);
        $oopm = number_format((float) ($this->out_of_pocket_max ?? 0), 2);
        $netType = $this->network_type ? "({$this->network_type})" : '';

        $copayDoc = $this->copay_primary_care !== null ? '$'.number_format((float) $this->copay_primary_care, 2) : 'N/A';
        $copaySpec = $this->copay_specialist !== null ? '$'.number_format((float) $this->copay_specialist, 2) : 'N/A';
        $copayRx = $this->copay_generic_drugs !== null ? '$'.number_format((float) $this->copay_generic_drugs, 2) : 'N/A';

        return "🩺 *Propuesta de Cobertura Médica (ACA)*\n\n"
            ."👤 *Cliente:* {$clientName}\n"
            ."🏢 *Aseguradora:* {$carrier}\n"
            ."📋 *Plan:* {$plan} {$tier} {$netType}\n\n"
            ."💵 *Prima Original:* \${$gross}/mes\n"
            ."🏛️ *Subsidio Federal (APTC):* -\${$subsidy}/mes\n"
            ."⭐ *TU PAGO MENSUAL:* *\${$net}/mes*\n\n"
            ."🛡️ *Deducible:* \${$deductible}\n"
            ."🏥 *Máximo de Bolsillo (OOPM):* \${$oopm}\n"
            ."👨‍⚕️ *Copago Médico Primario:* {$copayDoc}\n"
            ."🩺 *Copago Especialista:* {$copaySpec}\n"
            ."💊 *Copago Medicamentos Genéricos:* {$copayRx}\n\n"
            .'_¿Deseas que procedamos hoy mismo con la emisión de esta póliza?_';
    }

    /**
     * Get the quote items record associated with the quote.
     */
    public function items()
    {
        return $this->hasMany(QuoteItemProxy::modelClass());
    }

    /**
     * Get the user that owns the quote.
     */
    public function user()
    {
        return $this->belongsTo(UserProxy::modelClass());
    }

    /**
     * Get the person that owns the quote.
     */
    public function person()
    {
        return $this->belongsTo(PersonProxy::modelClass());
    }

    /**
     * The leads that belong to the quote.
     */
    public function leads()
    {
        return $this->belongsToMany(LeadProxy::modelClass(), 'lead_quotes');
    }
}

<?php

namespace Webkul\Quote\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Models\LeadProxy;
use Webkul\User\Models\User;

class HealthPlanProposal extends Model
{
    protected $table = 'health_plan_proposals';

    protected $fillable = [
        'lead_id',
        'user_id',
        'token',
        'title',
        'quote_ids',
        'selected_quote_id',
        'status',
        'client_notes',
        'viewed_at',
        'accepted_at',
        'client_ip',
    ];

    protected $casts = [
        'quote_ids' => 'array',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected $appends = [
        'public_url',
        'whatsapp_message',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function selectedQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'selected_quote_id');
    }

    /**
     * Get the Quote models included in this comparison proposal.
     */
    public function getQuotes(): Collection
    {
        if (empty($this->quote_ids)) {
            return new Collection;
        }

        return Quote::whereIn('id', $this->quote_ids)->get();
    }

    public function getPublicUrlAttribute(): string
    {
        return route('proposal.portal.show', $this->token);
    }

    /**
     * Format a compelling comparison message for WhatsApp.
     */
    public function getWhatsappMessageAttribute(): string
    {
        $quotes = $this->getQuotes();
        $lead = $this->lead;
        $clientName = $lead?->person?->name ?: 'Estimado/a';
        $agentName = $this->user?->name ?: 'Su Agente de Seguros';

        $text = "Hola *{$clientName}*, le saluda *{$agentName}*.\n\n"
            ."📊 He preparado una *Comparativa Personalizada de Planes de Salud* diseñada a la medida de su presupuesto y necesidades médicas:\n\n";

        $i = 1;
        foreach ($quotes as $quote) {
            $carrier = $quote->carrier_name ?: 'Aseguradora';
            $plan = $quote->plan_name ?: 'Plan de Salud';
            $tier = strtoupper($quote->metal_tier ?: 'Silver');
            $net = number_format((float) ($quote->net_premium ?? $quote->grand_total), 2);
            $deductible = number_format((float) ($quote->deductible ?? 0), 2);
            $pcp = number_format((float) ($quote->copay_primary_care ?? 0), 2);
            $spec = number_format((float) ($quote->copay_specialist ?? 0), 2);
            $rx = number_format((float) ($quote->copay_generic_drugs ?? 0), 2);

            $text .= "*Opción {$i}: {$carrier} - {$plan}* [{$tier}]\n"
                ."   💵 *Costo Mensual para usted: \${$net} / mes*\n"
                ."   🛡️ Deducible: \${$deductible}\n"
                ."   🩺 Médico Primario: \${$pcp} | Especialista: \${$spec}\n"
                ."   💊 Medicamentos Genéricos: \${$rx}\n\n";
            $i++;
        }

        $text .= "👉 *Vea la comparativa completa lado a lado y seleccione su plan preferido aquí:*\n"
            ."{$this->public_url}\n\n"
            .'Si tiene cualquier pregunta sobre redes de doctores u hospitales, avíseme de inmediato. ¡Estoy para servirle!';

        return $text;
    }
}

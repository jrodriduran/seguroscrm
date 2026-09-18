<?php

namespace Webkul\Lead\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Quote\Models\Quote;
use Webkul\User\Models\User;

class InsurancePolicy extends Model
{
    protected $table = 'insurance_policies';

    protected $fillable = [
        'policy_number',
        'portal_token',
        'member_id',
        'group_number',
        'lead_id',
        'person_id',
        'user_id',
        'quote_id',
        'carrier_name',
        'plan_name',
        'pcp_name',
        'metal_tier',
        'network_type',
        'market_type',
        'gross_premium',
        'aptc_subsidy',
        'net_premium',
        'effective_date',
        'renewal_date',
        'paid_to_date',
        'status',
        'grace_period_start_date',
        'grace_period_days',
        'members_count',
        'notes',
    ];

    protected $casts = [
        'gross_premium' => 'float',
        'aptc_subsidy' => 'float',
        'net_premium' => 'float',
        'effective_date' => 'date',
        'renewal_date' => 'date',
        'paid_to_date' => 'date',
        'grace_period_start_date' => 'date',
        'grace_period_days' => 'integer',
        'members_count' => 'integer',
    ];

    protected $appends = [
        'grace_status_badge',
        'days_overdue',
        'whatsapp_payment_reminder',
        'portal_url',
    ];

    protected static function booted(): void
    {
        static::creating(function ($policy) {
            if (empty($policy->portal_token)) {
                $policy->portal_token = \Illuminate\Support\Str::random(40);
            }
            if (empty($policy->member_id)) {
                $policy->member_id = 'MBR-'.rand(10000000, 99999999);
            }
            if (empty($policy->group_number)) {
                $policy->group_number = 'GRP-'.substr(strtoupper($policy->carrier_name), 0, 3).'-'.rand(1000, 9999);
            }
        });
    }

    public function getPortalUrlAttribute(): string
    {
        return route('insured.portal.show', $this->portal_token ?: 'token');
    }

    /**
     * Map carrier support phone numbers and portals.
     */
    public function getCarrierSupportContacts(): array
    {
        $carrier = strtolower($this->carrier_name ?: '');

        return match (true) {
            str_contains($carrier, 'florida blue') || str_contains($carrier, 'bcbs') => [
                'phone' => '1-800-352-2583',
                'nurse_line' => '1-877-789-2583',
                'website' => 'https://www.floridablue.com',
                'portal_app' => 'Florida Blue Member App',
            ],
            str_contains($carrier, 'ambetter') || str_contains($carrier, 'sunshine') => [
                'phone' => '1-877-687-1180',
                'nurse_line' => '1-877-687-1180 (Opción 2)',
                'website' => 'https://ambetter.sunshinehealth.com',
                'portal_app' => 'Ambetter Health App',
            ],
            str_contains($carrier, 'oscar') => [
                'phone' => '1-855-672-2788',
                'nurse_line' => '1-855-672-2788',
                'website' => 'https://www.hioscar.com',
                'portal_app' => 'Oscar Health App (24/7 Virtual Urgent Care)',
            ],
            str_contains($carrier, 'molina') => [
                'phone' => '1-888-562-5442',
                'nurse_line' => '1-888-275-8750',
                'website' => 'https://www.molinahealthcare.com',
                'portal_app' => 'My Molina App',
            ],
            str_contains($carrier, 'united') || str_contains($carrier, 'uhc') => [
                'phone' => '1-800-985-7719',
                'nurse_line' => '1-800-846-4678',
                'website' => 'https://www.myuhc.com',
                'portal_app' => 'UnitedHealthcare App',
            ],
            str_contains($carrier, 'aetna') => [
                'phone' => '1-800-872-3862',
                'nurse_line' => '1-800-556-1555',
                'website' => 'https://www.aetna.com',
                'portal_app' => 'Aetna Health App',
            ],
            default => [
                'phone' => '1-800-318-2596 (Healthcare.gov)',
                'nurse_line' => 'Consulte el reverso de su tarjeta',
                'website' => 'https://www.healthcare.gov',
                'portal_app' => 'Portal de la Aseguradora',
            ],
        };
    }

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

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(InsuranceCommission::class, 'quote_id', 'quote_id');
    }

    /**
     * Compute days overdue past the paid-to date.
     */
    public function getDaysOverdueAttribute(): int
    {
        if (! $this->paid_to_date) {
            return 0;
        }

        $now = Carbon::today();
        if ($now->lessThanOrEqualTo($this->paid_to_date)) {
            return 0;
        }

        return (int) $this->paid_to_date->diffInDays($now, false);
    }

    /**
     * Visual status badge identifier for frontend styling.
     */
    public function getGraceStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'active' => [
                'label' => 'Al Día (Vigente)',
                'color' => 'emerald',
                'bg' => 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-300',
                'icon' => '✓',
            ],
            'grace_period_1' => [
                'label' => "Gracia Mes 1 ({$this->grace_period_days}d)",
                'color' => 'amber',
                'bg' => 'bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border-amber-300',
                'icon' => '⚠️',
            ],
            'grace_period_2_3' => [
                'label' => "Gracia Crítica ({$this->grace_period_days}d)",
                'color' => 'rose',
                'bg' => 'bg-rose-100 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border-rose-300 animate-pulse',
                'icon' => '🚨',
            ],
            'cancelled' => [
                'label' => 'Cancelada / Lapsada',
                'color' => 'slate',
                'bg' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-300',
                'icon' => '✕',
            ],
            'renewed' => [
                'label' => 'Renovada OEP',
                'color' => 'blue',
                'bg' => 'bg-blue-100 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 border-blue-300',
                'icon' => '🔄',
            ],
            default => [
                'label' => ucfirst($this->status),
                'color' => 'slate',
                'bg' => 'bg-slate-100 text-slate-700 border-slate-300',
                'icon' => '•',
            ],
        };
    }

    /**
     * Preformatted WhatsApp message alerting the policyholder about missed payment.
     */
    public function getWhatsappPaymentReminderAttribute(): string
    {
        $name = $this->person?->name ?: ($this->lead?->person?->name ?: 'Estimado/a cliente');
        $carrier = $this->carrier_name;
        $policy = $this->policy_number;
        $premium = number_format($this->net_premium, 2);
        $agent = $this->user?->name ?: 'Su Agente de Seguros';

        return "Hola *{$name}*, le saluda *{$agent}*.\n\n"
            ."⚠️ Nos comunicamos para informarle que su póliza de salud *{$carrier}* (N° *{$policy}*) registra un pago mensual pendiente por un monto de *\${$premium}*.\n\n"
            ."📌 *Importante:* La ley de seguros contempla un período de gracia limitado. Para evitar que la aseguradora suspenda sus reclamos médicos o cancele su cobertura médica, le recomendamos realizar el pago a la brevedad.\n\n"
            ."Puede pagar directamente llamando al número al reverso de su tarjeta o en el portal en línea de {$carrier}.\n\n"
            .'Si ya realizó este pago recientemente, por favor confírmeme para actualizar su expediente. ¡Estamos para apoyarle!';
    }

    /**
     * Create or update policy from a bound/converted quote.
     */
    public static function createOrUpdateFromQuote(Quote $quote, ?string $customPolicyNumber = null): self
    {
        $lead = $quote->leads()->first();
        $carrier = $quote->carrier_name ?: 'Salud';
        $policyNum = $customPolicyNumber ?: ('POL-'.strtoupper(substr($carrier, 0, 3)).'-'.str_pad((string) $quote->id, 6, '0', STR_PAD_LEFT));

        $members = max(1, (int) ($quote->household_members_count ?? 1));
        $gross = (float) ($quote->gross_premium ?: $quote->sub_total ?: 0);
        $subsidy = (float) ($quote->aptc_subsidy ?: $quote->discount_amount ?: 0);
        $net = (float) ($quote->net_premium ?: $quote->grand_total ?: 0);

        $now = Carbon::today();
        // Effective date defaults to 1st of next month
        $effective = $quote->created_at ? Carbon::parse($quote->created_at)->addMonth()->startOfMonth() : $now->copy()->addMonth()->startOfMonth();
        // Renewal is 1 year later (Dec 31 of current plan year)
        $renewal = Carbon::create($effective->year, 12, 31);
        // Paid to date defaults to 1 month after effective
        $paidTo = $effective->copy()->endOfMonth();

        return self::updateOrCreate(
            ['quote_id' => $quote->id],
            [
                'policy_number' => $policyNum,
                'lead_id' => $lead?->id,
                'person_id' => $quote->person_id ?: $lead?->person_id,
                'user_id' => $quote->user_id ?: ($lead?->user_id ?: 1),
                'carrier_name' => $carrier,
                'plan_name' => $quote->plan_name ?: $quote->subject,
                'metal_tier' => $quote->metal_tier ?: 'silver',
                'network_type' => $quote->network_type ?: 'HMO',
                'market_type' => 'aca_individual',
                'gross_premium' => $gross,
                'aptc_subsidy' => $subsidy,
                'net_premium' => $net,
                'effective_date' => $effective->toDateString(),
                'renewal_date' => $renewal->toDateString(),
                'paid_to_date' => $paidTo->toDateString(),
                'status' => 'active',
                'grace_period_days' => 0,
                'members_count' => $members,
            ]
        );
    }

    /**
     * Scope: Active in-force policies.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Policies currently in any grace period.
     */
    public function scopeInGracePeriod($query)
    {
        return $query->whereIn('status', ['grace_period_1', 'grace_period_2_3']);
    }

    /**
     * Scope: Policies in critical grace period (Month 2-3 / Day 31-90).
     */
    public function scopeCriticalGracePeriod($query)
    {
        return $query->where('status', 'grace_period_2_3');
    }
}

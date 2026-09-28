<?php

namespace Webkul\Lead\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Traits\BelongsToAgency;
use Webkul\Quote\Models\Quote;
use Webkul\User\Models\User;

class InsurancePolicy extends Model
{
    use BelongsToAgency;

    protected $table = 'insurance_policies';

    protected $fillable = [
        'policy_number',
        'portal_token',
        'member_id',
        'group_number',
        'lead_id',
        'person_id',
        'user_id',
        'agency_id',
        'quote_id',
        'carrier_name',
        'plan_name',
        'pcp_name',
        'metal_tier',
        'network_type',
        'market_type',
        'plan_year',
        'gross_premium',
        'aptc_subsidy',
        'net_premium',
        'deductible',
        'max_out_of_pocket',
        'effective_date',
        'renewal_date',
        'paid_to_date',
        'status',
        'binder_payment_status',
        'binder_amount',
        'binder_due_date',
        'binder_paid_at',
        'binder_confirmation_number',
        'binder_payment_method',
        'effectuation_date',
        'effectuation_source',
        'effectuation_verified_by',
        'prior_policy_id',
        'renewal_type',
        'renewal_cohort_year',
        'renewal_notes',
        'missing_commission_flag',
        'missing_commission_detected_at',
        'missing_commission_amount',
        'missing_commission_days',
        'missing_commission_notes',
        'grace_period_start_date',
        'grace_period_days',
        'members_count',
        'notes',
    ];

    protected $casts = [
        'missing_commission_flag' => 'boolean',
        'missing_commission_detected_at' => 'datetime',
        'missing_commission_amount' => 'float',
        'missing_commission_days' => 'integer',
        'plan_year' => 'integer',
        'gross_premium' => 'float',
        'aptc_subsidy' => 'float',
        'net_premium' => 'float',
        'deductible' => 'float',
        'max_out_of_pocket' => 'float',
        'binder_amount' => 'float',
        'renewal_cohort_year' => 'integer',
        'effective_date' => 'date',
        'renewal_date' => 'date',
        'paid_to_date' => 'date',
        'binder_due_date' => 'date',
        'binder_paid_at' => 'datetime',
        'effectuation_date' => 'date',
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
                $policy->portal_token = Str::random(40);
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
                'label' => trans('admin::insurance.policies.status_active'),
                'color' => 'emerald',
                'bg' => 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-300',
                'icon' => '✓',
            ],
            'binder_pending' => [
                'label' => trans('admin::insurance.policies.status_binder_pending'),
                'color' => 'amber',
                'bg' => 'bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border-amber-300',
                'icon' => '💳',
            ],
            'dmi_pending' => [
                'label' => trans('admin::insurance.policies.status_dmi_pending'),
                'color' => 'indigo',
                'bg' => 'bg-indigo-100 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-300 border-indigo-300',
                'icon' => '📄',
            ],
            'grace_period_1' => [
                'label' => trans('admin::insurance.policies.status_grace_1', ['days' => $this->grace_period_days]),
                'color' => 'amber',
                'bg' => 'bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border-amber-300',
                'icon' => '⚠️',
            ],
            'grace_period_2_3' => [
                'label' => trans('admin::insurance.policies.status_grace_critical', ['days' => $this->grace_period_days]),
                'color' => 'rose',
                'bg' => 'bg-rose-100 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border-rose-300 animate-pulse',
                'icon' => '🚨',
            ],
            'cancelled' => [
                'label' => trans('admin::insurance.policies.status_cancelled'),
                'color' => 'slate',
                'bg' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-300',
                'icon' => '✕',
            ],
            'renewed' => [
                'label' => trans('admin::insurance.policies.status_renewed'),
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
     * Coverage status audit histories relation.
     */
    public function coverageHistories(): HasMany
    {
        return $this->hasMany(PolicyCoverageStatusHistoryProxy::modelClass(), 'policy_id')->orderBy('id', 'desc');
    }

    /**
     * Service cases / post-sale tickets associated with the policy.
     */
    public function serviceCases(): HasMany
    {
        return $this->hasMany(PolicyServiceCaseProxy::modelClass(), 'policy_id')->orderBy('id', 'desc');
    }

    /**
     * Prior year policy link (renewal chain).
     */
    public function priorPolicy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'prior_policy_id');
    }

    /**
     * Renewed policies for subsequent years.
     */
    public function renewedPolicies(): HasMany
    {
        return $this->hasMany(self::class, 'prior_policy_id');
    }

    /**
     * Compute year-over-year comparison against prior policy or a custom target.
     */
    public function getYearOverYearComparison(?InsurancePolicy $comparisonTarget = null): ?array
    {
        $prior = $comparisonTarget ?: $this->priorPolicy;

        if (! $prior) {
            return null;
        }

        $grossDiff = round((float) $this->gross_premium - (float) $prior->gross_premium, 2);
        $subsidyDiff = round((float) $this->aptc_subsidy - (float) $prior->aptc_subsidy, 2);
        $netDiff = round((float) $this->net_premium - (float) $prior->net_premium, 2);
        $deductibleDiff = round((float) ($this->deductible ?? 0) - (float) ($prior->deductible ?? 0), 2);
        $moopDiff = round((float) ($this->max_out_of_pocket ?? 0) - (float) ($prior->max_out_of_pocket ?? 0), 2);

        $isCarrierChanged = strcasecmp((string) $this->carrier_name, (string) $prior->carrier_name) !== 0;
        $isPlanChanged = strcasecmp((string) $this->plan_name, (string) $prior->plan_name) !== 0;

        return [
            'prior_policy' => [
                'id' => $prior->id,
                'policy_number' => $prior->policy_number,
                'carrier_name' => $prior->carrier_name,
                'plan_name' => $prior->plan_name,
                'metal_tier' => $prior->metal_tier,
                'plan_year' => $prior->plan_year ?: ($prior->effective_date ? Carbon::parse($prior->effective_date)->year : null),
                'gross_premium' => (float) $prior->gross_premium,
                'aptc_subsidy' => (float) $prior->aptc_subsidy,
                'net_premium' => (float) $prior->net_premium,
                'deductible' => (float) ($prior->deductible ?? 0),
                'max_out_of_pocket' => (float) ($prior->max_out_of_pocket ?? 0),
                'status' => $prior->status,
            ],
            'current_policy' => [
                'id' => $this->id,
                'policy_number' => $this->policy_number,
                'carrier_name' => $this->carrier_name,
                'plan_name' => $this->plan_name,
                'metal_tier' => $this->metal_tier,
                'plan_year' => $this->plan_year ?: ($this->effective_date ? Carbon::parse($this->effective_date)->year : null),
                'gross_premium' => (float) $this->gross_premium,
                'aptc_subsidy' => (float) $this->aptc_subsidy,
                'net_premium' => (float) $this->net_premium,
                'deductible' => (float) ($this->deductible ?? 0),
                'max_out_of_pocket' => (float) ($this->max_out_of_pocket ?? 0),
                'status' => $this->status,
                'renewal_type' => $this->renewal_type,
            ],
            'variance' => [
                'gross_premium_diff' => $grossDiff,
                'aptc_subsidy_diff' => $subsidyDiff,
                'net_premium_diff' => $netDiff,
                'deductible_diff' => $deductibleDiff,
                'moop_diff' => $moopDiff,
                'is_carrier_changed' => $isCarrierChanged,
                'is_plan_changed' => $isPlanChanged,
                'is_net_savings' => $netDiff < 0,
                'is_net_increase' => $netDiff > 0,
                'subsidy_loss_warning' => $subsidyDiff < -50 || ($prior->net_premium == 0 && $this->net_premium > 0),
            ],
        ];
    }

    /**
     * User who verified effectuation.
     */
    public function effectuationVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'effectuation_verified_by');
    }

    /**
     * Record coverage status transition with source and verifier audit trail.
     */
    public function recordCoverageTransition(
        string $toStatus,
        ?string $binderStatus = null,
        ?string $reason = null,
        string $source = 'agent_manual',
        ?int $userId = null
    ): PolicyCoverageStatusHistory {
        $fromStatus = $this->getOriginal('status') ?: ($this->status ?: 'application_submitted');

        return PolicyCoverageStatusHistory::create([
            'policy_id' => $this->id,
            'lead_id' => $this->lead_id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'binder_status' => $binderStatus ?: $this->binder_payment_status,
            'source' => $source,
            'reason' => $reason,
            'verified_by_user_id' => $userId ?: (auth()->guard('user')->id() ?: $this->user_id),
            'verified_at' => now(),
        ]);
    }

    /**
     * Record the initial binder payment to effectuate coverage.
     */
    public function recordBinderPayment(
        string $confirmationNumber,
        string $method = 'carrier_portal',
        ?string $paidAt = null,
        ?string $paidToDate = null,
        ?int $userId = null,
        ?string $notes = null
    ): self {
        $paidTimestamp = $paidAt ? Carbon::parse($paidAt) : now();
        $newPaidTo = $paidToDate ? Carbon::parse($paidToDate) : ($this->effective_date ? Carbon::parse($this->effective_date)->endOfMonth() : now()->endOfMonth());

        $this->update([
            'status' => 'active',
            'binder_payment_status' => 'paid',
            'binder_paid_at' => $paidTimestamp,
            'binder_confirmation_number' => $confirmationNumber,
            'binder_payment_method' => $method,
            'effectuation_date' => $paidTimestamp->toDateString(),
            'effectuation_source' => 'agent_verified',
            'effectuation_verified_by' => $userId ?: auth()->guard('user')->id(),
            'paid_to_date' => $newPaidTo->toDateString(),
            'grace_period_days' => 0,
            'grace_period_start_date' => null,
            'notes' => trim(($this->notes ?: '')."\n[".$paidTimestamp->toDateString()."] Primer pago (Binder) confirmado: #{$confirmationNumber} vía {$method}.".($notes ? " Nota: {$notes}" : '')),
        ]);

        $this->recordCoverageTransition(
            'active',
            'paid',
            "Primer pago (Binder) confirmado con éxito. Confirmación #{$confirmationNumber}. Cobertura médica en vigor.",
            'agent_verified',
            $userId
        );

        return $this;
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

        // Coverage Effectuation rules
        if ($net <= 0) {
            $status = 'active';
            $binderStatus = 'waived_zero_premium';
            $effectuationDate = $effective->toDateString();
            $effectuationSource = 'zero_dollar_subsidy';
            $reason = 'Cobertura emitida con subsidio APTC 100% (Prima neta $0). Cobertura efectuada automáticamente sin pago inicial requerido.';
        } else {
            $status = 'binder_pending';
            $binderStatus = 'pending';
            $effectuationDate = null;
            $effectuationSource = 'pending_binder_payment';
            $reason = "Solicitud emitida. Cobertura condicionada al pago inicial (Binder Payment) de \${$net} antes del {$effective->format('d/m/Y')}.";
        }

        $policy = self::updateOrCreate(
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
                'status' => $status,
                'binder_payment_status' => $binderStatus,
                'binder_amount' => $net,
                'binder_due_date' => $effective->toDateString(),
                'effectuation_date' => $effectuationDate,
                'effectuation_source' => $effectuationSource,
                'grace_period_days' => 0,
                'members_count' => $members,
            ]
        );

        $policy->recordCoverageTransition(
            $status,
            $binderStatus,
            $reason,
            $effectuationSource,
            $quote->user_id
        );

        return $policy;
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

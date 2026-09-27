<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\AgentCommissionLedgerTransaction as AgentCommissionLedgerTransactionContract;
use Webkul\User\Models\User;

class AgentCommissionLedgerTransaction extends Model implements AgentCommissionLedgerTransactionContract
{
    protected $table = 'agent_commission_ledger_transactions';

    protected $fillable = [
        'user_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'period_month',
        'carrier_name',
        'policy_id',
        'policy_number',
        'statement_id',
        'statement_item_id',
        'reference_code',
        'description',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
    ];

    protected $appends = [
        'type_label',
        'is_credit',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicyProxy::modelClass(), 'policy_id');
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(CarrierStatement::class, 'statement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->amount > 0;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->transaction_type) {
            'commission_credit' => trans('admin::insurance.ledger.type_commission_credit'),
            'override_credit' => trans('admin::insurance.ledger.type_override_credit'),
            'clawback_debit' => trans('admin::insurance.ledger.type_clawback_debit'),
            'payout_disbursement' => trans('admin::insurance.ledger.type_payout_disbursement'),
            'adjustment' => trans('admin::insurance.ledger.type_adjustment'),
            'reserve_withhold' => trans('admin::insurance.ledger.type_reserve_withhold'),
            'reserve_release' => trans('admin::insurance.ledger.type_reserve_release'),
            default => ucfirst(str_replace('_', ' ', $this->transaction_type ?: 'transaction')),
        };
    }
}

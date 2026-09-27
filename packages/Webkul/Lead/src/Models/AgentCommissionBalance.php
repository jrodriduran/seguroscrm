<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Lead\Contracts\AgentCommissionBalance as AgentCommissionBalanceContract;
use Webkul\User\Models\User;

class AgentCommissionBalance extends Model implements AgentCommissionBalanceContract
{
    protected $table = 'agent_commission_balances';

    protected $fillable = [
        'user_id',
        'total_earned',
        'total_clawbacks',
        'total_paid_out',
        'current_balance',
        'held_reserve',
        'status',
        'last_payout_at',
    ];

    protected $casts = [
        'total_earned' => 'float',
        'total_clawbacks' => 'float',
        'total_paid_out' => 'float',
        'current_balance' => 'float',
        'held_reserve' => 'float',
        'last_payout_at' => 'datetime',
    ];

    protected $appends = [
        'is_negative',
        'net_available_balance',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AgentCommissionLedgerTransactionProxy::modelClass(), 'user_id', 'user_id')
            ->orderBy('id', 'desc');
    }

    public function getIsNegativeAttribute(): bool
    {
        return $this->current_balance < 0;
    }

    public function getNetAvailableBalanceAttribute(): float
    {
        return max(0, round($this->current_balance - $this->held_reserve, 2));
    }
}

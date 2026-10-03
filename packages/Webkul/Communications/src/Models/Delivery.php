<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class Delivery extends Model
{
    public const ITEMS = ['card', 'gift_card', 'kit', 'flowers', 'other'];

    /**
     * Flow: requested → approved → purchased → sent → delivered (or cancelled).
     */
    public const STATUSES = ['requested', 'approved', 'purchased', 'sent', 'delivered', 'cancelled'];

    protected $table = 'communication_deliveries';

    protected $fillable = [
        'agency_id', 'person_id', 'lead_id', 'enrollment_id', 'follow_up_id', 'item', 'note', 'address', 'cost',
        'status', 'tracking', 'requested_by', 'assigned_to', 'approved_by', 'approved_at', 'sent_at', 'delivered_at',
    ];

    protected $casts = [
        'address' => 'array',
        'cost' => 'decimal:2',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Gift cards and anything with a cost above the agency limit need the owner.
     */
    public static function needsApproval(string $item, $cost, float $limit): bool
    {
        return $item === 'gift_card' || ((float) $cost > 0 && (float) $cost >= $limit);
    }

    public function addressLine(): ?string
    {
        $a = $this->address ?? [];

        if (! array_filter($a)) {
            return null;
        }

        return trim(implode(', ', array_filter([$a['address'] ?? null, $a['city'] ?? null, trim(($a['state'] ?? '').' '.($a['postcode'] ?? ''))])));
    }
}

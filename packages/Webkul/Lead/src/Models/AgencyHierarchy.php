<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\User\Models\User;

class AgencyHierarchy extends Model
{
    protected $table = 'agency_hierarchies';

    protected $fillable = [
        'user_id',
        'parent_user_id',
        'agency_tier',
        'sub_agency_name',
        'npn_number',
        'contract_level_percentage',
        'override_pmpm',
        'override_percentage',
        'status',
        'notes',
    ];

    protected $casts = [
        'contract_level_percentage' => 'float',
        'override_pmpm' => 'float',
        'override_percentage' => 'float',
    ];

    protected $appends = [
        'tier_label',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function downlines(): HasMany
    {
        return $this->hasMany(self::class, 'parent_user_id', 'user_id');
    }

    /**
     * Human-readable label for agency tier.
     */
    public function getTierLabelAttribute(): string
    {
        $labels = self::getTierLabels();

        return $labels[$this->agency_tier] ?? ucfirst($this->agency_tier);
    }

    /**
     * Definitions of agency hierarchy tiers.
     */
    public static function getTierLabels(): array
    {
        return [
            'fmo' => trans('admin::insurance.hierarchy.tier_fmo'),
            'mga' => trans('admin::insurance.hierarchy.tier_mga'),
            'ga' => trans('admin::insurance.hierarchy.tier_ga'),
            'producer' => trans('admin::insurance.hierarchy.tier_producer'),
            'sub_agent' => trans('admin::insurance.hierarchy.tier_sub_agent'),
        ];
    }

    /**
     * Walk up the tree to collect all uplines receiving overrides for a given writing agent.
     */
    public static function getUplineChain(int $writingAgentId, int $maxDepth = 4): array
    {
        $chain = [];
        $currentUserId = $writingAgentId;
        $level = 1;

        while ($currentUserId && $level <= $maxDepth) {
            $hierarchy = self::where('user_id', $currentUserId)->where('status', 'active')->first();

            if (! $hierarchy || ! $hierarchy->parent_user_id) {
                break;
            }

            $parentId = $hierarchy->parent_user_id;
            $parentHierarchy = self::where('user_id', $parentId)->first();
            $parentUser = User::find($parentId);

            if ($parentUser) {
                $chain[] = [
                    'level' => $level,
                    'beneficiary_user_id' => $parentUser->id,
                    'beneficiary_name' => $parentUser->name,
                    'agency_tier' => $parentHierarchy?->agency_tier ?: 'ga',
                    'tier_name' => ($parentHierarchy?->tier_label ?: 'Upline Level '.$level).' Override',
                    'override_pmpm' => (float) ($hierarchy->override_pmpm ?: ($parentHierarchy?->override_pmpm ?: 0)),
                    'override_percentage' => (float) ($hierarchy->override_percentage ?: ($parentHierarchy?->override_percentage ?: 0)),
                ];
            }

            $currentUserId = $parentId;
            $level++;
        }

        return $chain;
    }
}

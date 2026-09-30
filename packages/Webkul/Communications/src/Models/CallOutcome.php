<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;

class CallOutcome extends Model
{
    public const TONES = ['positive', 'neutral', 'negative'];

    protected $table = 'communication_call_outcomes';

    protected $fillable = [
        'agency_id',
        'code',
        'name',
        'tone',
        'revokes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'agency_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Lists already loaded in this request, keyed by agency and filter.
     */
    protected static array $cache = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::$cache = []);
        static::deleted(fn () => static::$cache = []);
    }

    /**
     * Outcomes visible to the signed-in user's agency: the shared defaults
     * plus the agency's own, where an agency row overrides a shared one.
     */
    public static function forAgency(?int $agencyId = null, bool $activeOnly = false): Collection
    {
        $agencyId ??= auth()->guard('user')->user()?->agency_id;

        return static::$cache[(int) $agencyId.':'.(int) $activeOnly] ??= static::query()
            ->where(fn (Builder $query) => $query->whereNull('agency_id')->when($agencyId, fn ($q) => $q->orWhere('agency_id', $agencyId)))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->sortByDesc('agency_id')
            ->unique('code')
            ->when($activeOnly, fn ($items) => $items->where('is_active', true))
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->values();
    }

    /**
     * Label of an outcome code for the current agency, or the code itself.
     */
    public static function labelFor(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        return static::forAgency()->firstWhere('code', $code)?->label ?? $code;
    }

    /**
     * Custom name, or the translated default.
     */
    public function getLabelAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $key = "communications::app.outcomes.defaults.{$this->code}";

        return Lang::has($key) ? trans($key) : $this->code;
    }
}

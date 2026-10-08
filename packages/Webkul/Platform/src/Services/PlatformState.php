<?php

namespace Webkul\Platform\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Subscription state of this instance, set by the SaaS operator:
 *
 * - active:    normal use
 * - warning:   banner (e.g. "your payment is pending")
 * - read_only: people can look and export, not create or change anything
 * - suspended: only a "contact support" page; data is kept intact
 */
class PlatformState
{
    public const STATUSES = ['active', 'warning', 'read_only', 'suspended'];

    protected ?array $values = null;

    public function status(): string
    {
        $status = $this->all()['status'] ?? 'active';

        return in_array($status, self::STATUSES, true) ? $status : 'active';
    }

    public function message(): ?string
    {
        return $this->all()['message'] ?? null;
    }

    public function set(string $status, ?string $message = null): void
    {
        abort_unless(in_array($status, self::STATUSES, true), 422);

        foreach (['status' => $status, 'message' => $message, 'changed_at' => now()->toIso8601String()] as $key => $value) {
            DB::table('platform_state')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        }

        $this->values = null;
    }

    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        try {
            return $this->values = Schema::hasTable('platform_state')
                ? DB::table('platform_state')->pluck('value', 'key')->all()
                : [];
        } catch (Throwable) {
            return $this->values = [];
        }
    }
}

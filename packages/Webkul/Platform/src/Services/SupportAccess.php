<?php

namespace Webkul\Platform\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\User\Models\User;

/**
 * What the SaaS operator can do inside an instance, always recorded in the
 * instance's own access log so the agency can see it:
 *
 * - a one-time support login link (valid one minute),
 * - an emergency password reset for the agency owner (temporary password,
 *   optionally clearing two-factor when the phone was lost),
 * - a health snapshot.
 */
class SupportAccess
{
    /**
     * The agency owner account (first active full-permission user), or the given email.
     */
    public function owner(?string $email = null): ?User
    {
        $query = User::with('role')->where('status', 1);

        if ($email) {
            return $query->where('email', $email)->first();
        }

        return $query->whereHas('role', fn ($q) => $q->where('permission_type', 'all'))->orderBy('id')->first();
    }

    public function createLink(User $user, ?string $reason = null): string
    {
        $token = Str::random(48);

        DB::table('platform_support_tokens')->insert([
            'token_hash' => hash('sha256', $token),
            'user_id' => $user->id,
            'reason' => $reason ? Str::limit($reason, 200, '') : null,
            'expires_at' => now()->addSeconds((int) config('platform.support_link_ttl', 60)),
            'created_at' => now(),
        ]);

        return route('platform.support.login', $token);
    }

    /**
     * The user behind a valid, unused link; the link is spent either way.
     */
    public function consume(string $token): ?User
    {
        $row = DB::table('platform_support_tokens')->where('token_hash', hash('sha256', $token))->first();

        if (! $row || $row->used_at || now()->gt($row->expires_at)) {
            return null;
        }

        DB::table('platform_support_tokens')->where('id', $row->id)->update(['used_at' => now()]);

        return User::where('id', $row->user_id)->where('status', 1)->first();
    }

    /**
     * New temporary password for the owner. Returns it once; never stored in clear.
     */
    public function resetPassword(User $user, bool $disableTwoFactor = false): string
    {
        $password = Str::password(14, symbols: false);

        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($password), 'remember_token' => null, 'updated_at' => now()]);

        if ($disableTwoFactor && class_exists('Webkul\\Security\\Models\\TwoFactor')) {
            DB::table('user_two_factor')->where('user_id', $user->id)->delete();
        }

        $this->log('password_reset', $user, $disableTwoFactor ? 'platform · 2FA cleared' : 'platform');

        return $password;
    }

    /**
     * First run of a new instance: the installer's default administrator
     * becomes the agency owner (their email and name, a temporary password),
     * the agency time zone is set, and the instance is marked as installed
     * (containers get their settings from the environment, not a .env file,
     * so Krayin would otherwise keep sending people to its web installer).
     */
    public function setupOwner(string $email, ?string $name = null, ?string $timezone = null): array
    {
        $user = User::where('email', 'admin@example.com')->first() ?? $this->owner();

        abort_unless($user, 404);

        $user->forceFill(array_filter(['email' => $email, 'name' => $name]))->save();

        if ($timezone) {
            DB::table('core_config')->updateOrInsert(
                ['code' => 'general.general.timezone.timezone'],
                ['value' => $timezone, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        app(DatabaseManager::class)->markInstallationCompleted();
        File::put(storage_path('installed'), 'Installed by the platform');

        return [$user->refresh(), $this->resetPassword($user)];
    }

    public function log(string $event, User $user, string $label): void
    {
        $repository = 'Webkul\\Security\\Repositories\\AccessLogRepository';

        if (class_exists($repository)) {
            app($repository)->record($event, $user->id, Str::limit($label.' · '.$user->email, 250, ''));
        }
    }

    public function health(PlatformState $state): array
    {
        $lastLogin = DB::table('user_access_logs')->where('event', 'login')->max('created_at');

        $dbBytes = (int) DB::table('information_schema.tables')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->sum(DB::raw('data_length + index_length'));

        return [
            'status' => $state->status(),
            'message' => $state->message(),
            'app' => config('app.name'),
            'version' => trim((string) @file_get_contents(base_path('VERSION'))) ?: null,
            'users_active' => DB::table('users')->where('status', 1)->count(),
            'contacts' => DB::table('persons')->count(),
            'leads_open' => DB::table('leads')->join('lead_pipeline_stages', 'lead_pipeline_stages.id', '=', 'leads.lead_pipeline_stage_id')->whereNotIn('lead_pipeline_stages.code', ['won', 'lost'])->count(),
            'policies' => DB::table('insurance_policies')->count(),
            'last_login_at' => $lastLogin,
            'database_mb' => round($dbBytes / 1048576, 1),
            'checked_at' => now()->toIso8601String(),
        ];
    }
}

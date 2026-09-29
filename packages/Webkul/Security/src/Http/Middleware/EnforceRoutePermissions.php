<?php

namespace Webkul\Security\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Safety net for custom-permission roles (assistant, auditor, front desk…).
 *
 * Krayin's Bouncer only checks routes listed in config('acl'), and many
 * insurance routes are not listed. For users whose role is not "all", this
 * middleware maps every other admin route to a module permission:
 *
 * - reading (GET) needs the module's base permission,
 * - creating / changing needs "<module>.create" / "<module>.edit",
 * - deleting needs "<module>.delete" (falls back to ".edit"),
 * - unknown write routes are denied; unknown reads are allowed.
 *
 * Full-permission roles are never affected.
 */
class EnforceRoutePermissions
{
    /**
     * Routes any signed-in user may use (their account, 2FA, follow-ups, grid helpers).
     */
    protected array $alwaysAllowed = [
        'admin.session.*',
        'admin.security.two_factor.*',
        'admin.user.account.*',
        'admin.datagrid.*',
        'admin.tinymce.*',
        'admin.teamwork.*',
        'admin.dashboard.*',
    ];

    /**
     * Route name pattern => [read permission, edit permission, delete permission|null].
     * First match wins.
     */
    protected array $modules = [
        '/^admin\.(insurance\.)?leads\./' => ['leads', 'leads.edit', 'leads.delete'],
        '/^admin\.(policies|service_cases|insurance\.action_board)\./' => ['policies', 'policies.edit', null],
        '/^admin\.(commissions|insurance\.(commissions|ledger|revenue_shield))\./' => ['commissions', 'commissions.edit', null],
        '/^admin\.hierarchy\./' => ['hierarchy', 'hierarchy.edit', null],
        '/^admin\.insurance\.analytics\./' => ['insurance_analytics', null, null],
        '/^admin\.insurance\.agent_compliance\./' => ['settings.user.users', 'settings.user.users.edit', null],
        '/^admin\.quotes\./' => ['quotes', 'quotes.edit', 'quotes.delete'],
        '/^admin\.contacts\.persons\./' => ['contacts.persons', 'contacts.persons.edit', 'contacts.persons.delete'],
        '/^admin\.communications\.persons\./' => ['contacts.persons.view', null, null],
        '/^admin\.contacts\.organizations\./' => ['contacts.organizations', 'contacts.organizations.edit', 'contacts.organizations.delete'],
        '/^admin\.products\./' => ['products', 'products.edit', 'products.delete'],
        '/^admin\.mail\./' => ['mail', 'mail.edit', 'mail.delete'],
        '/^admin\.activities\./' => ['activities', 'activities.edit', 'activities.delete'],
        '/^admin\.configuration\./' => ['configuration', 'configuration.edit', null],
        '/^admin\.settings\./' => ['settings', null, null],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->guard('user')->user();
        $route = $request->route();

        if (! $user || ! $route || $user->role?->permission_type === 'all') {
            return $next($request);
        }

        $name = (string) $route->getName();

        // Revenue figures on the dashboard are financial information.
        if (
            $name === 'admin.dashboard.stats'
            && str_starts_with((string) $request->query('type'), 'revenue')
            && ! bouncer()->hasPermission('financials')
        ) {
            return response()->json(['message' => trans('admin::app.errors.401')], 401);
        }

        if (! str_starts_with($name, 'admin.') || $request->routeIs(...$this->alwaysAllowed)) {
            return $next($request);
        }

        // Listed in config('acl'): Krayin's Bouncer already enforces it.
        if (array_key_exists($name, acl()->getRoles())) {
            return $next($request);
        }

        $permission = $this->requiredPermission($name, $request->method());

        if ($permission === true || ($permission && bouncer()->hasPermission($permission))) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => trans('admin::app.errors.401')], 401);
        }

        abort(401, trans('admin::app.errors.401'));
    }

    /**
     * Permission key needed, true when nothing is needed, or null to deny.
     */
    protected function requiredPermission(string $name, string $method): string|bool|null
    {
        $isRead = in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);

        foreach ($this->modules as $pattern => [$read, $edit, $delete]) {
            if (! preg_match($pattern, $name)) {
                continue;
            }

            if ($isRead) {
                return $read;
            }

            if ($method === 'DELETE' || preg_match('/\.(destroy|delete|remove|mass_delete)/', $name)) {
                return $delete ?? $edit;
            }

            if (preg_match('/\.(create|store)/', $name) && $edit && bouncer()->hasPermission(str_replace('.edit', '.create', $edit))) {
                return true;
            }

            return $edit;
        }

        // Unknown module: reading is harmless, writing is not.
        return $isRead ? true : null;
    }
}

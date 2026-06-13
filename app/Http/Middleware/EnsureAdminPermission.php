<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    /**
     * Restrict admin routes by permission instead of hard-coded module roles.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (Auth::guard('super_admin')->check()) {
            Auth::shouldUse('super_admin');

            return $next($request);
        }

        $permissions = array_values(array_filter(
            array_map(fn (string $permission): string => trim($permission), $permissions),
            fn (string $permission): bool => $permission !== '',
        ));

        $admin = Auth::guard('admin')->user();

        if ($admin) {
            Auth::shouldUse('admin');

            if ($permissions === [] || $this->adminHasAnyPermission($admin, $permissions)) {
                return $next($request);
            }

            return $this->deny($request, $admin->defaultRouteName());
        }

        $user = Auth::guard('web')->user();

        if ($user && $user->isAdmin()) {
            Auth::shouldUse('web');

            if ($permissions === [] || $this->legacyAdminHasAnyPermission($permissions)) {
                return $next($request);
            }
        }

        return $this->deny($request, 'login');
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function adminHasAnyPermission(Admin $admin, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($admin->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function legacyAdminHasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (in_array($permission, Admin::permissionsForRole(Admin::ROLE_ADMIN), true)) {
                return true;
            }
        }

        return false;
    }

    protected function deny(Request $request, string $routeName): Response
    {
        if ($request->expectsJson()) {
            abort(403, 'Your administrator role cannot access this module.');
        }

        if ($request->routeIs($routeName)) {
            abort(403, 'Your administrator role cannot access this module.');
        }

        return redirect()
            ->route($routeName)
            ->with('error', 'Your administrator role cannot access that module.');
    }
}

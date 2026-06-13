<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Restrict admin workspace routes to specific admin account roles.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (Auth::guard('super_admin')->check()) {
            return $next($request);
        }

        $admin = Auth::guard('admin')->user();

        if ($admin) {
            Auth::shouldUse('admin');

            if ($roles === [] || $admin->hasAnyRole($roles)) {
                return $next($request);
            }

            return $this->deny($request, $admin->defaultRouteName());
        }

        $user = Auth::guard('web')->user();

        if ($user && $user->isAdmin() && ($roles === [] || in_array(Admin::ROLE_ADMIN, $roles, true))) {
            Auth::shouldUse('web');

            return $next($request);
        }

        return $this->deny($request, 'login');
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

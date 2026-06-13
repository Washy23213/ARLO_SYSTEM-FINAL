<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotAdmin
{
    /**
     * Redirect admins away from alumni/user pages.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $webUser = Auth::guard('web')->user();
        $superAdmin = Auth::guard('super_admin')->user();

        if ($superAdmin) {
            return redirect()->route('admin.dashboard');
        }

        $admin = Auth::guard('admin')->user();

        if ($admin) {
            return redirect()->route($admin->defaultRouteName());
        }

        if ($webUser && $webUser->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}

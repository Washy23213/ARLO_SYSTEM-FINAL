<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Redirect authenticated users away from guest pages.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('super_admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            return redirect()->route($admin?->defaultRouteName() ?? 'admin.dashboard');
        }

        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}

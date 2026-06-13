<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RegularAdminOnly
{
    /**
     * Allow administrator accounts into the operational admin workspace.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('super_admin')->check()) {
            Auth::shouldUse('super_admin');

            return $next($request);
        }

        $admin = Auth::guard('admin')->user();

        if ($admin) {
            Auth::shouldUse('admin');

            return $next($request);
        }

        $user = Auth::guard('web')->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isAdmin()) {
            return redirect()->route('dashboard');
        }

        Auth::shouldUse('web');

        return $next($request);
    }
}

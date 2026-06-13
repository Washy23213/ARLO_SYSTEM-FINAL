<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Allow only authenticated admins.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('super_admin')->check()) {
            Auth::shouldUse('super_admin');

            return $next($request);
        }

        if (Auth::guard('admin')->check()) {
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

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    /**
     * Allow only the Super Admin account into the account-control workspace.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $superAdmin = Auth::guard('super_admin')->user();

        if (! $superAdmin) {
            $admin = Auth::guard('admin')->user();

            return $admin
                ? redirect()->route($admin->defaultRouteName())
                : (Auth::guard('web')->check()
                ? redirect()->route('dashboard')
                : redirect()->route('login'));
        }

        Auth::shouldUse('super_admin');

        return $next($request);
    }
}

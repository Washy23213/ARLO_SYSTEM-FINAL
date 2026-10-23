<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(\App\Http\Middleware\PreventBackHistory::class);
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,
            'regular_admin' => \App\Http\Middleware\RegularAdminOnly::class,
            'super_admin' => \App\Http\Middleware\SuperAdminOnly::class,
            'no_admin' => \App\Http\Middleware\EnsureNotAdmin::class,
            'admin_role' => \App\Http\Middleware\EnsureAdminRole::class,
            'admin_permission' => \App\Http\Middleware\EnsureAdminPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

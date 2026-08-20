<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/tripay',
        ]);

        // The app always sits behind a reverse proxy we control (host nginx and/or the
        // dockerized nginx), never directly exposed — trusting all proxies here is what lets
        // $request->secure() correctly read the proxy's X-Forwarded-Proto instead of always
        // seeing the plain-HTTP connection nginx makes to PHP-FPM internally.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\AddSecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

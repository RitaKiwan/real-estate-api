<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // The Next.js server calls this API on behalf of visitors and forwards their IP in
        // X-Forwarded-For. Two ways to make that safe:
        //  - GATEWAY_SECRET set: requests without the secret lose their forwarded headers
        //    (works on hosts with changing addresses such as Vercel; use TRUSTED_PROXIES=*).
        //  - otherwise only addresses listed in TRUSTED_PROXIES may set it.
        // Either way rate limits (login, inquiries) apply per visitor instead of per server.
        $middleware->prepend(\App\Http\Middleware\TrustGatewayHeaders::class);
        $middleware->trustProxies(at: array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1'))
        ))));

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

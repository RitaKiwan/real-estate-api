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
        // The Next.js server calls this API on behalf of visitors and sends their IP in
        // X-Visitor-IP together with the shared secret in X-Gateway-Secret.
        //  - GATEWAY_SECRET set: TrustGatewayHeaders accepts X-Visitor-IP only from requests
        //    that carry the secret, and strips all forwarded headers from every other request.
        //    Rate limits then use $request->visitorIp() (per visitor, not per server).
        //    Set TRUSTED_PROXIES=* so https / host detection keeps working behind the host's proxy.
        //  - GATEWAY_SECRET empty (local development): only addresses listed in
        //    TRUSTED_PROXIES may set forwarded headers.
        // This middleware runs first, before TrustProxies and before any rate limiter.
        $middleware->prepend(\App\Http\Middleware\TrustGatewayHeaders::class);

        // Laravel only treats the *string* '*' (or '**') as "trust the calling proxy",
        // so pass it through as a string instead of turning it into an array.
        $proxies = trim((string) env('TRUSTED_PROXIES', '127.0.0.1,::1'));

        $middleware->trustProxies(at: in_array($proxies, ['*', '**'], true)
            ? $proxies
            : array_values(array_filter(array_map('trim', explode(',', $proxies)))));

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
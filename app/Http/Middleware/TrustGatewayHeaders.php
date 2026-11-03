<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets the website's server (the "gateway") tell this API who the real visitor is.
 *
 * Hosting platforms such as Vercel call the API from changing addresses, so a fixed
 * TRUSTED_PROXIES list is not possible. Instead the gateway proves itself with a shared
 * secret header (X-Gateway-Secret) and names the visitor in X-Visitor-IP.
 *
 *  - Secret correct: the visitor IP is stored in the request attribute "visitor_ip"
 *    (read it with $request->visitorIp(), see AppServiceProvider).
 *  - Secret missing or wrong: every forwarded-for header is removed and X-Visitor-IP is
 *    ignored, so nobody can fake their IP to dodge the login / inquiry rate limits by
 *    calling the API directly.
 *
 * Active only when GATEWAY_SECRET is set (leave it empty for local development).
 */
class TrustGatewayHeaders
{
    private const FORWARDED_HEADERS = ['X-Forwarded-For', 'X-Forwarded-Host', 'X-Forwarded-Port', 'X-Forwarded-Proto', 'Forwarded'];

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.gateway.secret', '');

        if ($secret !== '') {
            $provided = (string) $request->headers->get('X-Gateway-Secret', '');

            if (hash_equals($secret, $provided)) {
                // The request really comes from our website, so it may name the visitor.
                $ip = (string) $request->headers->get('X-Visitor-IP', '');

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $request->attributes->set('visitor_ip', $ip);
                }
            } else {
                foreach (self::FORWARDED_HEADERS as $header) {
                    $request->headers->remove($header);
                }
            }
        }

        // Never let the secret or the claimed IP travel any further into the application.
        $request->headers->remove('X-Gateway-Secret');
        $request->headers->remove('X-Visitor-IP');

        return $next($request);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * Routes that proxy or embed a third party's own HTML/JS verbatim — our CSP would apply to
     * content we don't control and can break it. Excluded by route name rather than tightened,
     * since we can't audit code we don't own.
     */
    private const EXCLUDED_ROUTES = [
        'settings.whatsapp.qr',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->route() && in_array($request->route()->getName(), self::EXCLUDED_ROUTES, true)) {
            return $response;
        }

        // X-Frame-Options and X-Content-Type-Options are already added by nginx
        // (docker/nginx/app.conf) — not repeated here to avoid duplicate headers.
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 'unsafe-inline'/'unsafe-eval' are unavoidable right now: Alpine.js (not the CSP-safe
        // build) evaluates x-data/x-show expressions via new Function(), and several existing
        // views use native onclick= handlers and inline style= attributes. Tightening those
        // would mean auditing/rewriting views app-wide, not a header change — left as a known
        // follow-up. Even so, this still blocks loading script/style/frame content from any
        // third-party origin, clickjacking via framing, <object>/<embed> plugins, and
        // cross-origin form submission.
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net",
            "img-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            'upgrade-insecure-requests',
        ]));

        return $response;
    }
}

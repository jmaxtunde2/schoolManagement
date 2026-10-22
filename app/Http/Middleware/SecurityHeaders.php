<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ajoute des en-têtes de sécurité HTTP standards.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Empêche le clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Empêche le MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Active la protection XSS du navigateur
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (anciennement Feature Policy)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS (uniquement en production avec HTTPS)
        if ($request->isSecure() && config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy
        $csp = $this->buildCsp($request);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    private function buildCsp(Request $request): string
    {
        $isProduction = config('app.env') === 'production';
        $nonce = $request->attributes->get('csp_nonce', base64_encode(random_bytes(16)));

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic' https://cdn.tailwindcss.com https://unpkg.com",
            "style-src 'self' 'nonce-{$nonce}' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com",
            "font-src 'self' data: https://fonts.gstatic.com",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https: wss:",
            "manifest-src 'self'",
            "frame-src 'self'",
            "worker-src 'self' blob:",
        ];

        if (!$isProduction) {
            // En dev, autoriser eval pour Vite/HMR
            $directives[] = "script-src-eval 'self'";
            $directives[] = "connect-src 'self' ws: wss: http://localhost:*";
        }

        // Stocker le nonce pour l'utiliser dans les vues
        $request->attributes->set('csp_nonce', $nonce);

        return implode('; ', $directives);
    }
}
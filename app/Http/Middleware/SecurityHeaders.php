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
        // Générer le nonce avant la réponse pour qu'il soit disponible dans la vue
        $nonce = $request->attributes->get('csp_nonce', base64_encode(random_bytes(16)));
        $request->attributes->set('csp_nonce', $nonce);
        view()->share('csp_nonce', $nonce);

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

        // Content Security Policy - DISABLED for debugging
        // $csp = $this->buildCsp($nonce);
        // $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    private function buildCsp(string $nonce): string
    {
        $isProduction = config('app.env') === 'production';

        if ($isProduction) {
            $scriptSrc = "'self' 'nonce-{$nonce}' 'strict-dynamic' https://cdn.tailwindcss.com https://unpkg.com";
        } else {
            $scriptSrc = "'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com http://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5174 http://localhost:5174";
        }
        $connectSrc = "'self' https: wss:";
        $styleSrc = "'self' 'nonce-{$nonce}' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com";
        $imgSrc = "'self' data: https: blob:";

        if (!$isProduction) {
            // En dev, autoriser Vite dev server (ports 5173, 5174, etc.) et HMR
            $viteDev = 'http://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5174 http://localhost:5174';
            $connectSrc .= " ws: wss: {$viteDev} http://localhost:*";
            $styleSrc .= " {$viteDev}";
            $imgSrc .= " {$viteDev}";
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src 'self' data: https://fonts.gstatic.com",
            "img-src {$imgSrc}",
            "connect-src {$connectSrc}",
            "manifest-src 'self'",
            "frame-src 'self'",
            "worker-src 'self' blob:",
        ];

        return implode('; ', $directives);
    }
}
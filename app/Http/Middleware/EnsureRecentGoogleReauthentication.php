<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRecentGoogleReauthentication
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless($request->user()?->hasRecentGoogleReauthentication((int) config('auth.google_reauth_seconds', 300)), 403, 'Réauthentification Google requise pour cette action.');

        return $next($request);
    }
}

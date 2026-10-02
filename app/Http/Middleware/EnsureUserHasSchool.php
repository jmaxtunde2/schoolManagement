<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Un utilisateur connecté doit appartenir à un établissement actif. */
class EnsureUserHasSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isPlatformAdmin()) { return $next($request); }

        abort_if(! $user?->school_id || ! $user->school?->is_active, 403, 'Aucun établissement actif associé à ce compte.');

        return $next($request);
    }
}

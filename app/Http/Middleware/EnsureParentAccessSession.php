<?php

namespace App\Http\Middleware;

use App\Models\ParentAccessSession;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Services\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentAccessSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('parent_access_token');

        if (! $token) {
            $token = $request->session()->get('parent_access_token');
        }

        if (! $token) {
            abort(403, 'Accès non autorisé');
        }

        $tokenHash = hash('sha256', $token);
        $session = ParentAccessSession::query()
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $session) {
            abort(403, 'Session invalide');
        }

        if ($session->isRevoked() || $session->isExpired()) {
            abort(403, 'Session expirée ou révoquée');
        }

        $parent = ParentGuardian::query()->find($session->parent_id);
        if (! $parent || ! $parent->is_active) {
            abort(403, 'Accès non autorisé');
        }

        // Check school matches
        $resolver = app(TenantResolver::class);
        $school = $resolver->resolve($request);
        $slug = $request->route('slug');
        if (! $school && $slug) {
            $school = $resolver->resolveBySlug($slug);
        }

        if (! $school) {
            $school = School::query()->find($session->school_id);
        }

        if (! $school || (int) $school->id !== (int) $session->school_id) {
            abort(403, 'Accès non autorisé');
        }

        // Update last activity
        $session->update(['last_activity_at' => now()]);

        // Attach parent, school and session to request
        $request->attributes->set('parent_access_parent', $parent);
        $request->attributes->set('parent_access_school', $school);
        $request->attributes->set('parent_access_session', $session);
        $request->attributes->set('parent_access', true);

        return $next($request);
    }
}

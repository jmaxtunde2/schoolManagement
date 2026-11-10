<?php

namespace App\Http\Middleware;

use App\Models\ParentAccessSession;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Services\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentAccessSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $resolver = app(TenantResolver::class);
        $school = $resolver->resolve($request);
        $slug = $request->route('slug');
        if (! $school && $slug) {
            $school = $resolver->resolveBySlug($slug);
        }

        $token = $request->cookie('parent_access_token');

        if (! $token) {
            $token = $request->session()->get('parent_access_token');
        }

        // Aucun cookie : le parent se retrouve sur la page de saisie du code
        // au lieu d'une page « Accès refusé ».
        if (! $token) {
            return $this->redirectToLogin($request, $school);
        }

        $tokenHash = hash('sha256', $token);
        $session = ParentAccessSession::query()
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $session) {
            return $this->redirectToLogin($request, $school, 'Veuillez saisir votre code d\'accès.');
        }

        $sessionSchool = School::query()->find($session->school_id);
        if (! $school) {
            $school = $sessionSchool;
        }

        if ($session->isRevoked() || $session->isExpired()) {
            return $this->redirectToLogin(
                $request,
                $sessionSchool,
                'Votre session a expiré. Veuillez saisir un nouveau code d\'accès.'
            );
        }

        $parent = ParentGuardian::query()->find($session->parent_id);
        if (! $parent || ! $parent->is_active) {
            return $this->redirectToLogin(
                $request,
                $sessionSchool,
                'Votre accès a expiré. Veuillez saisir un nouveau code d\'accès.'
            );
        }

        if (! $school || ! $sessionSchool || (int) $school->id !== (int) $session->school_id) {
            return $this->redirectToLogin(
                $request,
                $sessionSchool,
                'Veuillez vous reconnecter à votre espace parent.'
            );
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

    private function redirectToLogin(Request $request, ?School $school = null, ?string $message = null): RedirectResponse
    {
        $slug = $request->route('slug');

        if ($school) {
            $redirect = redirect()->route('parent-access.login.slug', ['slug' => $school->slug]);
        } elseif ($slug) {
            $redirect = redirect()->route('parent-access.login.slug', ['slug' => $slug]);
        } else {
            $redirect = redirect()->route('public.school.home');
        }

        return $message ? $redirect->withErrors(['code' => $message]) : $redirect;
    }
}

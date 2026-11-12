<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\ParentAccess\ParentAccessSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Point d'entrée de l'application installée (manifest `start_url: /app`).
 *
 * Une seule PWA CoriSchool existe : cette route retrouve l'établissement du
 * parent à partir de sa session parent (cookie `parent_access_token`) puis le
 * redirige vers son tableau de bord. Sans session valide, elle affiche une page
 * de reprise sans jamais exposer de données scolaires.
 */
class PwaController extends Controller
{
    public function __invoke(
        Request $request,
        ParentAccessSessionService $sessions
    ): Response|RedirectResponse {
        $token = $request->cookie('parent_access_token')
            ?? $request->session()->get('parent_access_token');

        $session = $sessions->findValidByPlainToken($token);

        if ($session) {
            $school = School::query()->find($session->school_id);

            if ($school && $school->is_active) {
                return redirect()->route('parent-access.dashboard.slug', [
                    'slug' => $school->slug,
                ]);
            }
        }

        return Inertia::render('Public/PwaWelcome', [
            'schoolsUrl' => route('public.schools.index'),
            'homeUrl' => route('public.platform.landing'),
        ]);
    }
}

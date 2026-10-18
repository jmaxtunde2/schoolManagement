<?php

namespace App\Http\Controllers\Assistant;

use App\Http\Controllers\Controller;
use App\Services\Assistant\SchoolAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CoriSchool Assistant — interface de pilotage en langage naturel.
 *
 * Accessible à l'administrateur (fonde) et au directeur. Le périmètre et les
 * permissions sont déduits côté serveur de l'utilisateur authentifié ; aucun
 * identifiant d'établissement n'est accepté depuis la requête.
 */
class AssistantController extends Controller
{
    public function __construct(private SchoolAssistantService $assistant) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Assistant/Index', [
            'routePrefix' => $request->routeIs('director.*') ? 'director' : 'admin',
            'userName' => $user->name,
            'roleLabel' => $user->role->label(),
            'suggestions' => $this->assistant->suggestionsFor($user),
        ]);
    }

    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        return response()->json(
            $this->assistant->answer($request->user(), $validated['question']),
        );
    }
}

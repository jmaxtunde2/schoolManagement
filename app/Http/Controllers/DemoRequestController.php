<?php

namespace App\Http\Controllers;

use App\Enums\DemoRequestStatus;
use App\Http\Requests\Demo\StoreDemoRequest;
use App\Models\DemoRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Demande de démonstration — premier maillon du parcours commercial CoriSchool.
 *
 * Remplace l'ancienne inscription publique : soumettre ce formulaire ne crée
 * NI école, NI compte administrateur, NI licence, NI paiement. Seule une
 * ligne `demo_requests` au statut `pending` est écrite, puis le Super Admin
 * Cori enchaîne contact -> planification -> démonstration -> approbation.
 *
 * Route publique (middleware guest) : volontairement hors des groupes
 * `school` / `2fa.configured` / `2fa.verified`.
 */
class DemoRequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Public/DemoRequest');
    }

    public function store(StoreDemoRequest $request): RedirectResponse
    {
        // Champs explicitement listés : rien d'autre n'est accepté du client,
        // et surtout aucun school_id ne peut être injecté.
        DemoRequest::create([
            'school_name' => $request->validated('school_name'),
            'address' => $request->validated('address'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'preferred_demo_date' => $request->validated('preferred_demo_date'),
            'preferred_demo_time' => $request->validated('preferred_demo_time'),
            'status' => DemoRequestStatus::Pending,
        ]);

        return redirect()
            ->route('demo.request')
            ->with(
                'success',
                'Votre demande de démonstration a bien été enregistrée. L’équipe Coriyase vous contactera pour confirmer le créneau.'
            );
    }
}

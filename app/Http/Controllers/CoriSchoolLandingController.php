<?php

namespace App\Http\Controllers;

use App\Support\PricingPlan;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page publique de CoriSchool (le logiciel de gestion), servie sur
 * l'hôte de la plateforme lorsque aucun établissement n'est résolu.
 *
 * Les données sont toutes statiques : aucun effectif, aucune promotion et
 * aucun faux compteur n'est exposé.
 */
class CoriSchoolLandingController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Public/CoriSchoolLanding', [
            'plans' => PricingPlan::plans(),
            'smsNote' => PricingPlan::smsNote(),
            'faq' => PricingPlan::faq(),
        ]);
    }
}

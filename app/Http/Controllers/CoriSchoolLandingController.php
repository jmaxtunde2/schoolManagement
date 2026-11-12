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
            'support' => $this->support(),
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('Public/Contact', [
            'support' => $this->support(),
        ]);
    }

    public function terms(): Response
    {
        return Inertia::render('Public/Terms', [
            'support' => $this->support(),
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Public/Privacy', [
            'support' => $this->support(),
        ]);
    }

    public function banqueEpreuves(): Response
    {
        return Inertia::render('Public/BanqueEpreuvesLanding', [
            'support' => $this->support(),
        ]);
    }

    public function jeuEducatif(): Response
    {
        return Inertia::render('Public/JeuEducatifLanding', [
            'support' => $this->support(),
        ]);
    }

    public function competitions(): Response
    {
        return Inertia::render('Public/CompetitionsLanding', [
            'support' => $this->support(),
        ]);
    }

    /**
     * Coordonnées publiques du service clientèle.
     *
     * @return array{whatsapp: string|null, email: string|null, whatsapp_message: string}
     */
    private function support(): array
    {
        return [
            'whatsapp' => config('cori.support.whatsapp'),
            'email' => config('cori.support.email'),
            'whatsapp_message' => config('cori.support.whatsapp_message'),
        ];
    }
}

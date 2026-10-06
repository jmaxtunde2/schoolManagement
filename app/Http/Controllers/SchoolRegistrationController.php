<?php

namespace App\Http\Controllers;

use App\Actions\School\RegisterSchool;
use App\Enums\Role;
use App\Http\Requests\School\RegisterSchoolRequest;
use App\Models\SchoolSetting;
use App\Support\BeninDepartments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Onboarding : « Créer mon école ».
 *
 * Le formulaire est public (middleware guest) et hors des routes authentifiées,
 * donc il échappe volontairement à `school` / `2fa.configured` / `2fa.verified` :
 * un utilisateur qui n'existe pas encore ne peut pas avoir d'établissement.
 */
class SchoolRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Public/SchoolRegistration', [
            'schoolTypes' => collect(SchoolSetting::SCHOOL_TYPES)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'departments' => collect(BeninDepartments::options())
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'defaultAcademicYear' => $this->suggestAcademicYear(),
        ]);
    }

    public function store(RegisterSchoolRequest $request, RegisterSchool $register): RedirectResponse
    {
        $attributes = $request->schoolAttributes();

        // Identité visuelle par défaut : l'établissement la personnalisable ensuite
        // depuis Administration → Établissement. La palette verte/teal CoriSchool
        // sert de point de départ plutôt que le bleu par défaut de la plateforme.
        $result = $register->handle(
            [
                ...$attributes,
                'country' => $attributes['country'] ?? 'Bénin',
                'primary_color' => '#047857',
                'secondary_color' => '#0F766E',
                'accent_color' => '#D97706',
            ],
            $request->adminAttributes(),
            $request->file('logo'),
        );

        // Connexion directe : le compte vient d'être créé dans cette même requête.
        Auth::guard('web')->login($result['admin']);
        $request->session()->regenerate();

        return redirect()
            ->route(Role::Admin->homeRoute())
            ->with(
                'success',
                "Bienvenue ! L'établissement « {$result['school']->name} » est prêt. Configurez vos classes et votre personnel pour démarrer."
            );
    }

    /**
     * Année scolaire béninoise par défaut : septembre -> juillet.
     * En août on prépare déjà l'année suivante, comme dans les établissements.
     */
    private function suggestAcademicYear(): array
    {
        $startYear = now()->month >= 8 ? now()->year : now()->year - 1;

        return [
            'name' => $startYear.'-'.($startYear + 1),
            'starts_on' => $startYear.'-09-16',
            'ends_on' => ($startYear + 1).'-07-15',
        ];
    }
}

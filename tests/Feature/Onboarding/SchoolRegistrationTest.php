<?php

namespace Tests\Feature\Onboarding;

use App\Actions\School\RegisterSchool;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Onboarding : landing plateforme et création d'établissement.
 *
 * Le parcours public ne crée plus d'école : `/creer-mon-ecole` redirige vers
 * la demande de démonstration. L'action RegisterSchool reste utilisée par
 * l'activation Super Admin (approbation + paiement), d'où les tests directs.
 */
class SchoolRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_exposes_the_pricing_plans_and_the_faq(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/CoriSchoolLanding')
            ->has('plans', 3)
            ->has('plans.0.key')
            ->has('plans.0.price')
            ->has('plans.0.features')
            ->has('plans.1.featured')
            ->has('smsNote')
            ->has('faq.0.question')
            ->has('faq.0.answer')
        );
    }

    public function test_landing_page_calls_to_action_point_to_the_demo_request(): void
    {
        // Les CTA de la landing sont calculés côté client : on vérifie la source.
        $source = file_get_contents(
            base_path('resources/js/Pages/Public/CoriSchoolLanding.jsx')
        );

        $this->assertStringContainsString("route('demo.request')", $source);
        $this->assertStringNotContainsString("route('school.register')", $source);
        $this->assertStringNotContainsString('Créer mon école', $source);
        $this->assertStringContainsString('Demander une démonstration', $source);
    }

    public function test_the_legacy_registration_url_no_longer_creates_a_school(): void
    {
        $before = [
            School::withoutGlobalScopes()->count(),
            User::withoutGlobalScopes()->count(),
            AcademicYear::withoutGlobalScopes()->count(),
        ];

        $this->get('/creer-mon-ecole')->assertRedirect(route('demo.request'));
        $this->post('/creer-mon-ecole', [])->assertRedirect(route('demo.request'));

        $this->assertSame($before, [
            School::withoutGlobalScopes()->count(),
            User::withoutGlobalScopes()->count(),
            AcademicYear::withoutGlobalScopes()->count(),
        ]);
    }

    public function test_register_school_still_creates_the_school_the_year_and_the_admin(): void
    {
        // Utilisé lors de l'activation Super Admin (Phase 3), pas depuis le public.
        $result = app(RegisterSchool::class)->handle(
            [
                'school_name' => 'Lycée Moderne de Cotonou',
                'short_name' => 'LMC',
                'address' => 'Rue des Écoles',
                'city' => 'Cotonou',
                'department' => 'Atlantique',
                'country' => 'Bénin',
                'phone' => '+229 01 97 00 00 00',
                'primary_color' => '#047857',
                'secondary_color' => '#0F766E',
                'accent_color' => '#D97706',
                'academic_year_name' => '2025-2026',
                'academic_year_starts_on' => '2025-09-16',
                'academic_year_ends_on' => '2026-07-15',
            ],
            [
                'name' => 'Jean Kossi',
                'email' => 'direction@lmc.test',
                'password' => 'motdepasse123',
                'phone' => '+229 01 95 00 00 00',
            ],
        );

        $this->assertSame('Lycée Moderne de Cotonou', $result['school']->name);
        $this->assertTrue($result['admin']->isAdmin());
        $this->assertSame($result['school']->id, $result['admin']->school_id);
        $this->assertTrue($result['academic_year']->is_current);

        $this->assertSame('#047857', $result['setting']->primary_color);
    }
}

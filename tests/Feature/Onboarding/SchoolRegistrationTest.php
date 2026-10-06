<?php

namespace Tests\Feature\Onboarding;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Onboarding public : landing plateforme et création d'établissement.
 */
class SchoolRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'school_name' => 'Lycée Moderne de Cotonou',
            'short_name' => 'LMC',
            'address' => 'Rue des Écoles',
            'city' => 'Cotonou',
            'department' => 'Atlantique',
            'country' => 'Bénin',
            'phone' => '+229 01 97 00 00 00',
            'academic_year_name' => '2025-2026',
            'academic_year_starts_on' => '2025-09-16',
            'academic_year_ends_on' => '2026-07-15',
            'admin_name' => 'Jean Kossi',
            'admin_email' => 'direction@lmc.test',
            'admin_password' => 'motdepasse123',
            'admin_password_confirmation' => 'motdepasse123',
        ], $overrides);
    }

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

    public function test_registration_form_is_reachable_by_a_guest_and_offers_the_beninese_departments(): void
    {
        $response = $this->get(route('school.register'));

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/SchoolRegistration')
            ->has('schoolTypes')
            ->has('departments')
            ->where('departments.0.value', 'Alibori')
            ->has('defaultAcademicYear.name')
            ->has('defaultAcademicYear.starts_on')
            ->has('defaultAcademicYear.ends_on')
        );
    }

    public function test_registration_creates_the_school_the_academic_year_and_the_admin_and_logs_him_in(): void
    {
        $response = $this->post(route('school.register.store'), $this->payload([
            'admin_phone' => '+229 01 95 00 00 00',
        ]));

        $school = School::where('name', 'Lycée Moderne de Cotonou')->firstOrFail();

        $admin = User::where('email', 'direction@lmc.test')->firstOrFail();

        $response->assertRedirect(route(Role::Admin->homeRoute()));
        $response->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($admin);

        // school_id vient du serveur : l'admin est bien rattaché à sa nouvelle école.
        $this->assertSame($school->id, $admin->school_id);
        $this->assertSame('+229 01 95 00 00 00', $admin->phone);
        $this->assertTrue($admin->isAdmin());

        $this->assertSame('LMC', $school->settings->short_name);
        $this->assertSame('Cotonou', $school->settings->city);

        // Année scolaire créée et marquée comme courante.
        $this->assertDatabaseHas('academic_years', [
            'school_id' => $school->id,
            'name' => '2025-2026',
            'is_current' => true,
        ]);
    }

    public function test_registration_applies_the_corischool_green_teal_identity_by_default(): void
    {
        $this->post(route('school.register.store'), $this->payload());

        $school = School::where('name', 'Lycée Moderne de Cotonou')->firstOrFail();

        $this->assertSame('#047857', $school->settings->primary_color);
        $this->assertSame('#0F766E', $school->settings->secondary_color);
    }

    public function test_registration_stores_the_logo_when_one_is_provided(): void
    {
        $this->post(route('school.register.store'), $this->payload([
            'logo' => UploadedFile::fake()->image('logo.jpg', 400, 400),
        ]));

        $school = School::where('name', 'Lycée Moderne de Cotonou')->firstOrFail();

        Storage::disk('public')->assertExists($school->settings->logo_path);
    }

    public function test_registration_rejects_a_svg_logo(): void
    {
        $this->post(route('school.register.store'), $this->payload([
            'logo' => UploadedFile::fake()->create('logo.svg', 8, 'image/svg+xml'),
        ]))->assertSessionHasErrors('logo');

        $this->assertDatabaseCount('schools', 0);
    }

    public function test_registration_requires_a_matching_password_confirmation(): void
    {
        $this->post(route('school.register.store'), $this->payload([
            'admin_password_confirmation' => 'autrechose123',
        ]))->assertSessionHasErrors('admin_password');

        $this->assertDatabaseCount('schools', 0);
    }

    public function test_registration_rejects_a_department_outside_benin(): void
    {
        $this->post(route('school.register.store'), $this->payload([
            'department' => 'Île-de-France',
        ]))->assertSessionHasErrors('department');

        $this->assertDatabaseCount('schools', 0);
    }

    public function test_registration_rejects_an_admin_email_already_in_use(): void
    {
        User::factory()->create(['email' => 'direction@lmc.test']);

        $this->post(route('school.register.store'), $this->payload())
            ->assertSessionHasErrors('admin_email');

        // L'école factory déjà créée par le user factory n'est pas concernée :
        // aucune école ne doit porter le nom soumis.
        $this->assertSame(
            0,
            School::where('name', 'Lycée Moderne de Cotonou')->count()
        );
    }

    public function test_a_school_id_sent_by_the_client_is_ignored(): void
    {
        $other = School::factory()->create();

        $this->post(route('school.register.store'), $this->payload([
            'school_id' => $other->id,
        ]));

        $admin = User::where('email', 'direction@lmc.test')->firstOrFail();
        $school = School::where('name', 'Lycée Moderne de Cotonou')->firstOrFail();

        $this->assertNotSame($other->id, $admin->school_id);
        $this->assertSame($school->id, $admin->school_id);
    }

    public function test_an_authenticated_user_cannot_reach_the_registration_form(): void
    {
        $admin = User::factory()->admin()->create();

        // Le middleware `guest` renvoie un utilisateur déjà connecté vers son espace.
        $this->actingAs($admin)
            ->get(route('school.register'))
            ->assertRedirect(route(Role::Admin->homeRoute()));
    }

    public function test_the_second_academic_year_can_be_created_afterwards(): void
    {
        $this->post(route('school.register.store'), $this->payload());

        $school = School::where('name', 'Lycée Moderne de Cotonou')->firstOrFail();

        $previous = AcademicYear::where('school_id', $school->id)->firstOrFail();
        $previous->forceFill(['is_current' => false])->save();

        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026-2027',
            'starts_on' => '2026-09-16',
            'ends_on' => '2027-07-15',
            'is_current' => true,
        ]);

        $this->assertSame(2, AcademicYear::where('school_id', $school->id)->count());
        $this->assertSame(1, AcademicYear::where('school_id', $school->id)->where('is_current', true)->count());
    }

    public function test_a_new_school_has_no_student_nor_parent_yet(): void
    {
        $this->post(route('school.register.store'), $this->payload());

        $school = School::where('name', 'Lycée Moderne de Cotonou')->firstOrFail();

        $this->assertSame(0, ParentGuardian::withoutGlobalScopes()->where('school_id', $school->id)->count());
    }
}

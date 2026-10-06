<?php

namespace Tests\Feature\Staff;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Tableau de bord des rôles de pilotage (directeur et comptable).
 *
 * Ces deux rôles partagent une page en lecture seule : `billing` n'est présent
 * que pour le comptable.
 */
class ManagementDashboardTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create([
            'name' => 'Lycée Les Genies',
        ]);
    }

    public function test_the_director_sees_the_school_overview_without_billing(): void
    {
        AcademicYear::factory()->forSchool($this->school)->create([
            'name' => '2026-2027',
            'is_current' => true,
        ]);

        $this->actingAs(
            User::factory()->create([
                'school_id' => $this->school->id,
                'role' => Role::Director,
            ])
        )
            ->get(route('director.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/ManagementDashboard')
                ->where('role', Role::Director->value)
                ->where('school.name', 'Lycée Les Genies')
                ->where('currentAcademicYear.name', '2026-2027')
                ->has('stats.students')
                ->has('stats.teachers')
                ->has('stats.classes')
                ->has('stats.staff')
                ->has('stats.parents')
                ->has('stats.timetable_slots')
                ->missing('billing')
            );
    }

    public function test_the_accountant_also_sees_the_billing_summary(): void
    {
        AcademicYear::factory()->forSchool($this->school)->create([
            'is_current' => true,
        ]);

        $this->actingAs(
            User::factory()->create([
                'school_id' => $this->school->id,
                'role' => Role::Accountant,
            ])
        )
            ->get(route('accountant.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/ManagementDashboard')
                ->where('role', Role::Accountant->value)
                ->has('billing.summary.collected')
                ->has('billing.summary.coriyase_share')
                ->has('billing.summary.school_share')
                ->has('billing.settings.installation_fee')
                ->has('billing.recent_contributions')
            );
    }

    public function test_the_page_survives_a_school_without_current_academic_year(): void
    {
        $this->actingAs(
            User::factory()->create([
                'school_id' => $this->school->id,
                'role' => Role::Director,
            ])
        )
            ->get(route('director.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/ManagementDashboard')
                ->where('currentAcademicYear', null)
            );
    }

    public function test_the_dashboard_is_reserved_to_the_director_and_the_accountant(): void
    {
        $admin = User::factory()->admin()->forSchool($this->school)->create();

        $this->actingAs($admin)
            ->get(route('director.dashboard'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('accountant.dashboard'))
            ->assertForbidden();
    }

    public function test_a_dashboard_of_another_school_stays_empty(): void
    {
        $this->actingAs(
            User::factory()->create([
                'school_id' => $this->school->id,
                'role' => Role::Director,
            ])
        )
            ->get(route('director.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // Aucune année scolaire dans cette école : rien ne fuite ailleurs.
                ->where('currentAcademicYear', null)
                ->where('stats.students', 0)
                ->where('stats.classes', 0)
            );
    }
}

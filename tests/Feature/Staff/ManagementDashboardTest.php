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
 * Tableau de bord du comptable.
 *
 * Le directeur dispose désormais de son propre tableau de bord pédagogique
 * (voir Tests\Feature\Director\DashboardTest) ; cette page de pilotage en
 * lecture est réservée au comptable, qui y retrouve en plus la facturation.
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

    public function test_the_accountant_sees_the_school_overview_with_billing(): void
    {
        AcademicYear::factory()->forSchool($this->school)->create([
            'name' => '2026-2027',
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
                ->where('school.name', 'Lycée Les Genies')
                ->where('currentAcademicYear.name', '2026-2027')
                ->has('stats.students')
                ->has('stats.teachers')
                ->has('stats.classes')
                ->has('stats.staff')
                ->has('stats.parents')
                ->has('stats.timetable_slots')
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
                'role' => Role::Accountant,
            ])
        )
            ->get(route('accountant.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/ManagementDashboard')
                ->where('currentAcademicYear', null)
            );
    }

    public function test_the_dashboard_is_reserved_to_the_accountant(): void
    {
        $admin = User::factory()->admin()->forSchool($this->school)->create();
        $director = User::factory()->forSchool($this->school)->create([
            'role' => Role::Director,
        ]);

        $this->actingAs($admin)
            ->get(route('accountant.dashboard'))
            ->assertForbidden();

        $this->actingAs($director)
            ->get(route('accountant.dashboard'))
            ->assertForbidden();
    }

    public function test_a_dashboard_of_another_school_stays_empty(): void
    {
        $this->actingAs(
            User::factory()->create([
                'school_id' => $this->school->id,
                'role' => Role::Accountant,
            ])
        )
            ->get(route('accountant.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // Aucune année scolaire dans cette école : rien ne fuite ailleurs.
                ->where('currentAcademicYear', null)
                ->where('stats.students', 0)
                ->where('stats.classes', 0)
            );
    }
}

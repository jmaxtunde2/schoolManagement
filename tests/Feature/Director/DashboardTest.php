<?php

namespace Tests\Feature\Director;

use App\Enums\Role;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Rôle « Directeur Primaire ».
 *
 * Le directeur pilote l'établissement en lecture, mais uniquement sur le
 * primaire : les classes, élèves, parents et enseignants des niveaux
 * secondaires ne doivent jamais apparaître dans ses vues.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $director;

    private ClassRoom $primaryClass;

    private ClassRoom $secondaryClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        $this->director = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => Role::Director,
        ]);

        $this->primaryClass = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'CI A',
            'level' => 'CI',
        ]);

        $this->secondaryClass = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => '2nde A',
            'level' => '2nde',
        ]);
    }

    public function test_the_director_dashboard_renders_the_pedagogical_overview(): void
    {
        $this->actingAs($this->director)
            ->get(route('director.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('routePrefix', 'director')
                ->where('finance', false)
                ->etc()
            );
    }

    public function test_the_dashboard_only_counts_primary_classes_and_students(): void
    {
        Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->primaryClass->id,
        ]);
        Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->secondaryClass->id,
        ]);

        $this->actingAs($this->director)
            ->get(route('director.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.students', 1)
                ->where('stats.classes', 1)
            );
    }

    public function test_the_classes_page_only_lists_primary_classes(): void
    {
        $this->actingAs($this->director)
            ->get(route('director.classes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Classes/Index')
                ->has('classes', 1)
                ->where('classes.0.name', 'CI A')
            );
    }

    public function test_the_students_page_only_lists_primary_students(): void
    {
        Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->primaryClass->id,
        ]);
        Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->secondaryClass->id,
        ]);

        $this->actingAs($this->director)
            ->get(route('director.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Students/Index')
                ->has('students.data', 1)
                ->where('students.data.0.class_id', $this->primaryClass->id)
            );
    }

    public function test_the_director_cannot_reach_the_admin_dashboard(): void
    {
        $this->actingAs($this->director)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}

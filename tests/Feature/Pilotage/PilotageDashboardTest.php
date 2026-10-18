<?php

namespace Tests\Feature\Pilotage;

use App\Enums\EvaluationStatus;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Sale;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Tableau de bord de pilotage (phase 2).
 *
 * Vérifie que la carte de pilotage est exposée sur les tableaux de bord admin
 * et directeur, que les finance sont réservées aux rôles habilités, et que le
 * périmètre du directeur primaire est bien respecté.
 */
class PilotageDashboardTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $director;

    private AcademicYear $year;

    private AcademicPeriod $period;

    private ClassRoom $primaryClass;

    private ClassRoom $secondaryClass;

    private Subject $math;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        $this->admin = User::factory()->admin()->forSchool($this->school)->create();

        $this->director = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => Role::Director,
        ]);

        $this->year = AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'is_current' => true,
        ]);

        $this->period = AcademicPeriod::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'starts_at' => now()->startOfYear()->toDateString(),
            'ends_at' => now()->endOfYear()->toDateString(),
            'is_closed' => false,
        ]);

        $this->primaryClass = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'CM2 A',
            'level' => 'CM2',
        ]);

        $this->secondaryClass = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => '6ème A',
            'level' => '6ème',
        ]);

        $this->math = Subject::factory()->create(['school_id' => $this->school->id, 'name' => 'Mathématiques']);

        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
        ]);

        foreach ([$this->primaryClass, $this->secondaryClass] as $class) {
            $class->subjects()->sync([$this->math->id => ['default_coefficient' => 1]]);
        }
    }

    public function test_the_admin_dashboard_exposes_the_pilotage_panel_with_finance(): void
    {
        Sale::factory()->create([
            'school_id' => $this->school->id,
            'total' => 500000,
            'sold_at' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('pilotage')
                ->has('pilotage.effectifs')
                ->has('pilotage.performance')
                ->has('pilotage.administration')
                ->where('pilotage.finance.revenue', 500000)
                ->where('pilotage.effectifs.href', 'admin.students.index')
                ->etc()
            );
    }

    public function test_the_director_dashboard_is_scoped_to_primary_without_finance(): void
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
                ->where('pilotage.effectifs.students', 1)
                ->where('pilotage.effectifs.classes', 1)
                ->where('pilotage.finance', null)
                ->where('pilotage.effectifs.href', 'director.students.index')
                ->etc()
            );
    }

    public function test_the_panel_reports_academic_performance(): void
    {
        $good = $this->student($this->primaryClass);
        $struggling = $this->student($this->primaryClass);

        $this->grade($good, $this->primaryClass, 16);
        $this->grade($struggling, $this->primaryClass, 6);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pilotage.performance.available', true)
                ->where('pilotage.performance.average', 11)
                ->where('pilotage.performance.success_rate', 50)
                ->where('pilotage.performance.struggling', 1)
                ->where('pilotage.performance.evaluated', 2)
                ->etc()
            );
    }

    public function test_the_panel_counts_work_to_process(): void
    {
        Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->primaryClass->id,
        ]);

        Evaluation::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_period_id' => $this->period->id,
            'class_id' => $this->primaryClass->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher->id,
            'status' => EvaluationStatus::InProgress->value,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pilotage.administration.pending_evaluations', 1)
                ->where('pilotage.administration.pending_teachers', 1)
                ->etc()
            );
    }

    private function student(ClassRoom $class): Student
    {
        return Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
        ]);
    }

    private function grade(Student $student, ClassRoom $class, float $score): void
    {
        $evaluation = Evaluation::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_period_id' => $this->period->id,
            'class_id' => $class->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher->id,
            'max_score' => 20,
            'coefficient' => 1,
            'status' => EvaluationStatus::Validated->value,
            'validated_at' => now(),
        ]);

        $evaluation->results()->create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'score' => $score,
            'is_absent' => false,
        ]);
    }
}

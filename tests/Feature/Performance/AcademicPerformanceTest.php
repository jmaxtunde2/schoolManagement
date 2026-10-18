<?php

namespace Tests\Feature\Performance;

use App\Enums\EvaluationStatus;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Performance scolaire (phase 4).
 *
 * Vérifie les moyennes par classe/matière, le taux de réussite, les élèves en
 * difficulté, la comparaison des périodes et l'isolation multi-tenant.
 */
class AcademicPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private AcademicYear $year;

    private AcademicPeriod $periodOne;

    private AcademicPeriod $periodTwo;

    private ClassRoom $class;

    private Subject $math;

    private Subject $french;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->admin = User::factory()->admin()->forSchool($this->school)->create();

        $this->year = AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'is_current' => true,
        ]);

        $this->periodOne = AcademicPeriod::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'starts_at' => now()->startOfYear()->toDateString(),
            'ends_at' => now()->startOfYear()->addMonths(3)->toDateString(),
            'is_closed' => true,
        ]);

        $this->periodTwo = AcademicPeriod::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Trimestre 2',
            'position' => 2,
            'starts_at' => now()->startOfYear()->addMonths(3)->toDateString(),
            'ends_at' => now()->endOfYear()->toDateString(),
            'is_closed' => false,
        ]);

        $this->class = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'CM2 A',
            'level' => 'CM2',
        ]);

        $this->math = Subject::factory()->create(['school_id' => $this->school->id, 'name' => 'Mathématiques']);
        $this->french = Subject::factory()->create(['school_id' => $this->school->id, 'name' => 'Français']);

        $this->class->subjects()->sync([
            $this->math->id => ['default_coefficient' => 1],
            $this->french->id => ['default_coefficient' => 1],
        ]);

        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
        ]);
    }

    public function test_the_admin_sees_academic_performance_metrics(): void
    {
        $student = $this->student($this->class);
        $this->grade($student, $this->periodTwo, $this->math, 16);
        $this->grade($student, $this->periodTwo, $this->french, 8);

        $this->actingAs($this->admin)
            ->get(route('admin.performance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Performance/Index')
                ->where('academic.available', true)
                ->where('academic.overall.average', 12)
                ->where('academic.overall.success_rate', 100)
                ->has('academic.by_class', 1)
                ->where('academic.by_class.0.class_name', 'CM2 A')
                ->has('academic.by_subject', 2)
                // Matières triées croissantes : Français (8) avant Mathématiques (16).
                ->where('academic.by_subject.0.subject', 'Français')
                ->etc()
            );
    }

    public function test_struggling_students_are_listed(): void
    {
        $struggling = $this->student($this->class);
        $this->grade($struggling, $this->periodTwo, $this->math, 5);

        $this->actingAs($this->admin)
            ->get(route('admin.performance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('academic.overall.struggling', 1)
                ->has('academic.struggling_students', 1)
                ->where('academic.struggling_students.0.student', $struggling->full_name)
                ->etc()
            );
    }

    public function test_top_students_are_ranked(): void
    {
        $best = $this->student($this->class);
        $second = $this->student($this->class);
        $this->grade($best, $this->periodTwo, $this->math, 16);
        $this->grade($second, $this->periodTwo, $this->math, 12);

        $this->actingAs($this->admin)
            ->get(route('admin.performance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('academic.top_students', 2)
                ->where('academic.top_students.0.student', $best->full_name)
                ->where('academic.top_students.0.average', 16)
                ->where('academic.top_students.1.student', $second->full_name)
                ->etc()
            );
    }

    public function test_periods_are_compared(): void
    {
        $student = $this->student($this->class);
        $this->grade($student, $this->periodOne, $this->math, 8);
        $this->grade($student, $this->periodTwo, $this->math, 14);

        $this->actingAs($this->admin)
            ->get(route('admin.performance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('academic.evolution', 2)
                ->where('academic.evolution.0.name', 'Trimestre 1')
                ->where('academic.evolution.0.average', 8)
                ->where('academic.evolution.1.name', 'Trimestre 2')
                ->where('academic.evolution.1.average', 14)
                ->where('academic.evolution.1.is_current', true)
                ->etc()
            );
    }

    public function test_another_school_performance_never_leaks(): void
    {
        $otherSchool = School::factory()->create();
        $otherYear = AcademicYear::factory()->create(['school_id' => $otherSchool->id, 'is_current' => true]);
        $otherPeriod = AcademicPeriod::create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'starts_at' => now()->startOfYear()->toDateString(),
            'ends_at' => now()->endOfYear()->toDateString(),
            'is_closed' => false,
        ]);
        $otherClass = ClassRoom::factory()->create(['school_id' => $otherSchool->id, 'name' => 'Autre classe']);
        $otherClass->subjects()->sync([$this->math->id => ['default_coefficient' => 1]]);

        $otherStudent = Student::factory()->create([
            'school_id' => $otherSchool->id,
            'class_id' => $otherClass->id,
        ]);

        $evaluation = Evaluation::factory()->create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'academic_period_id' => $otherPeriod->id,
            'class_id' => $otherClass->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher->id,
            'max_score' => 20,
            'coefficient' => 1,
            'status' => EvaluationStatus::Validated->value,
            'validated_at' => now(),
        ]);
        $evaluation->results()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $otherStudent->id,
            'score' => 19,
            'is_absent' => false,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.performance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('academic.available', false)
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

    private function grade(Student $student, AcademicPeriod $period, Subject $subject, float $score): void
    {
        $evaluation = Evaluation::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_period_id' => $period->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
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

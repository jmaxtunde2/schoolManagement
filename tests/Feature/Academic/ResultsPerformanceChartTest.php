<?php

namespace Tests\Feature\Academic;

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
 * Onglet « Performance » de la page Résultats académiques.
 */
class ResultsPerformanceChartTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $yearOne;

    private AcademicYear $yearTwo;

    private ClassRoom $classA;

    private ClassRoom $classB;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->yearOne = AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'name' => '2025-2026',
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-08-31',
            'is_current' => true,
        ]);
        $this->yearTwo = AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'name' => '2026-2027',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-08-31',
            'is_current' => false,
        ]);
        $this->classA = ClassRoom::factory()->create(['school_id' => $this->school->id, 'name' => '6ème A']);
        $this->classB = ClassRoom::factory()->create(['school_id' => $this->school->id, 'name' => '6ème B']);
        $this->subject = Subject::factory()->create(['school_id' => $this->school->id, 'name' => 'Mathématiques']);
        $this->classA->subjects()->sync([$this->subject->id => ['default_coefficient' => 4]]);
        $this->classB->subjects()->sync([$this->subject->id => ['default_coefficient' => 4]]);
    }

    public function test_the_performance_tab_lists_the_average_per_period_for_the_whole_school(): void
    {
        $teacher = $this->teacher();
        $periodOne = $this->period($this->yearOne, 'Trimestre 1', 1);
        $periodTwo = $this->period($this->yearOne, 'Trimestre 2', 2);

        $studentA1 = $this->student($this->classA);
        $studentB1 = $this->student($this->classB);
        $evalA1 = $this->evaluation($periodOne, $this->classA, $teacher);
        $evalB1 = $this->evaluation($periodOne, $this->classB, $teacher);
        $this->score($evalA1, $studentA1, 14);
        $this->score($evalB1, $studentB1, 16);

        $studentA2 = $this->student($this->classA);
        $studentB2 = $this->student($this->classB);
        $evalA2 = $this->evaluation($periodTwo, $this->classA, $teacher);
        $evalB2 = $this->evaluation($periodTwo, $this->classB, $teacher);
        $this->score($evalA2, $studentA2, 10);
        $this->score($evalB2, $studentB2, 12);

        $this->actingAs(User::factory()->admin()->forSchool($this->school)->create())
            ->get('/admin/academic/results?tab=performance&perf_mode=periods')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Academic/Results')
                ->where('initialTab', 'performance')
                ->where('performance.mode', 'periods')
                ->where('performance.scope_label', 'Tout l’établissement')
                ->where('performance.academic_year', '2025-2026')
                ->where('performance.series.0.label', 'Trimestre 1')
                ->where('performance.series.0.average', 15)
                ->where('performance.series.0.distribution.graded', 2)
                ->where('performance.series.0.distribution.excellent', 1)
                ->where('performance.series.0.distribution.bien', 1)
                ->where('performance.series.0.distribution.success_rate', 100)
                ->where('performance.series.0.distribution.failure_rate', 0)
                ->where('performance.series.1.label', 'Trimestre 2')
                ->where('performance.series.1.average', 11)
                ->where('performance.series.1.distribution.assez', 1)
                ->where('performance.series.1.distribution.passable', 1)
                ->where('performance.series.1.distribution.success_rate', 100)
                ->where('performance.overall', 13));
    }

    public function test_the_distribution_summarises_mentions_and_failure_rate(): void
    {
        $teacher = $this->teacher();
        $period = $this->period($this->yearOne, 'Trimestre 1', 1);

        $studentA = $this->student($this->classA);
        $studentB = $this->student($this->classA);
        $this->score($this->evaluation($period, $this->classA, $teacher), $studentA, 8);
        $this->score($this->evaluation($period, $this->classA, $teacher), $studentB, 18);

        $this->actingAs(User::factory()->admin()->forSchool($this->school)->create())
            ->get('/admin/academic/results?tab=performance&perf_mode=periods&perf_class_id='.$this->classA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('performance.series.0.distribution.graded', 2)
                ->where('performance.series.0.distribution.echec', 1)
                ->where('performance.series.0.distribution.excellent', 1)
                ->where('performance.series.0.distribution.success', 1)
                ->where('performance.series.0.distribution.failure', 1)
                ->where('performance.series.0.distribution.success_rate', 50)
                ->where('performance.series.0.distribution.failure_rate', 50));
    }

    public function test_the_performance_can_be_limited_to_a_single_class(): void
    {
        $teacher = $this->teacher();
        $period = $this->period($this->yearOne, 'Trimestre 1', 1);

        $studentA = $this->student($this->classA);
        $studentB = $this->student($this->classB);
        $evalA = $this->evaluation($period, $this->classA, $teacher);
        $evalB = $this->evaluation($period, $this->classB, $teacher);
        $this->score($evalA, $studentA, 14);
        $this->score($evalB, $studentB, 16);

        $this->actingAs(User::factory()->admin()->forSchool($this->school)->create())
            ->get('/admin/academic/results?tab=performance&perf_mode=periods&perf_class_id='.$this->classB->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('performance.scope_label', '6ème B')
                ->where('performance.series.0.average', 16));
    }

    public function test_the_years_mode_compares_academic_years(): void
    {
        $teacher = $this->teacher();
        $periodOne = $this->period($this->yearOne, 'Trimestre 1', 1);
        $periodTwo = $this->period($this->yearOne, 'Trimestre 2', 2);
        $periodThree = $this->period($this->yearTwo, 'Trimestre 1', 1);

        $studentA1 = $this->student($this->classA);
        $studentB1 = $this->student($this->classB);
        $this->score($this->evaluation($periodOne, $this->classA, $teacher), $studentA1, 14);
        $this->score($this->evaluation($periodOne, $this->classB, $teacher), $studentB1, 16);

        $this->score($this->evaluation($periodTwo, $this->classA, $teacher), $studentA1, 10);
        $this->score($this->evaluation($periodTwo, $this->classB, $teacher), $studentB1, 12);

        $studentA2 = $this->student($this->classA);
        $this->score($this->evaluation($periodThree, $this->classA, $teacher), $studentA2, 18);

        $this->actingAs(User::factory()->admin()->forSchool($this->school)->create())
            ->get('/admin/academic/results?tab=performance&perf_mode=years')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('performance.mode', 'years')
                ->where('performance.series.0.label', '2025-2026')
                ->where('performance.series.0.average', 13)
                ->where('performance.series.0.distribution.graded', 2)
                ->where('performance.series.0.distribution.assez', 1)
                ->where('performance.series.0.distribution.bien', 1)
                ->where('performance.series.1.label', '2026-2027')
                ->where('performance.series.1.average', 18)
                ->where('performance.series.1.distribution.excellent', 1));
    }

    public function test_a_teacher_cannot_open_the_chart_for_an_unassigned_class(): void
    {
        $period = $this->period($this->yearOne, 'Trimestre 1', 1);
        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $teacher = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
        ]);
        $teacher->assignments()->create([
            'class_id' => $this->classA->id,
            'subject_id' => $this->subject->id,
        ]);

        $this->actingAs($teacherUser)
            ->get('/teacher/academic/results?class_id='.$this->classA->id.'&period_id='.$period->id.'&tab=performance&perf_mode=periods&perf_class_id='.$this->classB->id)
            ->assertForbidden();
    }

    public function test_the_results_tab_does_not_build_the_performance_payload(): void
    {
        $this->actingAs(User::factory()->admin()->forSchool($this->school)->create())
            ->get('/admin/academic/results')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('initialTab', 'results')
                ->where('performance', null));
    }

    private function teacher(): Teacher
    {
        $user = User::factory()->teacher()->forSchool($this->school)->create();

        return Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
        ]);
    }

    private function period(AcademicYear $year, string $name, int $position): AcademicPeriod
    {
        return AcademicPeriod::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'position' => $position,
        ]);
    }

    private function student(ClassRoom $classRoom): Student
    {
        return Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $classRoom->id,
        ]);
    }

    private function evaluation(AcademicPeriod $period, ClassRoom $classRoom, Teacher $teacher): Evaluation
    {
        return Evaluation::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $period->academic_year_id,
            'academic_period_id' => $period->id,
            'class_id' => $classRoom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacher->id,
            'max_score' => 20,
            'coefficient' => 4,
            'status' => EvaluationStatus::Validated->value,
            'validated_at' => now(),
        ]);
    }

    private function score(Evaluation $evaluation, Student $student, float $score): void
    {
        $evaluation->results()->create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'score' => $score,
            'is_absent' => false,
        ]);
    }
}

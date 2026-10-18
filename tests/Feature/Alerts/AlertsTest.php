<?php

namespace Tests\Feature\Alerts;

use App\Enums\EvaluationStatus;
use App\Enums\ExpenseStatus;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alertes intelligentes (phase 3).
 *
 * Vérifie la génération agrégée des alertes, la déduplication hebdomadaire,
 * la répartition selon les rôles et l'isolation multi-tenant.
 */
class AlertsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $director;

    private User $accountant;

    private AcademicYear $year;

    private AcademicPeriod $period;

    private ClassRoom $class;

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
        $this->accountant = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => Role::Accountant,
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

        $this->class = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'CM2 A',
            'level' => 'CM2',
        ]);
        $this->math = Subject::factory()->create(['school_id' => $this->school->id, 'name' => 'Mathématiques']);
        $this->class->subjects()->sync([$this->math->id => ['default_coefficient' => 1]]);

        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
        ]);
    }

    public function test_struggling_students_raise_an_aggregated_academic_alert(): void
    {
        $student = $this->student($this->class);
        $this->grade($student, 6);

        $this->artisan('corischool:generate-alerts')->assertSuccessful();

        $alert = UserNotification::query()
            ->where('school_id', $this->school->id)
            ->where('user_id', $this->admin->id)
            ->where('type', 'alert.academic')
            ->where('title', 'like', '%en difficulté')
            ->first();

        $this->assertNotNull($alert);
        $this->assertSame('high', $alert->data['priority']);
        $this->assertSame('admin.academic.results.index', $alert->data['link_route']);
    }

    public function test_alerts_are_delivered_to_admin_and_director_but_not_the_accountant(): void
    {
        $student = $this->student($this->class);
        $this->grade($student, 6);

        $this->artisan('corischool:generate-alerts')->assertSuccessful();

        $recipients = UserNotification::query()
            ->where('school_id', $this->school->id)
            ->where('type', 'alert.academic')
            ->pluck('user_id')
            ->all();

        $this->assertContains($this->admin->id, $recipients);
        $this->assertContains($this->director->id, $recipients);
        $this->assertNotContains($this->accountant->id, $recipients);
    }

    public function test_the_director_alert_links_to_the_director_route(): void
    {
        $student = $this->student($this->class);
        $this->grade($student, 6);

        $this->artisan('corischool:generate-alerts')->assertSuccessful();

        $alert = UserNotification::query()
            ->where('school_id', $this->school->id)
            ->where('user_id', $this->director->id)
            ->where('type', 'alert.academic')
            ->first();

        $this->assertSame('director.academic.results.index', $alert->data['link_route']);
    }

    public function test_a_budget_deficit_raises_a_finance_alert_for_admin_and_accountant(): void
    {
        Sale::factory()->create([
            'school_id' => $this->school->id,
            'total' => 100000,
            'sold_at' => now()->toDateString(),
        ]);
        Expense::factory()->create([
            'school_id' => $this->school->id,
            'amount' => 500000,
            'expense_date' => now()->toDateString(),
            'status' => ExpenseStatus::Approved,
        ]);

        $this->artisan('corischool:generate-alerts')->assertSuccessful();

        $recipients = UserNotification::query()
            ->where('school_id', $this->school->id)
            ->where('type', 'alert.finance')
            ->pluck('user_id')
            ->all();

        $this->assertContains($this->admin->id, $recipients);
        $this->assertContains($this->accountant->id, $recipients);
        $this->assertNotContains($this->director->id, $recipients);
    }

    public function test_alerts_are_deduplicated_and_not_re_emitted_without_force(): void
    {
        $student = $this->student($this->class);
        $this->grade($student, 6);

        $this->artisan('corischool:generate-alerts')->assertSuccessful();
        $countAfterFirstRun = UserNotification::query()
            ->where('school_id', $this->school->id)
            ->count();

        $this->artisan('corischool:generate-alerts')->assertSuccessful();
        $this->assertSame(
            $countAfterFirstRun,
            UserNotification::query()->where('school_id', $this->school->id)->count()
        );

        $this->artisan('corischool:generate-alerts --force')->assertSuccessful();
        $this->assertSame(
            $countAfterFirstRun,
            UserNotification::query()->where('school_id', $this->school->id)->count()
        );
    }

    public function test_alerts_never_leak_across_schools(): void
    {
        $student = $this->student($this->class);
        $this->grade($student, 6);

        $otherSchool = School::factory()->create();
        $otherAdmin = User::factory()->create([
            'school_id' => $otherSchool->id,
            'role' => Role::Admin,
        ]);

        $this->artisan('corischool:generate-alerts')->assertSuccessful();

        $this->assertSame(
            0,
            UserNotification::query()->where('user_id', $otherAdmin->id)->count()
        );
    }

    private function student(ClassRoom $class): Student
    {
        return Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
        ]);
    }

    private function grade(Student $student, float $score): void
    {
        $evaluation = Evaluation::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_period_id' => $this->period->id,
            'class_id' => $this->class->id,
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

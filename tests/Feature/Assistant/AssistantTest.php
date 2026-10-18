<?php

namespace Tests\Feature\Assistant;

use App\Enums\AttendanceStatus;
use App\Enums\EvaluationStatus;
use App\Enums\ExpenseStatus;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Expense;
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
 * CoriSchool Assistant — phase 1.
 *
 * Vérifie la sécurité multi-tenant, le périmètre du directeur primaire, la
 * restriction des données financières, et la justesse des réponses
 * structurées pour les principaux domaines.
 */
class AssistantTest extends TestCase
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

    private Subject $french;

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
        $this->french = Subject::factory()->create(['school_id' => $this->school->id, 'name' => 'Français']);

        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
        ]);

        foreach ([$this->primaryClass, $this->secondaryClass] as $class) {
            $class->subjects()->sync([
                $this->math->id => ['default_coefficient' => 2],
                $this->french->id => ['default_coefficient' => 2],
            ]);
        }
    }

    public function test_guests_cannot_reach_the_assistant(): void
    {
        $this->get(route('admin.assistant.index'))->assertRedirect(route('login'));
        $this->post(route('admin.assistant.ask'), ['question' => "Combien d'élèves ?"])->assertRedirect(route('login'));
    }

    public function test_the_assistant_is_restricted_to_admin_and_director(): void
    {
        $teacher = User::factory()->teacher()->forSchool($this->school)->create();

        $this->actingAs($teacher)->get(route('admin.assistant.index'))->assertForbidden();
        $this->actingAs($teacher)->post(route('admin.assistant.ask'), ['question' => 'Test'])->assertForbidden();
    }

    public function test_the_admin_can_open_the_assistant_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.assistant.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assistant/Index')
                ->where('routePrefix', 'admin')
                ->where('suggestions', fn ($suggestions) => collect($suggestions)->contains(
                    fn ($suggestion) => str_contains($suggestion, 'dépensé'),
                ))
            );
    }

    public function test_the_director_page_is_scoped_to_primary_without_finance_suggestions(): void
    {
        $this->actingAs($this->director)
            ->get(route('director.assistant.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assistant/Index')
                ->where('routePrefix', 'director')
                ->where('suggestions', fn ($suggestions) => collect($suggestions)->doesntContain(
                    fn ($suggestion) => str_contains($suggestion, 'dépensé'),
                ))
            );
    }

    public function test_student_count_never_leaks_data_from_another_school(): void
    {
        Student::factory()->count(3)->create([
            'school_id' => $this->school->id,
            'class_id' => $this->secondaryClass->id,
        ]);

        $otherSchool = School::factory()->create();
        $otherClass = ClassRoom::factory()->create(['school_id' => $otherSchool->id]);
        Student::factory()->count(5)->create([
            'school_id' => $otherSchool->id,
            'class_id' => $otherClass->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assistant.ask'), ['question' => "Combien avons-nous d'élèves ?"])
            ->assertOk()
            ->assertJsonPath('intent', 'student_count')
            ->assertJsonPath('metrics.0.value', 3);
    }

    public function test_the_director_only_counts_primary_students(): void
    {
        Student::factory()->count(2)->create([
            'school_id' => $this->school->id,
            'class_id' => $this->primaryClass->id,
        ]);
        Student::factory()->count(4)->create([
            'school_id' => $this->school->id,
            'class_id' => $this->secondaryClass->id,
        ]);

        $this->actingAs($this->director)
            ->postJson(route('director.assistant.ask'), ['question' => "Combien avons-nous d'élèves ?"])
            ->assertOk()
            ->assertJsonPath('intent', 'student_count')
            ->assertJsonPath('metrics.0.value', 2);
    }

    public function test_the_best_class_question_returns_the_highest_average(): void
    {
        $topA = $this->student($this->primaryClass);
        $topB = $this->student($this->primaryClass);
        $low = $this->student($this->secondaryClass);

        $this->grade($topA, $this->primaryClass, $this->math, 16);
        $this->grade($topB, $this->primaryClass, $this->math, 14);
        $this->grade($low, $this->secondaryClass, $this->math, 8);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assistant.ask'), ['question' => 'Quelle classe a la meilleure moyenne ?'])
            ->assertOk()
            ->assertJsonPath('intent', 'best_class')
            ->assertJsonPath('metrics.0.value', 'CM2 A')
            ->assertJsonPath('available', true);
    }

    public function test_students_below_average_are_listed(): void
    {
        $struggling = $this->student($this->primaryClass);
        $ok = $this->student($this->primaryClass);

        $this->grade($struggling, $this->primaryClass, $this->math, 6);
        $this->grade($ok, $this->primaryClass, $this->math, 15);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assistant.ask'), ['question' => "Combien d'élèves ont une moyenne inférieure à 10 ?"])
            ->assertOk()
            ->assertJsonPath('intent', 'students_below_average')
            ->assertJsonPath('metrics.0.value', 1)
            ->assertJsonPath('table.rows.0.student', $struggling->full_name);
    }

    public function test_academic_questions_report_missing_grades_instead_of_guessing(): void
    {
        $this->student($this->primaryClass);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assistant.ask'), ['question' => 'Quelle classe a la meilleure moyenne ?'])
            ->assertOk()
            ->assertJsonPath('intent', 'best_class')
            ->assertJsonPath('available', false);
    }

    public function test_weakest_subjects_are_reported(): void
    {
        $student = $this->student($this->primaryClass);
        $this->grade($student, $this->primaryClass, $this->math, 7);
        $this->grade($student, $this->primaryClass, $this->french, 15);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assistant.ask'), ['question' => 'Quelles matières présentent les plus faibles résultats ?'])
            ->assertOk()
            ->assertJsonPath('intent', 'weakest_subjects')
            ->assertJsonPath('table.rows.0.subject', 'Mathématiques');
    }

    public function test_pending_evaluations_are_reported(): void
    {
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
            ->postJson(route('admin.assistant.ask'), ['question' => "Quels enseignants n'ont pas encore saisi leurs notes ?"])
            ->assertOk()
            ->assertJsonPath('intent', 'pending_evaluations')
            ->assertJsonPath('metrics.0.value', 1);
    }

    public function test_admin_finance_questions_return_totals(): void
    {
        Sale::factory()->create([
            'school_id' => $this->school->id,
            'total' => 500000,
            'sold_at' => now()->toDateString(),
        ]);
        Expense::factory()->create([
            'school_id' => $this->school->id,
            'amount' => 200000,
            'expense_date' => now()->toDateString(),
            'status' => ExpenseStatus::Approved,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assistant.ask'), ['question' => 'Combien avons-nous de recettes ce mois-ci ?'])
            ->assertOk()
            ->assertJsonPath('intent', 'revenue')
            ->assertJsonPath('metrics.0.value', '500 000 FCFA');

        $this->actingAs($this->admin)
            ->postJson(route('admin.assistant.ask'), ['question' => 'Combien avons-nous dépensé ce mois-ci ?'])
            ->assertOk()
            ->assertJsonPath('intent', 'expenses')
            ->assertJsonPath('metrics.1.value', '200 000 FCFA');

        $this->actingAs($this->admin)
            ->postJson(route('admin.assistant.ask'), ['question' => 'Quel est notre solde actuel ?'])
            ->assertOk()
            ->assertJsonPath('intent', 'balance')
            ->assertJsonPath('metrics.2.value', '300 000 FCFA');
    }

    public function test_the_director_cannot_access_financial_data(): void
    {
        Sale::factory()->create([
            'school_id' => $this->school->id,
            'total' => 500000,
            'sold_at' => now()->toDateString(),
        ]);

        $this->actingAs($this->director)
            ->postJson(route('director.assistant.ask'), ['question' => 'Combien avons-nous de recettes ce mois-ci ?'])
            ->assertOk()
            ->assertJsonPath('intent', 'revenue')
            ->assertJsonPath('available', false)
            ->assertJsonPath('metrics', []);
    }

    public function test_repeated_absences_are_reported(): void
    {
        $student = $this->student($this->primaryClass);

        foreach (range(1, 3) as $day) {
            AttendanceRecord::create([
                'school_id' => $this->school->id,
                'student_id' => $student->id,
                'class_room_id' => $this->primaryClass->id,
                'academic_period_id' => $this->period->id,
                'attendance_date' => now()->startOfMonth()->addDays($day)->toDateString(),
                'status' => AttendanceStatus::Absent->value,
            ]);
        }

        $this->actingAs($this->admin)
            ->postJson(route('admin.assistant.ask'), ['question' => 'Quels élèves ont eu plusieurs absences ce mois-ci ?'])
            ->assertOk()
            ->assertJsonPath('intent', 'repeated_absences')
            ->assertJsonPath('metrics.0.value', 1)
            ->assertJsonPath('table.rows.0.absences', 3);
    }

    public function test_an_unrecognized_question_falls_back_to_help(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.assistant.ask'), ['question' => 'Bonjour'])
            ->assertOk()
            ->assertJsonPath('intent', 'help')
            ->assertJsonPath('available', true)
            ->assertJsonCount(7, 'list');
    }

    private function student(ClassRoom $class): Student
    {
        return Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
        ]);
    }

    private function grade(Student $student, ClassRoom $class, Subject $subject, float $score): void
    {
        $evaluation = Evaluation::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_period_id' => $this->period->id,
            'class_id' => $class->id,
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

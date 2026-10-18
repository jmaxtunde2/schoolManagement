<?php

namespace Tests\Feature\Students;

use App\Enums\AttendanceStatus;
use App\Enums\EvaluationStatus;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentFollowUp;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Suivi des élèves en difficulté (phase 5).
 */
class StudentFollowUpTest extends TestCase
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

        foreach ([$this->primaryClass, $this->secondaryClass] as $class) {
            $class->subjects()->sync([$this->math->id => ['default_coefficient' => 1]]);
        }

        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
        ]);
    }

    public function test_the_admin_sees_students_with_a_low_average(): void
    {
        $struggling = $this->student($this->primaryClass);
        $this->grade($struggling, 6);

        $this->actingAs($this->admin)
            ->get(route('admin.students.at-risk'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Students/AtRisk/Index')
                ->has('students', 1)
                ->where('students.0.student_id', $struggling->id)
                ->where('students.0.performance_level', 'red')
                ->etc()
            );
    }

    public function test_repeated_absences_are_flagged(): void
    {
        $student = $this->student($this->primaryClass);
        $this->grade($student, 14);

        foreach (range(1, 3) as $day) {
            AttendanceRecord::create([
                'school_id' => $this->school->id,
                'student_id' => $student->id,
                'class_room_id' => $this->primaryClass->id,
                'academic_period_id' => $this->period->id,
                'attendance_date' => now()->startOfYear()->addDays($day)->toDateString(),
                'status' => AttendanceStatus::Absent->value,
            ]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.students.at-risk'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('students', 1)
                ->where('students.0.attendance_level', 'red')
                ->where('students.0.absences', 3)
                ->etc()
            );
    }

    public function test_the_director_only_sees_primary_students(): void
    {
        $primary = $this->student($this->primaryClass);
        $secondary = $this->student($this->secondaryClass);
        $this->grade($primary, 6);
        $this->grade($secondary, 6);

        $this->actingAs($this->director)
            ->get(route('director.students.at-risk'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('students', 1)
                ->where('students.0.student_id', $primary->id)
                ->etc()
            );
    }

    public function test_the_director_cannot_open_a_secondary_student_folio(): void
    {
        $secondary = $this->student($this->secondaryClass);

        $this->actingAs($this->director)
            ->get(route('director.students.follow-up', $secondary->id))
            ->assertForbidden();
    }

    public function test_a_follow_up_action_can_be_recorded_and_appears_on_the_folio(): void
    {
        $student = $this->student($this->primaryClass);
        $this->grade($student, 6);

        $this->actingAs($this->admin)
            ->post(route('admin.students.follow-up.store', $student->id), [
                'type' => 'academic',
                'title' => 'Entretien avec les parents',
                'notes' => 'Prévoir une remise à niveau en mathématiques.',
                'status' => 'planned',
                'due_date' => now()->addWeek()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_follow_ups', [
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'title' => 'Entretien avec les parents',
            'status' => 'planned',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.students.follow-up', $student->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Students/AtRisk/Show')
                ->has('followUps', 1)
                ->where('followUps.0.title', 'Entretien avec les parents')
                ->etc()
            );
    }

    public function test_follow_up_actions_are_isolated_per_school(): void
    {
        $student = $this->student($this->primaryClass);

        StudentFollowUp::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'type' => 'academic',
            'title' => 'Action locale',
            'status' => 'planned',
        ]);

        $otherSchool = School::factory()->create();
        $otherClass = ClassRoom::factory()->create(['school_id' => $otherSchool->id]);
        $otherStudent = Student::factory()->create([
            'school_id' => $otherSchool->id,
            'class_id' => $otherClass->id,
        ]);

        $this->assertSame(
            0,
            StudentFollowUp::withoutGlobalScopes()->where('student_id', $otherStudent->id)->count()
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
            'class_id' => $student->class_id,
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

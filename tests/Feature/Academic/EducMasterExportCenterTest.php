<?php

namespace Tests\Feature\Academic;

use App\Actions\Academic\EducMaster\EducMasterStudentsExport;
use App\Enums\EvaluationStatus;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EducMasterExportCenterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $director;

    private AcademicYear $year;

    private AcademicPeriod $period;

    private ClassRoom $primaryClass;

    private ClassRoom $secondaryClass;

    private Subject $subject;

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
            'name' => '2026-2027',
        ]);
        $this->period = AcademicPeriod::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'starts_at' => now()->startOfYear()->toDateString(),
            'ends_at' => now()->endOfYear()->toDateString(),
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

        $this->subject = Subject::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'Mathématiques',
        ]);
        $this->primaryClass->subjects()->sync([
            $this->subject->id => ['default_coefficient' => 4],
        ]);
        $this->secondaryClass->subjects()->sync([
            $this->subject->id => ['default_coefficient' => 4],
        ]);

        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
        ]);

        $primaryStudent = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->primaryClass->id,
            'matricule' => 'P-001',
            'gender' => 'F',
        ]);
        Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->secondaryClass->id,
            'matricule' => 'S-001',
        ]);

        $evaluation = Evaluation::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_period_id' => $this->period->id,
            'class_id' => $this->primaryClass->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'max_score' => 20,
            'coefficient' => 1,
            'status' => EvaluationStatus::Validated->value,
            'validated_at' => now(),
        ]);
        $evaluation->results()->create([
            'school_id' => $this->school->id,
            'student_id' => $primaryStudent->id,
            'score' => 14,
            'is_absent' => false,
        ]);

        Timetable::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->primaryClass->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
        ]);
    }

    public function test_admin_sees_the_educmaster_export_center(): void
    {
        $this->actingAs($this->admin)
            ->get(route('educmaster.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/EducMaster/Index')
                ->has('classes', 2)
                ->where('summary.students.total', 2)
                ->where('summary.classes.total', 2)
                ->where('summary.evaluations.total', 1)
                ->where('summary.timetable.total', 1)
                ->etc()
            );
    }

    public function test_admin_can_export_students_as_excel(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('educmaster.students'));

        $response->assertOk();
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringContainsString(
            'educmaster_eleves',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_admin_can_export_every_dataset(): void
    {
        foreach (['students', 'classes', 'notes', 'timetable'] as $export) {
            $response = $this->actingAs($this->admin)
                ->get(route("educmaster.{$export}"));

            $response->assertOk();
            $this->assertStringContainsString(
                'attachment',
                (string) $response->headers->get('content-disposition')
            );
        }
    }

    public function test_director_is_limited_to_primary_classes(): void
    {
        $this->actingAs($this->director)
            ->get(route('educmaster.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/EducMaster/Index')
                ->has('classes', 1)
                ->where('classes.0.id', $this->primaryClass->id)
                ->where('summary.students.total', 1)
                ->where('summary.classes.total', 1)
                ->where('routePrefix', 'director')
                ->etc()
            );

        $this->actingAs($this->director)
            ->get(route('educmaster.students', ['class_id' => $this->secondaryClass->id]))
            ->assertForbidden();
    }

    public function test_exports_are_isolated_per_school(): void
    {
        $otherSchool = School::factory()->create();
        $otherClass = ClassRoom::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($this->admin)
            ->from(route('educmaster.index'))
            ->get(route('educmaster.students', ['class_id' => $otherClass->id]))
            ->assertSessionHasErrors('class_id');
    }

    public function test_teacher_cannot_access_educmaster_exports(): void
    {
        $teacher = User::factory()->teacher()->forSchool($this->school)->create();

        $this->actingAs($teacher)
            ->get(route('educmaster.index'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('educmaster.students'))
            ->assertForbidden();
    }

    public function test_students_export_maps_gender_and_guardian(): void
    {
        $export = new EducMasterStudentsExport($this->school);

        $rows = $export->collection()->map(fn ($student) => $export->map($student));
        $row = $rows->firstWhere(0, 'P-001');

        $this->assertSame('F', $row[4]);
        $this->assertSame('CM2 A', $row[5]);
        $this->assertSame('Actif', $row[7]);
    }
}

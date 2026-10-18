<?php

namespace Tests\Feature\Academic;

use App\Enums\EvaluationStatus;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EducMasterPreparationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicPeriod $period;

    private ClassRoom $classRoom;

    private Subject $subject;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->school = School::factory()->create(['name' => 'École EducMaster']);
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
        $this->classRoom = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'CM2 A',
        ]);
        $this->subject = Subject::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'Mathématiques',
        ]);
        $this->classRoom->subjects()->sync([
            $this->subject->id => ['default_coefficient' => 4],
        ]);
        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
        ]);

        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'school_name' => 'École EducMaster',
            'address' => 'Cotonou',
        ]);
    }

    private function verifiedAdmin(?School $school = null): User
    {
        $admin = User::factory()->admin()
            ->forSchool($school ?? $this->school)
            ->create(['two_factor_confirmed_at' => now()]);

        $this->actingAs($admin)->withSession([
            'two_factor_verified_at' => now()->timestamp,
        ]);

        return $admin;
    }

    private function student(array $attributes = []): Student
    {
        return Student::factory()->create(array_merge([
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
            'matricule' => 'ELV-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'first_name' => 'Jean',
            'last_name' => 'Test',
            'birth_date' => '2012-05-01',
            'gender' => 'M',
        ], $attributes));
    }

    private function validatedEvaluation(Student $student, float $score): void
    {
        $evaluation = Evaluation::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_period_id' => $this->period->id,
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
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

    public function test_preparation_page_reports_ready_data_for_a_complete_class(): void
    {
        $student = $this->student();
        $this->validatedEvaluation($student, 15);

        $this->verifiedAdmin();

        $this->get(route('admin.academic.results.prepare-educmaster', [
            'class_id' => $this->classRoom->id,
            'period_id' => $this->period->id,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Academic/EducMasterPreparation')
                ->where('report.ready', true)
                ->where('report.error_count', 0)
                ->where('report.student_count', 1)
                ->where('report.ranked_students', 1)
                ->where('classRoom.name', 'CM2 A')
                ->where('period.name', 'Trimestre 1'));
    }

    public function test_preparation_flags_missing_fields_and_duplicate_identities(): void
    {
        $this->student(['matricule' => 'DUP-001', 'birth_date' => null, 'gender' => null]);
        $this->student([
            'matricule' => 'DUP-002',
            'first_name' => 'Awa',
            'last_name' => 'Diallo',
            'birth_date' => '2012-03-03',
        ]);
        $this->student([
            'matricule' => 'DUP-003',
            'first_name' => 'Awa',
            'last_name' => 'Diallo',
            'birth_date' => '2012-03-03',
        ]);
        $this->student(['matricule' => null]);

        $this->verifiedAdmin();

        $response = $this->get(route('admin.academic.results.prepare-educmaster', [
            'class_id' => $this->classRoom->id,
            'period_id' => $this->period->id,
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Academic/EducMasterPreparation')
            ->where('report.ready', false)
            ->where('report.student_count', 4)
            ->where('report.error_count', function ($count) {
                return $count >= 1;
            })
            ->where('report.issues', function ($issues) {
                $types = collect($issues)->pluck('type');

                return $types->contains('duplicate_student')
                    && $types->contains('missing_matricule')
                    && $types->contains('missing_birth_date')
                    && $types->contains('missing_gender');
            }));
    }

    public function test_preparation_is_forbidden_for_a_class_outside_the_admin_school(): void
    {
        $otherSchool = School::factory()->create();
        $otherClass = ClassRoom::factory()->create([
            'school_id' => $otherSchool->id,
            'name' => 'Classe étrangère',
        ]);

        $this->verifiedAdmin();

        $this->get(route('admin.academic.results.prepare-educmaster', [
            'class_id' => $otherClass->id,
            'period_id' => $this->period->id,
        ]))->assertSessionHasErrors('class_id');
    }

    public function test_educmaster_export_route_returns_a_file_download(): void
    {
        $student = $this->student(['gender' => 'F']);
        $this->validatedEvaluation($student, 16);

        $this->verifiedAdmin();

        $response = $this->post(route('admin.academic.results.export-educmaster'), [
            'class_id' => $this->classRoom->id,
            'period_id' => $this->period->id,
        ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringContainsString(
            'educmaster',
            (string) $response->headers->get('content-disposition')
        );
    }
}

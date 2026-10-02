<?php

namespace Tests\Feature\Academic;

use App\Enums\EvaluationStatus;
use App\Enums\ReportCardStatus;
use App\Jobs\SendUserNotificationEmailJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\ParentGuardian;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Actions\Academic\GenerateReportCards;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportCardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private AcademicPeriod $period;
    private ClassRoom $classRoom;
    private Subject $subject;
    private Teacher $teacher;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->school = School::factory()->create(['name' => 'École Académie']);
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
            'name' => '3e A',
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
        $this->teacher->assignments()->create([
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
        ]);
        $this->student = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
            'first_name' => 'Jean',
            'last_name' => 'Test',
            'matricule' => 'ACAD-001',
        ]);
        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'school_name' => 'École Académie',
            'address' => 'Cotonou',
            'primary_color' => '#14532D',
        ]);
    }

    private function actingAsVerified(User $user): static
    {
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $this->withSession([
            'two_factor_verified_at' => now()->timestamp,
        ])->actingAs($user);
    }

    private function evaluation(
        float $score,
        EvaluationStatus $status = EvaluationStatus::Validated,
        bool $absent = false
    ): Evaluation {
        $evaluation = Evaluation::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_period_id' => $this->period->id,
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'max_score' => 20,
            'coefficient' => 1,
            'status' => $status->value,
            'validated_at' => $status === EvaluationStatus::Validated ? now() : null,
        ]);
        $evaluation->results()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'score' => $absent ? null : $score,
            'is_absent' => $absent,
        ]);

        return $evaluation;
    }

    public function test_only_validated_scores_generate_versioned_snapshot_published_to_own_parent(): void
    {
        $this->evaluation(16);
        $this->evaluation(20, EvaluationStatus::Draft);
        $this->evaluation(0, EvaluationStatus::Validated, true);
        AttendanceRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'class_room_id' => $this->classRoom->id,
            'academic_period_id' => $this->period->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'absent',
            'justified_at' => now(),
        ]);

        $admin = User::factory()->admin()->forSchool($this->school)->create();
        $this->actingAsVerified($admin);
        $result = app(GenerateReportCards::class)->handle(
            $this->school,
            $this->classRoom,
            $this->period,
            $admin->id
        );

        $this->assertSame(1, $result['generated']);
        $this->assertSame(0, $result['skipped']);
        $reportCard = $result['report_cards'][0];
        $this->assertSame(16.0, (float) $reportCard->general_average);
        $this->assertSame(4.0, (float) $reportCard->items->first()->coefficient);
        $this->assertSame('École Académie', data_get($reportCard->snapshot, 'school.name'));
        $this->assertSame('ACAD-001', data_get($reportCard->snapshot, 'student.matricule'));
        $this->assertSame(1, data_get($reportCard->attendance_summary, 'absences'));
        $this->assertSame(1, data_get($reportCard->attendance_summary, 'justified_absences'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'report_card.generated']);

        $this->actingAsVerified(User::factory()->admin()->forSchool($this->school)->create())
            ->get('/admin/report-cards')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Academic/ReportCards/Index')
                ->has('reportCards.data', 1)
                ->missing('reportCards.data.0.verification_token')
                ->missing('reportCards.data.0.pdf_path')
                ->missing('reportCards.data.0.snapshot'));

        $unassignedClass = ClassRoom::factory()->create(['school_id' => $this->school->id]);
        $otherStudent = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $unassignedClass->id,
        ]);
        ReportCard::create([
            'school_id' => $this->school->id,
            'student_id' => $otherStudent->id,
            'class_room_id' => $unassignedClass->id,
            'academic_year_id' => $this->year->id,
            'academic_period_id' => $this->period->id,
            'status' => ReportCardStatus::Generated,
            'version' => 1,
            'verification_token' => str()->random(64),
        ]);

        $this->actingAsVerified($this->teacher->user)
            ->get('/teacher/report-cards')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Academic/ReportCards/Index')
                ->where('reportCards.total', 1)
                ->where('classes.0.id', $this->classRoom->id));

        $item = $reportCard->items->first();
        $this->actingAsVerified($this->teacher->user)
            ->put(route('teacher.report-cards.comments.update', $reportCard->id), [
                'appreciation' => 'Bon trimestre.',
                'items' => [[
                    'id' => $item->id,
                    'teacher_comment' => 'Travail régulier.',
                    'appreciation' => 'Très satisfaisant',
                ]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Travail régulier.', $item->fresh()->teacher_comment);

        $guardianUser = User::factory()->forSchool($this->school)->create(['role' => 'parent']);
        $guardian = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $guardianUser->id,
        ]);
        $guardian->students()->attach($this->student->id, ['relationship' => 'parent']);

        $censeur = User::factory()->forSchool($this->school)->create(['role' => 'censeur']);
        $this->actingAsVerified($censeur)
            ->post(route('censeur.report-cards.publish', $reportCard->id))
            ->assertRedirect(route('two-factor.challenge', [
                'purpose' => 'publish_report_card',
                'return' => route('censeur.dashboard', [], false),
            ]));

        $this->withSession([
            'two_factor_sensitive.publish_report_card' => now()->timestamp,
        ])->actingAs($censeur)
            ->post(route('censeur.report-cards.publish', $reportCard->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(ReportCardStatus::Published, $reportCard->fresh()->status);
        Queue::assertPushed(SendUserNotificationEmailJob::class);

        $this->actingAsVerified($this->teacher->user)
            ->put(route('teacher.report-cards.comments.update', $reportCard->id), [
                'appreciation' => 'Modification interdite.',
                'items' => [[
                    'id' => $item->id,
                    'teacher_comment' => 'Ne pas écraser le bulletin publié.',
                ]],
            ])
            ->assertForbidden();

        $this->actingAsVerified($guardianUser)
            ->get(route('parent.children.report-cards', $this->student->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/ReportCards/Index')
                ->has('reportCards', 1));

        $this->actingAsVerified($guardianUser)
            ->get(route('parent.report-cards.show', $reportCard->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Academic/ReportCards/Show')
                ->where('reportCard.id', $reportCard->id)
                ->where('reportCard.status', 'published'));

        $pdf = $this->actingAsVerified($guardianUser)
            ->get(route('parent.report-cards.pdf', $reportCard->id));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $pdfPath = $reportCard->fresh()->pdf_path;
        $this->assertNotEmpty($pdfPath);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($pdfPath));

        $validatedEvaluation = Evaluation::where('school_id', $this->school->id)
            ->where('status', EvaluationStatus::Validated->value)
            ->firstOrFail();
        $validatedEvaluation->results()->where('student_id', $this->student->id)->update(['score' => 18]);

        $this->actingAsVerified($censeur)
            ->post(route('censeur.report-cards.regenerate', $reportCard->id))
            ->assertRedirect(route('two-factor.challenge', [
                'purpose' => 'regenerate_report_card',
                'return' => route('censeur.dashboard', [], false),
            ]));

        $this->withSession([
            'two_factor_sensitive.regenerate_report_card' => now()->timestamp,
        ])->actingAs($censeur)
            ->post(route('censeur.report-cards.regenerate', $reportCard->id))
            ->assertRedirect();

        $newVersion = ReportCard::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->where('student_id', $this->student->id)
            ->where('academic_period_id', $this->period->id)
            ->where('version', 2)
            ->firstOrFail();
        $this->assertSame(ReportCardStatus::Generated, $newVersion->status);
        $this->assertSame(18.0, (float) $newVersion->general_average);
        $this->assertSame(ReportCardStatus::Published, $reportCard->fresh()->status);
        $this->assertSame(16.0, (float) $reportCard->fresh()->general_average);
        $this->assertSame(16.0, (float) data_get($reportCard->fresh()->items->first(), 'average'));

        $this->actingAsVerified($censeur);
        $regenerated = app(GenerateReportCards::class)->handle(
            $this->school,
            $this->classRoom,
            $this->period,
            $censeur->id,
            $this->student->id,
            true
        )['report_cards'][0];
        $this->assertSame(2, $regenerated->version);
        $this->assertSame(ReportCardStatus::Published, $reportCard->fresh()->status);
        $this->assertSame(1, $reportCard->fresh()->version);
        $this->assertSame('Travail régulier.', $reportCard->fresh()->items->first()->teacher_comment);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report_card.regenerated']);

        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->create(['school_id' => $otherSchool->id]);
        $otherClass = ClassRoom::factory()->create(['school_id' => $otherSchool->id]);
        $otherYear = AcademicYear::factory()->create([
            'school_id' => $otherSchool->id,
            'is_current' => true,
        ]);
        $otherPeriod = AcademicPeriod::create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'name' => 'Période étrangère',
            'position' => 1,
        ]);
        $otherReportCard = ReportCard::create([
            'school_id' => $otherSchool->id,
            'student_id' => $otherStudent->id,
            'class_room_id' => $otherClass->id,
            'academic_year_id' => $otherYear->id,
            'academic_period_id' => $otherPeriod->id,
            'status' => ReportCardStatus::Published,
            'verification_token' => str()->random(64),
        ]);

        $this->actingAsVerified($guardianUser)
            ->get(route('parent.report-cards.show', $otherReportCard->id))
            ->assertNotFound();
    }

    public function test_secretary_can_generate_but_cannot_publish_report_cards(): void
    {
        $this->evaluation(13);
        $secretary = User::factory()->forSchool($this->school)->create([
            'role' => 'secretary',
        ]);

        $this->actingAsVerified($secretary)
            ->post('/teacher/academic/report-cards/generate', [
                'class_id' => $this->classRoom->id,
                'period_id' => $this->period->id,
            ])
            ->assertSessionHasNoErrors();

        $reportCard = ReportCard::where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame(ReportCardStatus::Generated, $reportCard->status);

        $this->actingAsVerified($secretary)
            ->post("/teacher/report-cards/{$reportCard->id}/publish")
            ->assertNotFound();
    }
}
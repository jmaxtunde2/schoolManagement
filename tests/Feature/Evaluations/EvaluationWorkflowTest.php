<?php

namespace Tests\Feature\Evaluations;

use App\Jobs\SendSmsNotificationJob;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\TwoFactor\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EvaluationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Teacher $teacher;

    private ClassRoom $class;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::factory()->create();
        AcademicYear::factory()->create(['school_id' => $this->school->id, 'is_current' => true]);
        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create(['school_id' => $this->school->id, 'user_id' => $teacherUser->id]);
        $this->class = ClassRoom::factory()->create(['school_id' => $this->school->id]);
        $this->subject = Subject::factory()->create(['school_id' => $this->school->id]);
        $this->teacher->assignments()->create(['class_id' => $this->class->id, 'subject_id' => $this->subject->id]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'title' => 'Devoir 1',
            'type' => 'devoir',
            'evaluation_date' => now()->format('Y-m-d'),
            'max_score' => 20,
            'coefficient' => 2,
        ], $overrides);
    }

    private function submittedEvaluation(): Evaluation
    {
        return Evaluation::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->school->academicYears()->value('id'),
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'in_progress',
            'submitted_by' => $this->teacher->user_id,
            'submitted_at' => now(),
        ]);
    }

    private function actingAsTwoFactorVerified(User $user): static
    {
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $this->withSession([
            'two_factor_verified_at' => now()->timestamp,
        ])->actingAs($user);
    }

    /**
     * La soumission est une action sensible : elle exige une
     * revalidation 2FA récente en plus de la session vérifiée.
     */
    private function actingAsTwoFactorVerifiedFor(string $purpose, User $user): static
    {
        $this->actingAsTwoFactorVerified($user);

        return $this->markSensitiveTwoFactorVerified($purpose);
    }

    public function test_teacher_can_create_and_save_a_draft(): void
    {
        $students = Student::factory()->count(2)->create(['school_id' => $this->school->id, 'class_id' => $this->class->id]);

        $this->actingAs($this->teacher->user)->post('/teacher/evaluations', $this->payload());
        $evaluation = Evaluation::firstOrFail();

        $this->actingAs($this->teacher->user)->put("/teacher/evaluations/{$evaluation->id}/grades", [
            'results' => [
                ['student_id' => $students[0]->id, 'score' => 15, 'is_absent' => false],
                ['student_id' => $students[1]->id, 'score' => 12, 'is_absent' => false],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame('draft', $evaluation->fresh()->status->value);
        $this->assertSame($this->teacher->user->id, $evaluation->fresh()->entered_by);
    }

    public function test_teacher_submits_only_after_all_grades_are_entered(): void
    {
        $students = Student::factory()->count(2)->create(['school_id' => $this->school->id, 'class_id' => $this->class->id]);
        $this->actingAs($this->teacher->user)->post('/teacher/evaluations', $this->payload());
        $evaluation = Evaluation::firstOrFail();

        $this->teacher->user->forceFill(['google_reauthenticated_at' => now()])->save();
        $this->actingAs($this->teacher->user)
            ->post("/teacher/evaluations/{$evaluation->id}/submit")
            ->assertSessionHas('error');

        $this->assertSame('draft', $evaluation->fresh()->status->value);
    }

    public function test_submission_moves_evaluation_to_in_progress_and_locks_teacher(): void
    {
        $student = Student::factory()->create(['school_id' => $this->school->id, 'class_id' => $this->class->id]);
        $this->actingAs($this->teacher->user)->post('/teacher/evaluations', $this->payload());
        $evaluation = Evaluation::firstOrFail();
        $this->actingAs($this->teacher->user)->put("/teacher/evaluations/{$evaluation->id}/grades", [
            'results' => [['student_id' => $student->id, 'score' => 14, 'is_absent' => false]],
        ]);

        $this->teacher->user->forceFill(['google_reauthenticated_at' => now()])->save();
        $this->actingAsTwoFactorVerifiedFor('submit_evaluation', $this->teacher->user)
            ->post("/teacher/evaluations/{$evaluation->id}/submit")
            ->assertSessionHasNoErrors();

        $evaluation->refresh();
        $this->assertSame('in_progress', $evaluation->status->value);
        $this->assertSame($this->teacher->user->id, $evaluation->submitted_by);

        $this->actingAs($this->teacher->user)->put("/teacher/evaluations/{$evaluation->id}/grades", [
            'results' => [['student_id' => $student->id, 'score' => 18, 'is_absent' => false]],
        ])->assertForbidden();
    }

    public function test_only_admin_or_censeur_can_validate_after_submission(): void
    {
        Queue::fake();
        $student = Student::factory()->create(['school_id' => $this->school->id, 'class_id' => $this->class->id]);
        $guardian = ParentGuardian::factory()->create(['school_id' => $this->school->id, 'phone' => '+22997000000']);
        $guardian->students()->attach($student->id, ['relationship' => 'parent']);

        $this->actingAs($this->teacher->user)->post('/teacher/evaluations', $this->payload());
        $evaluation = Evaluation::firstOrFail();
        $this->actingAs($this->teacher->user)->put("/teacher/evaluations/{$evaluation->id}/grades", [
            'results' => [['student_id' => $student->id, 'score' => 14, 'is_absent' => false]],
        ]);
        $this->teacher->user->forceFill(['google_reauthenticated_at' => now()])->save();
        $this->actingAsTwoFactorVerifiedFor('submit_evaluation', $this->teacher->user)
            ->post("/teacher/evaluations/{$evaluation->id}/submit");

        $censeur = User::factory()->forSchool($this->school)->create(['role' => 'censeur']);
        $censeur->forceFill(['google_reauthenticated_at' => now()])->save();
        $this->actingAsTwoFactorVerifiedFor('validate_evaluation', $censeur)
            ->post("/censeur/evaluations/{$evaluation->id}/validate")
            ->assertSessionHasNoErrors();

        $this->assertSame('validated', $evaluation->fresh()->status->value);
        $this->assertSame($censeur->id, $evaluation->fresh()->validated_by);
        Queue::assertPushed(SendSmsNotificationJob::class);
    }

    public function test_teacher_cannot_validate_own_evaluation(): void
    {
        $this->actingAs($this->teacher->user)->post('/teacher/evaluations', $this->payload());
        $evaluation = Evaluation::firstOrFail();
        $this->actingAs($this->teacher->user)->post("/teacher/evaluations/{$evaluation->id}/validate")->assertNotFound();
    }

    public function test_admin_can_return_a_submitted_evaluation_with_a_reason(): void
    {
        $evaluation = $this->submittedEvaluation();
        $admin = User::factory()->forSchool($this->school)->create(['role' => 'admin']);

        $this->actingAsTwoFactorVerified($admin)
            ->post("/admin/evaluations/{$evaluation->id}/return", [
                'reason' => 'Veuillez corriger les notes manquantes.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $evaluation->refresh();
        $this->assertSame('returned', $evaluation->status->value);
        $this->assertSame($admin->id, $evaluation->returned_by);
        $this->assertSame('Veuillez corriger les notes manquantes.', $evaluation->return_reason);
    }

    public function test_censeur_must_provide_a_reason_to_return_an_evaluation(): void
    {
        $evaluation = $this->submittedEvaluation();
        $censeur = User::factory()->forSchool($this->school)->create(['role' => 'censeur']);

        $this->actingAsTwoFactorVerified($censeur)
            ->post("/censeur/evaluations/{$evaluation->id}/return", ['reason' => ''])
            ->assertRedirect()
            ->assertSessionHasErrors('reason');

        $this->assertSame('in_progress', $evaluation->fresh()->status->value);

        $this->actingAsTwoFactorVerified($censeur)
            ->post("/censeur/evaluations/{$evaluation->id}/return", [
                'reason' => 'Le barème doit être corrigé.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('returned', $evaluation->fresh()->status->value);
    }

    public function test_sensitive_action_resumes_after_successful_two_factor_verification(): void
    {
        Queue::fake();

        $evaluation = $this->submittedEvaluation();
        $student = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
        ]);
        $evaluation->results()->create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'score' => 14,
        ]);

        $admin = User::factory()->forSchool($this->school)->create(['role' => 'admin']);
        $this->actingAsTwoFactorVerified($admin);

        $this->mock(TwoFactorService::class, function ($twoFactor) {
            $twoFactor->shouldReceive('loginVerified')
                ->andReturnUsing(fn ($request) => $request->session()->has('two_factor_verified_at')
                );
            $twoFactor->shouldReceive('sensitiveVerified')
                ->andReturnUsing(fn ($request, $purpose) => $request->session()->has('two_factor_sensitive.'.$purpose)
                );
            $twoFactor->shouldReceive('verify')->once()->andReturnTrue();
            $twoFactor->shouldReceive('markSensitiveVerified')
                ->once()
                ->andReturnUsing(function ($request, $purpose) {
                    $request->session()->put(
                        'two_factor_sensitive.'.$purpose,
                        now()->timestamp
                    );
                });
        });

        $return = "/admin/evaluations/{$evaluation->id}/grades";
        $this->withHeader('referer', $return)
            ->post("/admin/evaluations/{$evaluation->id}/validate")
            ->assertRedirect(route('two-factor.challenge', [
                'purpose' => 'validate_evaluation',
                'return' => $return,
            ]));

        $this->assertSame('in_progress', $evaluation->fresh()->status->value);

        $this->post(route('two-factor.verify'), [
            'code' => '123456',
            'purpose' => 'validate_evaluation',
            'return' => $return,
        ])->assertRedirect(route('two-factor.challenge', [
            'purpose' => 'validate_evaluation',
            'return' => $return,
        ]));

        $this->get(route('two-factor.challenge', [
            'purpose' => 'validate_evaluation',
            'return' => $return,
        ]))->assertInertia(fn ($page) => $page
            ->component('Auth/TwoFactor/Challenge')
            ->where('pendingAction.url', "/admin/evaluations/{$evaluation->id}/validate")
            ->where('pendingAction.method', 'post'));

        $this->post("/admin/evaluations/{$evaluation->id}/validate")
            ->assertRedirect($return);

        $this->assertSame('validated', $evaluation->fresh()->status->value);
    }

    public function test_censeur_dashboard_links_to_censeur_evaluation_routes(): void
    {
        $censeur = User::factory()->forSchool($this->school)->create([
            'role' => 'censeur',
        ]);

        $this->actingAsTwoFactorVerified($censeur)
            ->get('/censeur/dashboard')
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->where('routePrefix', 'censeur'));

        $evaluation = $this->submittedEvaluation();

        $this->actingAsTwoFactorVerified($censeur)
            ->get("/censeur/evaluations/{$evaluation->id}/grades")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Teacher/Evaluations/Grades')
                ->where('evaluation.can_return', true));
    }
}

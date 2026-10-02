<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationStatus;
use App\Jobs\SendSmsNotificationJob;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use App\Services\Sms\EsmsAfricaProvider;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\SmsResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvaluationWithGuardian(School $school): array
    {
        $class = ClassRoom::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teacherUser = User::factory()->teacher()->forSchool($school)->create();
        $teacher = Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $teacherUser->id]);
        $student = Student::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $guardian = ParentGuardian::factory()->create(['school_id' => $school->id, 'phone' => '+22900000001']);
        $guardian->students()->attach($student->id);

        $evaluation = Evaluation::factory()->validated()->create([
            'school_id' => $school->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
        ]);
        $evaluation->results()->create(['school_id' => $school->id, 'student_id' => $student->id, 'score' => 15]);

        return compact('evaluation', 'student', 'guardian');
    }

    public function test_notification_service_creates_one_notification_per_guardian_with_a_phone(): void
    {
        Queue::fake();
        $school = School::factory()->create();
        ['evaluation' => $evaluation, 'guardian' => $guardian] = $this->makeEvaluationWithGuardian($school);

        $created = app(NotificationService::class)->notifyForEvaluation($evaluation);

        $this->assertSame(1, $created);
        $notification = Notification::withoutGlobalScopes()->first();
        $this->assertSame($guardian->id, $notification->parent_id);
        $this->assertSame(NotificationStatus::Pending, $notification->status);
        Queue::assertPushed(SendSmsNotificationJob::class);
    }

    public function test_notification_service_skips_students_without_any_guardian_phone(): void
    {
        $school = School::factory()->create();
        $class = ClassRoom::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teacherUser = User::factory()->teacher()->forSchool($school)->create();
        $teacher = Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $teacherUser->id]);
        $student = Student::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]); // aucun parent associé

        $evaluation = Evaluation::factory()->validated()->create([
            'school_id' => $school->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
        ]);
        $evaluation->results()->create(['school_id' => $school->id, 'student_id' => $student->id, 'score' => 15]);

        $created = app(NotificationService::class)->notifyForEvaluation($evaluation);

        $this->assertSame(0, $created);
    }

    public function test_send_sms_job_marks_notification_sent_on_provider_success(): void
    {
        $this->mock(SmsProviderInterface::class, function ($mock) {
            $mock->shouldReceive('send')->once()->andReturn(SmsResponse::success('ext_123'));
            $mock->shouldReceive('name')->andReturn('fake');
        });

        $school = School::factory()->create();
        ['evaluation' => $evaluation] = $this->makeEvaluationWithGuardian($school);
        app(NotificationService::class)->notifyForEvaluation($evaluation);

        $notification = Notification::withoutGlobalScopes()->first();

        $notification->refresh();
        $this->assertSame(NotificationStatus::Sent, $notification->status);
        $this->assertSame('ext_123', $notification->external_id);
        $this->assertNotNull($notification->sent_at);
    }

    public function test_send_sms_job_marks_notification_failed_on_provider_error(): void
    {
        $this->mock(SmsProviderInterface::class, function ($mock) {
            $mock->shouldReceive('send')->once()->andReturn(SmsResponse::failure('Numéro invalide'));
            $mock->shouldReceive('name')->andReturn('fake');
        });

        $school = School::factory()->create();
        ['evaluation' => $evaluation] = $this->makeEvaluationWithGuardian($school);
        app(NotificationService::class)->notifyForEvaluation($evaluation);

        $notification = Notification::withoutGlobalScopes()->first();

        $notification->refresh();
        $this->assertSame(NotificationStatus::Failed, $notification->status);
        $this->assertSame('Numéro invalide', $notification->error);
    }

    public function test_esms_africa_sends_json_with_bearer_token(): void
    {
        config([
            'services.sms.token' => 'fake-esms-token',
            'services.sms.api_url' => 'https://sms.esmsafrica.io',
        ]);

        Http::fake([
            'https://sms.esmsafrica.io/api/messages/send' => Http::response([
                'id' => 'message-123',
            ], 200),
        ]);

        $response = (new EsmsAfricaProvider)->send(
            '+254712432109',
            'Hello from eSMS!'
        );

        $this->assertTrue($response->success);
        $this->assertSame('message-123', $response->externalId);

        Http::assertSent(fn (HttpRequest $request) =>
            $request->url() === 'https://sms.esmsafrica.io/api/messages/send'
            && $request->hasHeader('Authorization', 'Bearer fake-esms-token')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request['to'] === '+254712432109'
            && $request['text'] === 'Hello from eSMS!'
            && ! array_key_exists('sender_id', $request->data())
        );
    }

    public function test_admin_can_view_notification_history_scoped_to_their_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        ['evaluation' => $evalA] = $this->makeEvaluationWithGuardian($schoolA);
        ['evaluation' => $evalB] = $this->makeEvaluationWithGuardian($schoolB);
        app(NotificationService::class)->notifyForEvaluation($evalA);
        app(NotificationService::class)->notifyForEvaluation($evalB);

        $admin = User::factory()->admin()->forSchool($schoolA)->create();

        $this->actingAs($admin)->get('/admin/notifications')
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Notifications/Index')->has('notifications.data', 1));
    }

    public function test_admin_can_resend_a_failed_notification_only(): void
    {
        Queue::fake();
        $school = School::factory()->create();
        ['evaluation' => $evaluation] = $this->makeEvaluationWithGuardian($school);
        app(NotificationService::class)->notifyForEvaluation($evaluation);
        $notification = Notification::withoutGlobalScopes()->first();
        $notification->update(['status' => NotificationStatus::Failed->value]);
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)->post("/admin/notifications/{$notification->id}/resend")
            ->assertRedirect();

        Queue::assertPushed(SendSmsNotificationJob::class, 2); // envoi initial + relance

        $notification->update(['status' => NotificationStatus::Sent->value]);
        $this->actingAs($admin)->post("/admin/notifications/{$notification->id}/resend")->assertForbidden();
    }

    public function test_teacher_cannot_access_notification_history(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->teacher()->forSchool($school)->create();

        $this->actingAs($teacher)->get('/admin/notifications')->assertForbidden();
    }
}

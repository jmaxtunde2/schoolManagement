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
use App\Services\Sms\MetaWhatsAppProvider;
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

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://sms.esmsafrica.io/api/messages/send'
            && $request->hasHeader('Authorization', 'Bearer fake-esms-token')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request['to'] === '+254712432109'
            && $request['text'] === 'Hello from eSMS!'
            && ! array_key_exists('sender_id', $request->data())
        );
    }

    public function test_whatsapp_provider_is_resolved_when_sms_provider_is_whatsapp(): void
    {
        config(['services.sms.provider' => 'whatsapp']);

        $this->assertInstanceOf(MetaWhatsAppProvider::class, app(SmsProviderInterface::class));
    }

    public function test_meta_whatsapp_sends_text_message_with_bearer_token(): void
    {
        config([
            'services.whatsapp.token' => 'fake-whatsapp-token',
            'services.whatsapp.phone_number_id' => '106732507566763',
        ]);

        Http::fake([
            'https://graph.facebook.com/v21.0/106732507566763/messages' => Http::response([
                'messages' => [['id' => 'wamid.HBg...+22890000001']],
            ], 200),
        ]);

        $response = (new MetaWhatsAppProvider)->send('90000001', 'CoriSchool : note de Eya en Maths (Devoir) : 15.0/20.');

        $this->assertTrue($response->success);
        $this->assertSame('wamid.HBg...+22890000001', $response->externalId);

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://graph.facebook.com/v21.0/106732507566763/messages'
            && $request->hasHeader('Authorization', 'Bearer fake-whatsapp-token')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request['messaging_product'] === 'whatsapp'
            && $request['to'] === '+22890000001'
            && $request['type'] === 'text'
            && $request['text']['body'] === 'CoriSchool : note de Eya en Maths (Devoir) : 15.0/20.'
        );
    }

    public function test_meta_whatsapp_normalizes_local_and_international_numbers_to_e164(): void
    {
        config([
            'services.whatsapp.token' => 'fake-whatsapp-token',
            'services.whatsapp.phone_number_id' => '106732507566763',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ok']]], 200),
        ]);

        foreach (['90000001', '090000001', '22890000001', '+22890000001', '0022890000001'] as $input) {
            (new MetaWhatsAppProvider)->send($input, 'Bonjour');
        }

        $destinations = collect(Http::recorded())
            ->map(fn (array $pair) => $pair[0]['to'])
            ->all();

        $this->assertSame(
            ['+22890000001', '+22890000001', '+22890000001', '+22890000001', '+22890000001'],
            $destinations
        );
    }

    public function test_meta_whatsapp_returns_failure_when_credentials_are_missing(): void
    {
        config(['services.whatsapp.token' => null, 'services.whatsapp.phone_number_id' => null]);

        $response = (new MetaWhatsAppProvider)->send('90000001', 'Bonjour');

        $this->assertFalse($response->success);
        $this->assertStringContainsString('WHATSAPP_TOKEN is not configured.', (string) $response->error);
    }

    public function test_meta_whatsapp_returns_failure_on_graph_api_error(): void
    {
        config([
            'services.whatsapp.token' => 'fake-whatsapp-token',
            'services.whatsapp.phone_number_id' => '106732507566763',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Invalid OAuth access token.', 'code' => 190],
            ], 400),
        ]);

        $response = (new MetaWhatsAppProvider)->send('+22890000001', 'Bonjour');

        $this->assertFalse($response->success);
        $this->assertStringContainsString('Invalid OAuth access token.', (string) $response->error);
    }

    public function test_meta_whatsapp_rejects_invalid_phone_number_without_calling_api(): void
    {
        config([
            'services.whatsapp.token' => 'fake-whatsapp-token',
            'services.whatsapp.phone_number_id' => '106732507566763',
        ]);

        Http::fake();

        $response = (new MetaWhatsAppProvider)->send('abc', 'Bonjour');

        $this->assertFalse($response->success);
        $this->assertStringContainsString('Numéro invalide', (string) $response->error);
        Http::assertNothingSent();
    }

    public function test_notification_service_uses_whatsapp_channel_with_rich_message_when_provider_is_whatsapp(): void
    {
        config(['services.sms.provider' => 'whatsapp']);
        Queue::fake();

        $school = School::factory()->create();
        ['evaluation' => $evaluation, 'guardian' => $guardian] = $this->makeEvaluationWithGuardian($school);

        $created = app(NotificationService::class)->notifyForEvaluation($evaluation);

        $this->assertSame(1, $created);
        $notification = Notification::withoutGlobalScopes()->first();

        $this->assertSame('whatsapp', $notification->channel);
        $this->assertStringStartsWith('🎓 *'.$school->name.'*', $notification->message);
        $this->assertStringContainsString('Bonjour '.$guardian->name.',', $notification->message);
        $this->assertStringContainsString('15.0/20', $notification->message);
        $this->assertStringContainsString('Merci pour votre confiance 🙏', $notification->message);

        Queue::assertPushed(SendSmsNotificationJob::class);

        config(['services.sms.provider' => 'log']);
    }

    public function test_notification_service_keeps_compact_message_on_sms_channel(): void
    {
        Queue::fake();

        $school = School::factory()->create();
        ['evaluation' => $evaluation] = $this->makeEvaluationWithGuardian($school);

        app(NotificationService::class)->notifyForEvaluation($evaluation);

        $notification = Notification::withoutGlobalScopes()->first();

        $this->assertSame('sms', $notification->channel);
        $this->assertStringContainsString('note de', $notification->message);
        $this->assertStringNotContainsString('🎓', $notification->message);
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

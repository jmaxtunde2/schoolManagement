<?php

namespace Tests\Feature\Communication;

use App\Enums\CommunicationChannel;
use App\Enums\EvaluationNotificationStatus;
use App\Enums\NotificationStatus;
use App\Enums\Role;
use App\Jobs\SendCommunicationJob;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\SchoolCommunicationSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Communication\CommunicationBudgetService;
use App\Services\Communication\CommunicationSettingsService;
use App\Services\Communication\CommunicationTransport;
use App\Services\Communication\NotificationAggregationService;
use App\Services\Communication\NotificationChannelService;
use App\Services\Communication\NotificationMessageBuilder;
use App\Services\Communication\ProviderCommunicationTransport;
use App\Services\Mail\SchoolMailService;
use App\Services\Sms\SmsResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EvaluationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private ClassRoom $class;

    private Subject $subject;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.token' => 'fake-whatsapp-token',
            'services.whatsapp.phone_number_id' => 'fake-phone-number-id',
        ]);

        $this->school = School::factory()->create(['name' => 'École Test']);
        $this->year = AcademicYear::factory()->create(['school_id' => $this->school->id, 'is_current' => true]);
        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create(['school_id' => $this->school->id, 'user_id' => $teacherUser->id]);
        $this->class = ClassRoom::factory()->create(['school_id' => $this->school->id]);
        $this->subject = Subject::factory()->create(['school_id' => $this->school->id, 'name' => 'Mathématiques']);
    }

    private function validatedEvaluation(Student $student, ?Subject $subject = null, float $score = 15): Evaluation
    {
        $evaluation = Evaluation::factory()->validated()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'subject_id' => ($subject ?? $this->subject)->id,
            'teacher_id' => $this->teacher->id,
            'validated_at' => now(),
        ]);

        $evaluation->results()->create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'score' => $score,
        ]);

        return $evaluation;
    }

    private function studentWithGuardian(?string $phone, ?string $email, ?User $user = null): Student
    {
        $student = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
        ]);

        $guardian = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'phone' => $phone,
            'email' => $email,
            'user_id' => $user?->id,
        ]);

        $guardian->students()->attach($student->id, ['relationship' => 'parent']);

        return $student;
    }

    public function test_aggregate_consolidates_evaluations_into_one_batch_and_notification_per_student(): void
    {
        Queue::fake();

        $student = $this->studentWithGuardian('+22890000001', null);
        $this->validatedEvaluation($student);
        $this->validatedEvaluation($student, score: 12);

        $stats = app(NotificationAggregationService::class)->aggregate();

        $this->assertSame(1, $stats['batches']);
        $this->assertSame(1, $stats['recipients']);
        $this->assertSame(1, NotificationBatch::count());
        $this->assertSame(1, Notification::count());
        $this->assertSame(2, NotificationBatch::first()->evaluations()->count());

        $this->assertSame(
            EvaluationNotificationStatus::Included,
            Evaluation::first()->notification_status
        );
    }

    public function test_running_aggregate_twice_does_not_duplicate(): void
    {
        Queue::fake();

        $student = $this->studentWithGuardian('+22890000001', null);
        $this->validatedEvaluation($student);

        app(NotificationAggregationService::class)->aggregate();
        app(NotificationAggregationService::class)->aggregate();

        $this->assertSame(1, NotificationBatch::count());
        $this->assertSame(1, Notification::count());
    }

    public function test_guardians_sharing_a_phone_receive_a_single_external_message(): void
    {
        Queue::fake();

        $student = Student::factory()->create(['school_id' => $this->school->id, 'class_id' => $this->class->id]);

        foreach (['Père', 'Mère'] as $name) {
            $guardian = ParentGuardian::factory()->create([
                'school_id' => $this->school->id,
                'phone' => '+22890000009',
            ]);
            $guardian->students()->attach($student->id, ['relationship' => 'parent']);
        }

        $this->validatedEvaluation($student);

        app(NotificationAggregationService::class)->aggregate();

        $this->assertSame(1, Notification::count());
    }

    public function test_in_app_notification_is_created_for_linked_parent_user(): void
    {
        Queue::fake();

        $parentUser = User::factory()->forSchool($this->school)->create();
        $parentUser->forceFill(['role' => Role::Parent])->save();

        $student = $this->studentWithGuardian('+22890000001', null, $parentUser);
        $this->validatedEvaluation($student);

        app(NotificationAggregationService::class)->aggregate();

        $this->assertSame(1, UserNotification::where('type', 'evaluation.grades')->count());
    }

    public function test_external_message_falls_back_to_concise_alert_when_details_too_long(): void
    {
        $builder = app(NotificationMessageBuilder::class);
        $entries = [];

        for ($i = 0; $i < 8; $i++) {
            $entries[] = [
                'subject' => 'Mathématiques Appliquées',
                'title' => 'Devoir',
                'score' => 15.0,
                'max' => 20.0,
                'absent' => false,
            ];
        }

        $built = $builder->externalMessage(
            CommunicationChannel::Sms,
            'École Test',
            'Amadou Diallo',
            $entries
        );

        $this->assertFalse($built['detailed']);
        $this->assertLessThanOrEqual(160, mb_strlen($built['message']));
        $this->assertStringContainsString('sont disponibles', $built['message']);
    }

    public function test_job_sends_whatsapp_first_and_consumes_whatsapp_budget(): void
    {
        Queue::fake();

        $student = $this->studentWithGuardian('+22890000001', null);
        $this->validatedEvaluation($student);
        app(NotificationAggregationService::class)->aggregate();

        $notification = Notification::firstOrFail();
        $transport = new FakeCommunicationTransport;

        $this->runJob($notification->id, $transport);

        $notification->refresh();
        $this->assertSame(CommunicationChannel::Whatsapp, CommunicationChannel::from($notification->channel));
        $this->assertSame(CommunicationChannel::Whatsapp, $transport->sent[0]['channel']);
        $this->assertSame(NotificationStatus::Sent, $notification->status);
        $this->assertEqualsWithDelta(0.02, (float) $notification->estimated_cost, 0.000001);
        $this->assertEqualsWithDelta(0.02, (float) $notification->batch->cost, 0.000001);

        $settings = SchoolCommunicationSetting::withoutGlobalScopes()->firstOrFail();
        $this->assertGreaterThan(0, (float) $settings->whatsapp_consumed);
        $this->assertSame(3000, (int) $settings->sms_balance);
    }

    public function test_job_falls_back_to_sms_when_whatsapp_fails(): void
    {
        Queue::fake();

        $student = $this->studentWithGuardian('+22890000001', null);
        $this->validatedEvaluation($student);
        app(NotificationAggregationService::class)->aggregate();

        $notification = Notification::firstOrFail();
        $transport = new FakeCommunicationTransport(failChannels: [CommunicationChannel::Whatsapp]);

        $this->runJob($notification->id, $transport);

        $notification->refresh();
        $this->assertSame(CommunicationChannel::Sms, CommunicationChannel::from($notification->channel));
        $this->assertSame(CommunicationChannel::Sms, $transport->sent[0]['channel']);
        $this->assertSame(NotificationStatus::Sent, $notification->status);
        $this->assertEqualsWithDelta(25, (float) $notification->estimated_cost, 0.000001);
        $this->assertEqualsWithDelta(25, (float) $notification->batch->cost, 0.000001);

        $settings = SchoolCommunicationSetting::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(2975, (int) $settings->sms_balance);
        $this->assertSame(0.0, (float) $settings->whatsapp_consumed);
    }

    public function test_job_falls_back_to_email_when_whatsapp_and_sms_fail(): void
    {
        Queue::fake();

        $student = $this->studentWithGuardian('+22890000001', 'parent@example.com');
        $this->validatedEvaluation($student);
        app(NotificationAggregationService::class)->aggregate();

        $notification = Notification::firstOrFail();
        $transport = new FakeCommunicationTransport(failChannels: [
            CommunicationChannel::Whatsapp,
            CommunicationChannel::Sms,
        ]);

        $this->runJob($notification->id, $transport);

        $notification->refresh();
        $this->assertSame(CommunicationChannel::Email, CommunicationChannel::from($notification->channel));
        $this->assertSame(CommunicationChannel::Email, $transport->sent[0]['channel']);
        $this->assertSame(NotificationStatus::Sent, $notification->status);
        $this->assertSame('parent@example.com', $notification->email);
    }

    public function test_job_falls_back_to_email_when_phone_channels_disabled(): void
    {
        Queue::fake();

        $student = $this->studentWithGuardian('+22890000001', 'parent@example.com');
        $this->validatedEvaluation($student);
        app(NotificationAggregationService::class)->aggregate();

        app(CommunicationSettingsService::class)->for($this->school)->update([
            'sms_enabled' => false,
            'whatsapp_enabled' => false,
        ]);

        $notification = Notification::firstOrFail();
        $transport = new FakeCommunicationTransport;

        $this->runJob($notification->id, $transport);

        $notification->refresh();
        $this->assertSame(CommunicationChannel::Email, CommunicationChannel::from($notification->channel));
        $this->assertSame(NotificationStatus::Sent, $notification->status);
        $this->assertSame('parent@example.com', $notification->email);
    }

    public function test_low_sms_balance_skips_sms_channel(): void
    {
        Queue::fake();

        $student = $this->studentWithGuardian('+22890000001', null);
        $this->validatedEvaluation($student);

        app(CommunicationSettingsService::class)->for($this->school)->update([
            'sms_balance' => 100,
            'sms_low_balance_threshold' => 500,
        ]);

        app(NotificationAggregationService::class)->aggregate();

        $this->assertSame(
            CommunicationChannel::Whatsapp,
            CommunicationChannel::from(Notification::firstOrFail()->channel)
        );
    }

    public function test_whatsapp_is_skipped_when_not_configured(): void
    {
        Queue::fake();

        config([
            'services.whatsapp.token' => null,
            'services.whatsapp.phone_number_id' => null,
        ]);

        $student = $this->studentWithGuardian('+22890000001', null);
        $this->validatedEvaluation($student);
        app(NotificationAggregationService::class)->aggregate();

        $this->assertSame(
            CommunicationChannel::Sms,
            CommunicationChannel::from(Notification::firstOrFail()->channel)
        );
    }

    public function test_email_transport_uses_the_school_mail_service(): void
    {
        $this->mock(SchoolMailService::class, function ($mock) {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(fn (School $school, string $to) => $school->is($this->school)
                    && $to === 'parent@example.com');
        });

        $response = app(ProviderCommunicationTransport::class)->send(
            CommunicationChannel::Email,
            $this->school,
            'parent@example.com',
            'Nouvelles notes disponibles'
        );

        $this->assertTrue($response->success);
    }

    public function test_command_runs_successfully(): void
    {
        Queue::fake();

        $student = $this->studentWithGuardian('+22890000001', null);
        $this->validatedEvaluation($student);

        $this->artisan('corischool:notify-validated-evaluations')
            ->assertSuccessful();

        $this->assertSame(1, NotificationBatch::count());
    }

    private function runJob(int $notificationId, CommunicationTransport $transport): void
    {
        (new SendCommunicationJob($notificationId))->handle(
            $transport,
            app(CommunicationBudgetService::class),
            app(NotificationChannelService::class),
        );
    }

    public function test_send_communication_job_passes_notification_metadata_to_transport(): void
    {
        Queue::fake();

        $student = $this->studentWithGuardian('+22890000001', null);
        $this->validatedEvaluation($student);

        app(NotificationAggregationService::class)->aggregate();

        $notification = Notification::firstOrFail();
        $notification->forceFill(['metadata' => ['source' => 'evaluation', 'batch_id' => $notification->notification_batch_id]])->save();

        $transport = new FakeCommunicationTransport;

        $this->runJob($notification->id, $transport);

        $this->assertCount(1, $transport->sent);
        $this->assertSame(['source' => 'evaluation', 'batch_id' => $notification->notification_batch_id], $transport->sent[0]['metadata']);
    }

    public function test_whatsapp_template_payload_from_metadata(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response([
                'messages' => [['id' => 'wamid.test']],
            ], 200),
        ]);

        config([
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.api_url' => 'https://graph.facebook.com',
            'services.whatsapp.graph_version' => 'v21.0',
        ]);

        $provider = new \App\Services\Sms\MetaWhatsAppProvider();
        $metadata = [
            'whatsapp' => [
                'template' => 'notes_disponibles',
                'language' => 'fr',
                'body_parameters' => ['Amadou', 'Maths'],
                'url_parameter' => 'abc123',
            ],
        ];

        $response = $provider->send('+22890000001', 'ignored', $metadata);

        $this->assertTrue($response->success);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            $data = $request->data();

            return $data['type'] === 'template'
                && $data['template']['name'] === 'notes_disponibles'
                && $data['template']['language']['code'] === 'fr'
                && isset($data['template']['components'][0])
                && $data['template']['components'][0]['type'] === 'body'
                && $data['template']['components'][1]['type'] === 'button'
                && $data['template']['components'][1]['sub_type'] === 'url'
                && $data['template']['components'][1]['parameters'][0]['text'] === 'abc123';
        });
    }

    public function test_validated_evaluation_uses_corischool_template_parameters_in_meta_order(): void
    {
        Queue::fake();
        $student = $this->studentWithGuardian('+22890000001', null);
        $this->validatedEvaluation($student);

        app(NotificationAggregationService::class)->aggregate();

        $notification = Notification::firstOrFail();
        $this->assertSame('whatsapp', $notification->channel);
        $this->assertSame('corischool_evaluation', data_get($notification->metadata, 'whatsapp.template'));
        $this->assertSame('fr', data_get($notification->metadata, 'whatsapp.language'));
        $this->assertSame([
            $this->school->name,
            ParentGuardian::firstOrFail()->name,
            $student->full_name,
            Evaluation::firstOrFail()->title,
            "Mathématiques : 15/20",
        ], data_get($notification->metadata, 'whatsapp.body_parameters'));
        $this->assertSame($this->school->slug, data_get($notification->metadata, 'whatsapp.url_parameter'));
    }
}

class FakeCommunicationTransport implements CommunicationTransport
{
    /** @var array<int, array{channel: CommunicationChannel, recipient: string, message: string, metadata: ?array}> */
    public array $sent = [];

    /**
     * @param  array<int, CommunicationChannel>  $failChannels
     */
    public function __construct(private array $failChannels = []) {}

    public function send(
        CommunicationChannel $channel,
        School $school,
        string $recipient,
        string $message,
        ?array $metadata = null
    ): SmsResponse {
        if (in_array($channel, $this->failChannels, true)) {
            return SmsResponse::failure('Échec simulé pour '.$channel->value);
        }

        $this->sent[] = ['channel' => $channel, 'recipient' => $recipient, 'message' => $message, 'metadata' => $metadata];

        return SmsResponse::success('fake_'.count($this->sent));
    }
}

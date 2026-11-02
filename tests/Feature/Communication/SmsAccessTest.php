<?php

namespace Tests\Feature\Communication;

use App\Enums\CommunicationChannel;
use App\Enums\Role;
use App\Jobs\SendCommunicationJob;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\SmsNotificationAccessToken;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Communication\CommunicationBudgetService;
use App\Services\Communication\CommunicationTransport;
use App\Services\Communication\NotificationAggregationService;
use App\Services\Communication\NotificationChannelService;
use App\Services\Sms\SmsResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SmsAccessTest extends TestCase
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

        // Aucun WhatsApp configuré : le canal téléphonique sélectionné est le SMS.
        config([
            'services.whatsapp.token' => null,
            'services.whatsapp.phone_number_id' => null,
            'communication.sms_access.url' => 'https://corischool.com',
        ]);

        $this->school = School::factory()->create(['name' => 'École Test']);
        $this->year = AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'is_current' => true,
        ]);
        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
        ]);
        $this->class = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => '3ème A',
        ]);
        $this->subject = Subject::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'Mathématiques',
        ]);
    }

    private function makeStudent(string $firstName, string $lastName): Student
    {
        return Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function makeGuardian(string $phone = '+22890000001'): ParentGuardian
    {
        return ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'phone' => $phone,
        ]);
    }

    private function validatedEvaluation(Student $student, float $score = 15): Evaluation
    {
        $evaluation = Evaluation::factory()->validated()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Devoir de mathématiques',
            'validated_at' => now(),
        ]);

        $evaluation->results()->create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'score' => $score,
            'is_absent' => false,
        ]);

        return $evaluation;
    }

    /**
     * @return array{notification: Notification, token: string}
     */
    private function sendSmsAndExtractToken(Notification $notification): array
    {
        $transport = new RecordingCommunicationTransport;

        (new SendCommunicationJob($notification->id))->handle(
            $transport,
            app(CommunicationBudgetService::class),
            app(NotificationChannelService::class),
        );

        $this->assertCount(1, $transport->sent, 'Le SMS aurait dû être envoyé.');

        $message = $transport->sent[0]['message'];

        $this->assertMatchesRegularExpression(
            '#https://corischool\.com/sms/access/[A-Za-z0-9]+#',
            $message
        );

        preg_match('#/sms/access/([A-Za-z0-9]+)#', $message, $matches);

        return [
            'notification' => $notification->fresh(),
            'token' => $matches[1],
        ];
    }

    private function notificationFor(Student $student): Notification
    {
        return Notification::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->latest('id')
            ->firstOrFail();
    }

    public function test_parent_with_one_child_lands_directly_on_the_evaluation(): void
    {
        Queue::fake();

        $child = $this->makeStudent('Jean', 'Kouassi');
        $guardian = $this->makeGuardian();
        $guardian->students()->attach($child->id, ['relationship' => 'père']);

        $this->validatedEvaluation($child, 15);

        app(NotificationAggregationService::class)->aggregate();

        $notification = $this->notificationFor($child);
        $this->assertSame('sms', $notification->channel);

        ['token' => $token] = $this->sendSmsAndExtractToken($notification);

        $this->get('/sms/access/'.$token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SmsAccess/Show')
                ->where('student.name', $child->full_name)
                ->where('student.class_name', '3ème A')
                ->where('school.name', 'École Test')
                ->has('evaluations', 1)
                ->where('evaluations.0.subject', 'Mathématiques')
                ->where('evaluations.0.title', 'Devoir de mathématiques')
                ->where('evaluations.0.score', 15)
                ->where('evaluations.0.max_score', 20));
    }

    public function test_weekly_batch_link_shows_all_evaluations_of_the_week(): void
    {
        Queue::fake();

        $child = $this->makeStudent('Jean', 'Kouassi');
        $guardian = $this->makeGuardian();
        $guardian->students()->attach($child->id);

        $this->validatedEvaluation($child, 10);
        $this->validatedEvaluation($child, 15);
        $this->validatedEvaluation($child, 18);

        app(NotificationAggregationService::class)->aggregate();

        // L'agrégation hebdomadaire regroupe les trois évaluations dans un
        // seul lot et donc un seul SMS.
        $this->assertSame(
            1,
            Notification::withoutGlobalScopes()->where('student_id', $child->id)->count()
        );

        ['token' => $token] = $this->sendSmsAndExtractToken($this->notificationFor($child));

        $this->get('/sms/access/'.$token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SmsAccess/Show')
                ->where('student.name', $child->full_name)
                ->has('evaluations', 3)
                ->where('evaluations.0.score', 10)
                ->where('evaluations.1.score', 15)
                ->where('evaluations.2.score', 18));
    }

    public function test_parent_with_five_children_only_sees_the_target_child(): void
    {
        Queue::fake();

        $guardian = $this->makeGuardian();
        $children = [
            $this->makeStudent('Jean', 'Kouassi'),
            $this->makeStudent('Marie', 'Kouassi'),
            $this->makeStudent('Paul', 'Kouassi'),
            $this->makeStudent('Awa', 'Kouassi'),
            $this->makeStudent('Léa', 'Kouassi'),
        ];

        foreach ($children as $child) {
            $guardian->students()->attach($child->id, ['relationship' => 'parent']);
            $this->validatedEvaluation($child, 12);
        }

        app(NotificationAggregationService::class)->aggregate();

        $target = $children[2];
        ['token' => $token] = $this->sendSmsAndExtractToken($this->notificationFor($target));

        $this->get('/sms/access/'.$token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SmsAccess/Show')
                ->where('student.name', $target->full_name)
                ->has('evaluations', 1));

        $response = $this->get('/sms/access/'.$token);

        foreach ($children as $index => $child) {
            if ($index === 2) {
                $response->assertSee($child->full_name);
            } else {
                $response->assertDontSee($child->full_name);
            }
        }
    }

    public function test_expired_token_shows_a_clear_message(): void
    {
        Queue::fake();

        $child = $this->makeStudent('Jean', 'Kouassi');
        $guardian = $this->makeGuardian();
        $guardian->students()->attach($child->id);
        $this->validatedEvaluation($child);
        app(NotificationAggregationService::class)->aggregate();

        ['token' => $token] = $this->sendSmsAndExtractToken($this->notificationFor($child));

        SmsNotificationAccessToken::withoutGlobalScopes()
            ->where('notification_id', $this->notificationFor($child)->id)
            ->update(['expires_at' => now()->subMinute()]);

        $this->get('/sms/access/'.$token)
            ->assertStatus(410)
            ->assertInertia(fn (Assert $page) => $page
                ->component('SmsAccess/Unavailable')
                ->where('title', 'Lien expiré')
                ->where('message', 'Ce lien a expiré.'));
    }

    public function test_revoked_token_shows_a_clear_message(): void
    {
        Queue::fake();

        $child = $this->makeStudent('Jean', 'Kouassi');
        $guardian = $this->makeGuardian();
        $guardian->students()->attach($child->id);
        $this->validatedEvaluation($child);
        app(NotificationAggregationService::class)->aggregate();

        ['token' => $token] = $this->sendSmsAndExtractToken($this->notificationFor($child));

        SmsNotificationAccessToken::withoutGlobalScopes()
            ->where('notification_id', $this->notificationFor($child)->id)
            ->update(['revoked_at' => now(), 'revoked_reason' => 'Test']);

        $this->get('/sms/access/'.$token)
            ->assertStatus(410)
            ->assertInertia(fn (Assert $page) => $page
                ->component('SmsAccess/Unavailable')
                ->where('title', 'Lien révoqué'));
    }

    public function test_forged_token_is_refused(): void
    {
        $this->get('/sms/access/'.str_repeat('a', 32))
            ->assertStatus(404)
            ->assertInertia(fn (Assert $page) => $page
                ->component('SmsAccess/Unavailable')
                ->where('title', 'Lien invalide'));
    }

    public function test_url_parameters_cannot_change_the_student_or_evaluation(): void
    {
        Queue::fake();

        $guardian = $this->makeGuardian();
        $target = $this->makeStudent('Jean', 'Kouassi');
        $other = $this->makeStudent('Marie', 'Kouassi');
        $guardian->students()->attach($target->id);
        $guardian->students()->attach($other->id);

        $targetEvaluation = $this->validatedEvaluation($target, 15);
        $otherEvaluation = $this->validatedEvaluation($other, 5);

        app(NotificationAggregationService::class)->aggregate();

        ['token' => $token] = $this->sendSmsAndExtractToken($this->notificationFor($target));

        $this->get('/sms/access/'.$token.'?student_id='.$other->id.'&evaluation_id='.$otherEvaluation->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SmsAccess/Show')
                ->where('student.name', $target->full_name)
                ->has('evaluations', 1)
                ->where('evaluations.0.score', 15))
            ->assertDontSee($other->full_name);
    }

    public function test_raw_token_is_never_stored_in_database(): void
    {
        Queue::fake();

        $child = $this->makeStudent('Jean', 'Kouassi');
        $guardian = $this->makeGuardian();
        $guardian->students()->attach($child->id);
        $this->validatedEvaluation($child);
        app(NotificationAggregationService::class)->aggregate();

        ['token' => $token] = $this->sendSmsAndExtractToken($this->notificationFor($child));

        $this->assertTrue(
            SmsNotificationAccessToken::withoutGlobalScopes()
                ->where('token_hash', hash('sha256', $token))
                ->exists()
        );

        $this->assertFalse(
            SmsNotificationAccessToken::withoutGlobalScopes()
                ->where('token_hash', $token)
                ->exists()
        );

        $this->assertSame(0, DB::table('sms_notification_access_tokens')
            ->where('token_hash', $token)
            ->count());
    }

    public function test_usage_limit_blocks_further_access(): void
    {
        Queue::fake();

        $child = $this->makeStudent('Jean', 'Kouassi');
        $guardian = $this->makeGuardian();
        $guardian->students()->attach($child->id);
        $this->validatedEvaluation($child);
        app(NotificationAggregationService::class)->aggregate();

        ['token' => $token] = $this->sendSmsAndExtractToken($this->notificationFor($child));

        SmsNotificationAccessToken::withoutGlobalScopes()
            ->where('notification_id', $this->notificationFor($child)->id)
            ->update(['max_uses' => 1]);

        $this->get('/sms/access/'.$token)->assertOk();
        $this->get('/sms/access/'.$token)
            ->assertStatus(429)
            ->assertInertia(fn (Assert $page) => $page
                ->component('SmsAccess/Unavailable')
                ->where('title', 'Lien déjà utilisé'));
    }

    public function test_whatsapp_behaviour_is_unchanged_and_does_not_create_sms_tokens(): void
    {
        Queue::fake();

        config([
            'services.whatsapp.token' => 'fake-whatsapp-token',
            'services.whatsapp.phone_number_id' => 'fake-phone-number-id',
        ]);

        $child = $this->makeStudent('Jean', 'Kouassi');
        $guardian = $this->makeGuardian();
        $guardian->students()->attach($child->id);
        $this->validatedEvaluation($child);

        app(NotificationAggregationService::class)->aggregate();

        $notification = $this->notificationFor($child);

        $this->assertSame('whatsapp', $notification->channel);
        $this->assertSame('corischool_evaluation', data_get($notification->metadata, 'whatsapp.template'));
        $this->assertSame($this->school->slug, data_get($notification->metadata, 'whatsapp.url_parameter'));
        $this->assertStringContainsString('15/20', $notification->message);
        $this->assertStringNotContainsString('/sms/access/', $notification->message);
        $this->assertSame(0, SmsNotificationAccessToken::count());
    }

    public function test_sms_fallback_after_whatsapp_failure_contains_a_working_link(): void
    {
        Queue::fake();

        config([
            'services.whatsapp.token' => 'fake-whatsapp-token',
            'services.whatsapp.phone_number_id' => 'fake-phone-number-id',
        ]);

        $child = $this->makeStudent('Jean', 'Kouassi');
        $guardian = $this->makeGuardian();
        $guardian->students()->attach($child->id);
        $this->validatedEvaluation($child);

        app(NotificationAggregationService::class)->aggregate();

        $notification = $this->notificationFor($child);
        $this->assertSame('whatsapp', $notification->channel);
        $this->assertSame(0, SmsNotificationAccessToken::count());

        $transport = new RecordingCommunicationTransport;
        $transport->failChannels = [CommunicationChannel::Whatsapp];

        (new SendCommunicationJob($notification->id))->handle(
            $transport,
            app(CommunicationBudgetService::class),
            app(NotificationChannelService::class),
        );

        $this->assertCount(2, $transport->sent);
        $this->assertSame(CommunicationChannel::Sms, $transport->sent[1]['channel']);

        $message = $transport->sent[1]['message'];
        $this->assertMatchesRegularExpression('#/sms/access/[A-Za-z0-9]+#', $message);

        preg_match('#/sms/access/([A-Za-z0-9]+)#', $message, $matches);

        $this->get('/sms/access/'.$matches[1])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SmsAccess/Show')
                ->where('student.name', $child->full_name)
                ->has('evaluations', 1));
    }

    public function test_parent_with_linked_account_is_offered_the_parent_space(): void
    {
        Queue::fake();

        $parentUser = User::factory()->forSchool($this->school)->create();
        $parentUser->forceFill(['role' => Role::Parent])->save();
        $child = $this->makeStudent('Jean', 'Kouassi');
        $guardian = $this->makeGuardian();
        $guardian->forceFill(['user_id' => $parentUser->id])->save();
        $guardian->students()->attach($child->id);
        $this->validatedEvaluation($child);

        app(NotificationAggregationService::class)->aggregate();

        ['token' => $token] = $this->sendSmsAndExtractToken($this->notificationFor($child));

        $this->actingAs($parentUser)
            ->get('/sms/access/'.$token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SmsAccess/Show')
                ->where('is_authenticated', true)
                ->where('parent_url', route('parent.dashboard')));
    }
}

class RecordingCommunicationTransport implements CommunicationTransport
{
    /** @var array<int, array{channel: CommunicationChannel, recipient: string, message: string, metadata: ?array}> */
    public array $sent = [];

    /** @var array<int, CommunicationChannel> */
    public array $failChannels = [];

    public function send(
        CommunicationChannel $channel,
        School $school,
        string $recipient,
        string $message,
        ?array $metadata = null
    ): SmsResponse {
        $this->sent[] = [
            'channel' => $channel,
            'recipient' => $recipient,
            'message' => $message,
            'metadata' => $metadata,
        ];

        if (in_array($channel, $this->failChannels, true)) {
            return SmsResponse::failure('Échec simulé pour '.$channel->value);
        }

        return SmsResponse::success('fake_'.count($this->sent));
    }
}

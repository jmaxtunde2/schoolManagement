<?php

namespace Tests\Feature\Announcements;

use App\Enums\AnnouncementStatus;
use App\Enums\NotificationStatus;
use App\Jobs\DispatchAnnouncementJob;
use App\Jobs\SendAnnouncementRecipientJob;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Announcements\AnnouncementService;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\SmsResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * École avec 2 classes : classe A (2 parents, dont un avec email) et
     * classe B (1 parent). Chaque parent est rattaché à un élève de sa classe.
     *
     * @return array{school: School, classA: ClassRoom, classB: ClassRoom, guardianA: ParentGuardian, guardianA2: ParentGuardian, guardianB: ParentGuardian}
     */
    private function makeSchool(): array
    {
        $school = School::factory()->create();
        $classA = ClassRoom::factory()->create(['school_id' => $school->id, 'level' => '6ème']);
        $classB = ClassRoom::factory()->create(['school_id' => $school->id, 'level' => '5ème']);

        $studentA = Student::factory()->create(['school_id' => $school->id, 'class_id' => $classA->id]);
        $studentA2 = Student::factory()->create(['school_id' => $school->id, 'class_id' => $classA->id]);
        $studentB = Student::factory()->create(['school_id' => $school->id, 'class_id' => $classB->id]);

        $guardianA = ParentGuardian::factory()->create(['school_id' => $school->id, 'email' => 'a@example.com']);
        $guardianA->students()->attach($studentA->id);

        $guardianA2 = ParentGuardian::factory()->create(['school_id' => $school->id, 'email' => null]);
        $guardianA2->students()->attach($studentA2->id);

        $guardianB = ParentGuardian::factory()->create(['school_id' => $school->id, 'email' => null]);
        $guardianB->students()->attach($studentB->id);

        return compact('school', 'classA', 'classB', 'guardianA', 'guardianA2', 'guardianB');
    }

    private function baseAnnouncement(School $school, array $attributes = []): Announcement
    {
        return Announcement::factory()->create([
            'school_id' => $school->id,
            'status' => AnnouncementStatus::Draft,
            'channels' => [Announcement::CHANNEL_WHATSAPP],
            ...$attributes,
        ]);
    }

    public function test_admin_can_view_announcement_index(): void
    {
        $school = School::factory()->create();
        $this->baseAnnouncement($school);
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)
            ->get(route('communication.announcements.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Communication/Announcements/Index')
                ->has('announcements.data', 1));
    }

    public function test_censeur_and_secretary_can_access_but_teacher_cannot(): void
    {
        $school = School::factory()->create();

        $this->actingAs(User::factory()->censeur()->forSchool($school)->create())
            ->get(route('communication.announcements.index'))->assertOk();

        $this->actingAs(User::factory()->secretary()->forSchool($school)->create())
            ->get(route('communication.announcements.create'))->assertOk();

        $this->actingAs(User::factory()->teacher()->forSchool($school)->create())
            ->get(route('communication.announcements.index'))->assertForbidden();
    }

    public function test_audience_all_creates_a_recipient_per_guardian_and_channel(): void
    {
        Queue::fake();
        ['school' => $school] = $this->makeSchool();
        $announcement = $this->baseAnnouncement($school, [
            'channels' => [Announcement::CHANNEL_WHATSAPP, Announcement::CHANNEL_EMAIL],
            'audience_type' => Announcement::AUDIENCE_ALL,
        ]);

        $created = app(AnnouncementService::class)->dispatch($announcement);

        // 3 parents par WhatsApp + 1 email (seul guardianA a un email) = 4.
        $this->assertSame(4, $created);
        $this->assertSame(4, AnnouncementRecipient::withoutGlobalScopes()->count());
        Queue::assertPushed(SendAnnouncementRecipientJob::class, 4);
    }

    public function test_audience_classes_only_targets_guardians_of_selected_classes(): void
    {
        Queue::fake();
        ['school' => $school, 'classA' => $classA, 'guardianA' => $guardianA, 'guardianA2' => $guardianA2] = $this->makeSchool();

        $announcement = $this->baseAnnouncement($school, [
            'audience_type' => Announcement::AUDIENCE_CLASSES,
        ]);
        $announcement->classes()->sync([$classA->id]);

        app(AnnouncementService::class)->dispatch($announcement);

        $parentIds = AnnouncementRecipient::withoutGlobalScopes()->pluck('parent_id')->all();
        sort($parentIds);

        $this->assertSame([$guardianA->id, $guardianA2->id], $parentIds);
    }

    public function test_audience_guardians_only_targets_selected_guardians(): void
    {
        Queue::fake();
        ['school' => $school, 'guardianB' => $guardianB] = $this->makeSchool();

        $announcement = $this->baseAnnouncement($school, [
            'audience_type' => Announcement::AUDIENCE_GUARDIANS,
        ]);
        $announcement->guardians()->sync([$guardianB->id]);

        app(AnnouncementService::class)->dispatch($announcement);

        $this->assertSame(
            [$guardianB->id],
            AnnouncementRecipient::withoutGlobalScopes()->pluck('parent_id')->all()
        );
    }

    public function test_immediate_store_dispatches_the_dispatch_job(): void
    {
        Queue::fake();
        $school = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)->post(route('communication.announcements.store'), [
            'title' => 'Réunion de rentrée',
            'body' => 'Bonjour {parent}, une réunion est prévue.',
            'channels' => ['whatsapp'],
            'audience_type' => 'all',
        ])->assertRedirect();

        Queue::assertPushed(DispatchAnnouncementJob::class);

        $announcement = Announcement::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(AnnouncementStatus::Sending, $announcement->status);
    }

    public function test_scheduled_store_does_not_dispatch_immediately(): void
    {
        Queue::fake();
        $school = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)->post(route('communication.announcements.store'), [
            'title' => 'Convocation',
            'body' => 'Bonjour {parent}.',
            'channels' => ['email'],
            'audience_type' => 'all',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        Queue::assertNothingPushed();

        $announcement = Announcement::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(AnnouncementStatus::Scheduled, $announcement->status);
    }

    public function test_full_flow_marks_guardians_and_email_as_sent(): void
    {
        $this->mock(SmsProviderInterface::class, function ($mock) {
            $mock->shouldReceive('send')->andReturn(SmsResponse::success('ext_1'));
            $mock->shouldReceive('name')->andReturn('fake');
        });
        Mail::fake();

        ['school' => $school] = $this->makeSchool();

        $announcement = $this->baseAnnouncement($school, [
            'channels' => [Announcement::CHANNEL_WHATSAPP, Announcement::CHANNEL_EMAIL],
            'audience_type' => Announcement::AUDIENCE_ALL,
        ]);

        app(AnnouncementService::class)->dispatch($announcement);

        $recipients = AnnouncementRecipient::withoutGlobalScopes()->get();

        $this->assertCount(4, $recipients);
        $this->assertTrue($recipients->every(fn ($r) => $r->status === NotificationStatus::Sent));
        $this->assertSame(1, $recipients->where('channel', 'email')->count());
        $this->assertSame(AnnouncementStatus::Sent, $announcement->fresh()->status);
    }

    public function test_scheduled_command_dispatches_due_announcements(): void
    {
        Queue::fake();
        $school = School::factory()->create();
        $announcement = $this->baseAnnouncement($school, [
            'status' => AnnouncementStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('announcements:dispatch-scheduled')->assertSuccessful();

        Queue::assertPushed(DispatchAnnouncementJob::class);
        $this->assertSame(AnnouncementStatus::Scheduled, $announcement->fresh()->status);
    }

    public function test_admin_can_cancel_a_scheduled_announcement(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($school)->create();
        $announcement = $this->baseAnnouncement($school, ['status' => AnnouncementStatus::Scheduled]);

        $this->actingAs($admin)
            ->post(route('communication.announcements.cancel', $announcement))
            ->assertRedirect();

        $this->assertSame(AnnouncementStatus::Cancelled, $announcement->fresh()->status);
    }

    public function test_admin_can_delete_an_announcement(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($school)->create();
        $announcement = $this->baseAnnouncement($school);

        $this->actingAs($admin)
            ->delete(route('communication.announcements.destroy', $announcement))
            ->assertRedirect(route('communication.announcements.index'));

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_announcements_are_isolated_between_schools(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $announcementB = $this->baseAnnouncement($schoolB);
        $adminA = User::factory()->admin()->forSchool($schoolA)->create();

        $this->actingAs($adminA)
            ->get(route('communication.announcements.show', $announcementB))
            ->assertNotFound();
    }

    public function test_store_requires_at_least_one_channel_and_a_title(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)
            ->from(route('communication.announcements.create'))
            ->post(route('communication.announcements.store'), [
                'title' => '',
                'body' => '',
                'channels' => [],
                'audience_type' => 'all',
            ])
            ->assertSessionHasErrors(['title', 'body', 'channels']);
    }
}

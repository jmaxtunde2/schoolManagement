<?php

namespace Tests\Feature\Communication;

use App\Enums\NotificationStatus;
use App\Jobs\ProcessWatiWebhookJob;
use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Models\Notification;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_webhook_verification_still_returns_the_challenge(): void
    {
        config(['services.whatsapp.verify_token' => 'test-meta-verify-token']);

        $this->get('/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=test-meta-verify-token&hub.challenge=challenge-123')
            ->assertOk()
            ->assertSee('challenge-123');
    }

    public function test_meta_webhook_verification_rejects_an_invalid_token(): void
    {
        config(['services.whatsapp.verify_token' => 'test-meta-verify-token']);

        $this->get('/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=challenge-123')
            ->assertForbidden();
    }

    public function test_meta_webhook_payload_still_dispatches_the_meta_processing_job(): void
    {
        Queue::fake();

        $this->postJson('/api/whatsapp/webhook', [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.meta.route',
                            'status' => 'delivered',
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        Queue::assertPushed(ProcessWhatsAppWebhookJob::class);
        Queue::assertNotPushed(ProcessWatiWebhookJob::class);
    }

    public function test_meta_webhook_delivered_status_updates_a_notification(): void
    {
        $notification = $this->notification('wamid.meta.delivered');

        (new ProcessWhatsAppWebhookJob([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.meta.delivered',
                            'status' => 'delivered',
                        ]],
                    ],
                ]],
            ]],
        ]))->handle();

        $notification->refresh();

        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertNotNull($notification->delivered_at);
    }

    public function test_meta_webhook_failed_status_updates_a_notification(): void
    {
        $notification = $this->notification('wamid.meta.failed');

        (new ProcessWhatsAppWebhookJob([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.meta.failed',
                            'status' => 'failed',
                            'errors' => [[
                                'title' => 'Message failed to send',
                            ]],
                        ]],
                    ],
                ]],
            ]],
        ]))->handle();

        $notification->refresh();

        $this->assertSame(NotificationStatus::Failed, $notification->status);
        $this->assertSame('Message failed to send', $notification->error);
    }

    private function notification(string $externalId): Notification
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = ParentGuardian::factory()->create(['school_id' => $school->id]);

        return Notification::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'parent_id' => $guardian->id,
            'channel' => 'whatsapp',
            'phone' => $guardian->phone,
            'message' => 'Message Meta',
            'provider' => 'meta',
            'status' => NotificationStatus::Sent->value,
            'external_id' => $externalId,
        ]);
    }
}

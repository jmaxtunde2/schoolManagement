<?php

namespace Tests\Feature\Communication;

use App\Enums\NotificationStatus;
use App\Jobs\ProcessWatiWebhookJob;
use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Models\Notification;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Services\Sms\MetaWhatsAppProvider;
use App\Services\Sms\WatiWhatsAppProvider;
use App\Services\Sms\WhatsAppProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WatiWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private function configureWati(): void
    {
        config([
            'services.whatsapp.provider' => 'wati',
            'services.whatsapp.wati.enabled' => true,
            'services.whatsapp.wati.api_key' => 'fake-wati-key',
            'services.whatsapp.wati.api_url' => 'https://wati.test',
            'services.whatsapp.wati.channel_number' => '22969422974',
            'services.whatsapp.wati.webhook_secret' => 'test-wati-webhook-secret',
            'services.whatsapp.wati.timeout' => 5,
            'services.whatsapp.wati.template_cache_ttl' => 0,
            'services.whatsapp.wati.templates.corischool_evaluation.name' => 'corischool_evaluation_watifr',
            'services.whatsapp.wati.templates.corischool_evaluation.language' => 'fr',
            'services.whatsapp.wati.templates.corischool_evaluation.body_parameter_names' => [
                'school_name',
                'parent_name',
                'student_name',
                'evaluation_titles',
                'marks',
            ],
            'services.whatsapp.wati.templates.corischool_evaluation.url_parameter_name' => 'school_slug',
        ]);
    }

    public function test_manager_resolves_wati_and_meta_providers(): void
    {
        config(['services.whatsapp.provider' => 'wati']);
        $this->assertInstanceOf(WatiWhatsAppProvider::class, app(WhatsAppProviderManager::class)->provider());

        config(['services.whatsapp.provider' => 'meta']);
        $this->assertInstanceOf(MetaWhatsAppProvider::class, app(WhatsAppProviderManager::class)->provider());
    }

    public function test_wati_text_message_targets_the_active_conversation(): void
    {
        $this->configureWati();

        Http::fake([
            'https://wati.test/api/ext/v3/conversations/messages/text' => Http::response([
                'message' => [
                    'id' => 'wati-message-1',
                    'local_message_id' => 'local-text-1',
                    'status' => 'sent',
                ],
            ], 200),
        ]);

        $response = (new WatiWhatsAppProvider)->send('90000001', 'Bonjour depuis WATI');

        $this->assertTrue($response->success);
        $this->assertSame('local-text-1', $response->externalId);

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://wati.test/api/ext/v3/conversations/messages/text'
            && $request->hasHeader('Authorization', 'Bearer fake-wati-key')
            && $request['target'] === '22969422974:22890000001'
            && $request['text'] === 'Bonjour depuis WATI'
        );
    }

    public function test_wati_template_uses_named_body_and_url_parameters(): void
    {
        $this->configureWati();

        Http::fake(function (HttpRequest $request) {
            $data = $request->data();
            $recipient = $data['recipients'][0] ?? [];

            return Http::response([
                'success' => true,
                'broadcast_id' => 'broadcast-1',
                'recipients' => [[
                    'local_message_id' => $recipient['local_message_id'] ?? null,
                    'phone_number' => $recipient['phone_number'] ?? null,
                    'errors' => [],
                ]],
            ], 200);
        });

        $response = (new WatiWhatsAppProvider)->send('90000001', 'ignored', [
            'whatsapp' => [
                'template' => 'corischool_evaluation',
                'language' => 'fr',
                'body_parameters' => [
                    'École Test',
                    'Parent Test',
                    'Amadou Diallo',
                    'Devoir',
                    'Mathématiques : 15/20',
                ],
                'url_parameter' => 'ecole-test-1up6',
            ],
        ]);

        $this->assertTrue($response->success);

        Http::assertSent(function (HttpRequest $request) use ($response) {
            $data = $request->data();
            $params = collect($data['recipients'][0]['custom_params'] ?? [])->pluck('value', 'name')->all();

            return $request->url() === 'https://wati.test/api/ext/v3/messageTemplates/send'
                && $request->hasHeader('Authorization', 'Bearer fake-wati-key')
                && $data['template_name'] === 'corischool_evaluation_watifr'
                && $data['channel'] === '22969422974'
                && $data['recipients'][0]['phone_number'] === '22890000001'
                && $data['recipients'][0]['local_message_id'] === $response->externalId
                && $params === [
                    'school_name' => 'École Test',
                    'parent_name' => 'Parent Test',
                    'student_name' => 'Amadou Diallo',
                    'evaluation_titles' => 'Devoir',
                    'marks' => 'Mathématiques : 15/20',
                    'school_slug' => 'ecole-test-1up6',
                ];
        });
    }

    public function test_wati_template_discovery_reads_body_and_url_parameter_names(): void
    {
        $this->configureWati();
        config([
            'services.whatsapp.wati.templates.corischool_evaluation.body_parameter_names' => [],
            'services.whatsapp.wati.templates.corischool_evaluation.url_parameter_name' => null,
        ]);

        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/messageTemplates') && $request->method() === 'GET') {
                return Http::response([
                    'templates' => [[
                        'name' => 'corischool_evaluation_watifr',
                        'custom_params' => [
                            ['name' => 'school_name', 'value' => 'School'],
                            ['name' => 'parent_name', 'value' => 'Parent'],
                            ['name' => 'student_name', 'value' => 'Student'],
                            ['name' => 'evaluation_titles', 'value' => 'Titles'],
                            ['name' => 'marks', 'value' => 'Marks'],
                            ['name' => 'school_slug', 'value' => 'slug'],
                        ],
                        'buttons' => [[
                            'type' => 'url',
                            'parameter' => [
                                'url' => 'https://corischool.com/{{1}}',
                                'button_param_mapping' => ['param_name' => 'school_slug'],
                            ],
                        ]],
                    ]],
                    'total' => 1,
                ], 200);
            }

            $data = $request->data();
            $recipient = $data['recipients'][0] ?? [];

            return Http::response([
                'success' => true,
                'recipients' => [[
                    'local_message_id' => $recipient['local_message_id'] ?? null,
                    'errors' => [],
                ]],
            ], 200);
        });

        $response = (new WatiWhatsAppProvider)->send('90000001', 'ignored', [
            'whatsapp' => [
                'template' => 'corischool_evaluation',
                'body_parameters' => ['School', 'Parent', 'Student', 'Titles', 'Marks'],
                'url_parameter' => 'ecole-test-1up6',
            ],
        ]);

        $this->assertTrue($response->success);

        Http::assertSent(function (HttpRequest $request) {
            if ($request->method() !== 'POST') {
                return false;
            }

            $params = collect($request->data()['recipients'][0]['custom_params'] ?? [])->pluck('value', 'name')->all();

            return $params['school_name'] === 'School'
                && $params['marks'] === 'Marks'
                && $params['school_slug'] === 'ecole-test-1up6';
        });
    }

    public function test_wati_webhook_marks_a_notification_sent(): void
    {
        $notification = $this->notification('local-sent-1', NotificationStatus::Queued);

        (new ProcessWatiWebhookJob([
            'eventType' => 'templateMessageSent_v2',
            'statusString' => 'Sent',
            'localMessageId' => 'local-sent-1',
            'whatsappMessageId' => 'wamid.sent',
        ]))->handle();

        $notification->refresh();

        $this->assertSame(NotificationStatus::Sent, $notification->status);
    }

    public function test_wati_webhook_marks_a_notification_delivered(): void
    {
        $notification = $this->notification('local-delivery-1');

        (new ProcessWatiWebhookJob([
            'eventType' => 'sentMessageDELIVERED_v2',
            'statusString' => 'Delivered',
            'localMessageId' => 'local-delivery-1',
            'whatsappMessageId' => 'wamid.test',
        ]))->handle();

        $notification->refresh();

        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertNotNull($notification->delivered_at);
    }

    public function test_wati_webhook_marks_a_notification_read_as_delivered(): void
    {
        $notification = $this->notification('local-read-1');

        (new ProcessWatiWebhookJob([
            'eventType' => 'sentMessageREAD_v2',
            'statusString' => 'Read',
            'localMessageId' => 'local-read-1',
            'whatsappMessageId' => 'wamid.read',
        ]))->handle();

        $notification->refresh();

        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertNotNull($notification->delivered_at);
        $this->assertSame('Lu via WhatsApp (WATI).', $notification->logs()->latest('id')->value('message'));
    }

    public function test_wati_webhook_marks_a_notification_failed_with_the_wati_error(): void
    {
        $notification = $this->notification('local-failure-1');

        (new ProcessWatiWebhookJob([
            'eventType' => 'templateMessageFailed',
            'statusString' => 'Failed',
            'localMessageId' => 'local-failure-1',
            'failedCode' => '131026',
            'failedDetail' => 'Message undeliverable',
        ]))->handle();

        $notification->refresh();

        $this->assertSame(NotificationStatus::Failed, $notification->status);
        $this->assertSame('Message undeliverable (131026)', $notification->error);
    }

    public function test_wati_webhook_route_reuses_the_meta_webhook_endpoint(): void
    {
        $this->configureWati();
        Queue::fake();

        $this->postJson('/api/whatsapp/webhook', [
            'eventType' => 'templateMessageSent_v2',
            'localMessageId' => 'local-route-1',
        ], [
            'Authorization' => 'Bearer test-wati-webhook-secret',
        ])->assertOk();

        Queue::assertPushed(ProcessWatiWebhookJob::class);
        Queue::assertNotPushed(ProcessWhatsAppWebhookJob::class);
    }

    public function test_wati_webhook_route_rejects_invalid_authentication(): void
    {
        $this->configureWati();
        Queue::fake();

        $this->postJson('/api/whatsapp/webhook', [
            'eventType' => 'templateMessageSent_v2',
            'localMessageId' => 'local-route-2',
        ], [
            'Authorization' => 'Bearer wrong-secret',
        ])->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_wati_webhook_route_can_authenticate_with_a_query_token(): void
    {
        $this->configureWati();
        Queue::fake();

        $this->postJson('/api/whatsapp/webhook?provider=wati&token=test-wati-webhook-secret', [
            'eventType' => 'templateMessageSent_v2',
            'localMessageId' => 'local-route-3',
        ])->assertOk();

        Queue::assertPushed(ProcessWatiWebhookJob::class);
    }

    public function test_wati_webhook_route_rejects_requests_when_no_secret_is_configured(): void
    {
        config([
            'services.whatsapp.provider' => 'wati',
            'services.whatsapp.wati.enabled' => true,
            'services.whatsapp.wati.webhook_secret' => null,
        ]);
        Queue::fake();

        $this->postJson('/api/whatsapp/webhook', [
            'eventType' => 'templateMessageSent_v2',
            'localMessageId' => 'local-route-4',
        ])->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_wati_webhook_duplicate_events_are_ignored(): void
    {
        $notification = $this->notification('local-duplicate-1');
        $payload = [
            'eventType' => 'templateMessageFailed',
            'statusString' => 'Failed',
            'localMessageId' => 'local-duplicate-1',
            'failedCode' => '131026',
            'failedDetail' => 'Message undeliverable',
        ];

        (new ProcessWatiWebhookJob($payload))->handle();
        (new ProcessWatiWebhookJob($payload))->handle();

        $notification->refresh();

        $this->assertSame(NotificationStatus::Failed, $notification->status);
        $this->assertSame(1, $notification->logs()->count());
    }

    private function notification(string $externalId, NotificationStatus $status = NotificationStatus::Sent): Notification
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
            'message' => 'Message WATI',
            'provider' => 'wati',
            'status' => $status->value,
            'external_id' => $externalId,
        ]);
    }
}

<?php

namespace Tests\Feature\Push;

use App\Jobs\SendWebPushJob;
use App\Models\PushSubscription;
use App\Models\School;
use App\Models\User;
use App\Services\UserNotifications\UserNotificationService;
use App\Services\WebPush\LogPushProvider;
use App\Services\WebPush\MinishlinkPushProvider;
use App\Services\WebPush\PushProvider;
use App\Services\WebPush\PushResponse;
use App\Services\WebPush\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Intégration Web Push : le service de notifications in-app doit déclencher
 * l'envoi push, et le service Web Push doit gérer succès/expiration.
 */
class WebPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function user(?School $school = null): User
    {
        $school ??= School::factory()->create();

        return User::factory()->forSchool($school)->create(['role' => 'parent']);
    }

    private function fakeProvider(array $responses = []): void
    {
        $this->app->instance(PushProvider::class, new class($responses) implements PushProvider
        {
            public function __construct(private array $responses) {}

            public function sendMany(iterable $subscriptions, array $payload): array
            {
                $out = [];

                foreach ($subscriptions as $subscription) {
                    $out[$subscription->endpoint] = $this->responses[$subscription->endpoint]
                        ?? PushResponse::success();
                }

                return $out;
            }

            public function name(): string
            {
                return 'fake';
            }
        });
    }

    public function test_the_log_provider_is_used_by_default(): void
    {
        $this->assertSame('log', config('webpush.provider'));
        $this->assertInstanceOf(LogPushProvider::class, app(PushProvider::class));
    }

    public function test_the_minishlink_provider_is_resolved_when_configured(): void
    {
        config(['webpush.provider' => 'minishlink']);

        $this->assertInstanceOf(MinishlinkPushProvider::class, app(PushProvider::class));
    }

    public function test_a_push_job_is_dispatched_for_a_subscribed_user(): void
    {
        Queue::fake();

        $user = $this->user();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        app(UserNotificationService::class)->send(
            user: $user,
            type: 'evaluation.grades',
            title: 'Nouvelle note',
            message: 'Une nouvelle note est disponible.',
            data: ['url' => '/parent/dashboard'],
            sendEmail: false,
        );

        Queue::assertPushed(SendWebPushJob::class);
    }

    public function test_no_push_job_is_dispatched_without_subscription(): void
    {
        Queue::fake();

        $user = $this->user();

        app(UserNotificationService::class)->send(
            user: $user,
            type: 'evaluation.grades',
            title: 'Nouvelle note',
            message: 'Une nouvelle note est disponible.',
            data: ['url' => '/parent/dashboard'],
            sendEmail: false,
        );

        Queue::assertNotPushed(SendWebPushJob::class);
    }

    public function test_the_service_sends_to_all_subscriptions_and_touches_last_used_at(): void
    {
        $this->fakeProvider();

        $user = $this->user();
        $first = PushSubscription::factory()->create(['user_id' => $user->id]);
        $second = PushSubscription::factory()->create(['user_id' => $user->id]);

        $sent = app(WebPushService::class)->sendToUser($user, [
            'title' => 'Bulletin disponible',
            'body' => 'Le bulletin du 1er trimestre est disponible.',
        ]);

        $this->assertSame(2, $sent);
        $this->assertNotNull($first->fresh()->last_used_at);
        $this->assertNotNull($second->fresh()->last_used_at);
    }

    public function test_expired_subscriptions_are_pruned(): void
    {
        $user = $this->user();
        $subscription = PushSubscription::factory()->create(['user_id' => $user->id]);

        $this->fakeProvider([
            $subscription->endpoint => PushResponse::failure('Gone', expired: true),
        ]);

        $sent = app(WebPushService::class)->sendToUser($user, ['title' => 'Test']);

        $this->assertSame(0, $sent);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $subscription->id]);
    }

    public function test_a_notification_is_delivered_through_the_default_log_provider(): void
    {
        $user = $this->user();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        $notification = app(UserNotificationService::class)->send(
            user: $user,
            type: 'report_card.published',
            title: 'Bulletin publié',
            message: 'Votre bulletin est disponible.',
            data: ['url' => '/parent/report-cards/1'],
            sendEmail: false,
        );

        $sent = app(WebPushService::class)->sendNotification($notification);

        $this->assertSame(1, $sent);
    }
}

<?php

namespace Tests\Feature\Push;

use App\Models\PushSubscription;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Abonnements Web Push (PWA) de l'utilisateur connecté.
 */
class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function parent(?School $school = null): User
    {
        $school ??= School::factory()->create();

        return User::factory()->forSchool($school)->create(['role' => 'parent']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc-123',
            'keys' => [
                'p256dh' => 'BPublicKeyExample',
                'auth' => 'AuthTokenExample',
            ],
            'contentEncoding' => 'aes128gcm',
        ], $overrides);
    }

    public function test_a_guest_cannot_access_push_endpoints(): void
    {
        $this->get(route('push.vapid-key'))->assertRedirect(route('login'));
        $this->postJson(route('push.subscriptions.store'), $this->payload())->assertUnauthorized();
    }

    public function test_the_vapid_public_key_is_exposed(): void
    {
        config(['webpush.vapid.public_key' => 'PUBLIC_KEY_123']);

        $this->actingAs($this->parent())
            ->get(route('push.vapid-key'))
            ->assertOk()
            ->assertJson(['public_key' => 'PUBLIC_KEY_123']);
    }

    public function test_a_user_can_register_a_subscription(): void
    {
        $school = School::factory()->create();
        $user = $this->parent($school);

        $this->actingAs($user)
            ->postJson(route('push.subscriptions.store'), $this->payload())
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'school_id' => $school->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc-123',
            'public_key' => 'BPublicKeyExample',
            'auth_token' => 'AuthTokenExample',
            'content_encoding' => 'aes128gcm',
        ]);
    }

    public function test_registering_the_same_endpoint_again_updates_it(): void
    {
        $school = School::factory()->create();
        $user = $this->parent($school);

        $this->actingAs($user)->postJson(route('push.subscriptions.store'), $this->payload());

        $this->actingAs($user)->postJson(route('push.subscriptions.store'), $this->payload([
            'keys' => ['p256dh' => 'NEW_KEY', 'auth' => 'NEW_AUTH'],
        ]))->assertOk();

        $this->assertSame(1, PushSubscription::withoutGlobalScopes()->count());
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc-123',
            'public_key' => 'NEW_KEY',
        ]);
    }

    public function test_the_endpoint_is_required_and_must_be_valid(): void
    {
        $this->actingAs($this->parent())
            ->postJson(route('push.subscriptions.store'), ['endpoint' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['endpoint', 'keys']);
    }

    public function test_a_user_can_remove_their_subscription(): void
    {
        $user = $this->parent();

        $subscription = PushSubscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson(route('push.subscriptions.destroy'), ['endpoint' => $subscription->endpoint])
            ->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $subscription->id]);
    }

    public function test_a_user_cannot_remove_another_users_subscription(): void
    {
        $subscription = PushSubscription::factory()->create();
        $attacker = $this->parent();

        $this->actingAs($attacker)
            ->deleteJson(route('push.subscriptions.destroy'), ['endpoint' => $subscription->endpoint])
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', ['id' => $subscription->id]);
    }
}

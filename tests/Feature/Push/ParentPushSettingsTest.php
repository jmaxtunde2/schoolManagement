<?php

namespace Tests\Feature\Push;

use App\Models\PushSubscription;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Page « Notifications » du parent (opt-in PWA).
 */
class ParentPushSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function parent(?School $school = null): User
    {
        $school ??= School::factory()->create();

        return User::factory()->forSchool($school)->create(['role' => 'parent']);
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('parent.notifications.edit'))->assertRedirect(route('login'));
    }

    public function test_a_parent_can_open_the_notifications_page(): void
    {
        config(['webpush.vapid.public_key' => 'PUBLIC_KEY']);

        $user = $this->parent();
        PushSubscription::factory()->create([
            'user_id' => $user->id,
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ]);

        $this->actingAs($user)
            ->get(route('parent.notifications.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/PushNotifications')
                ->where('vapidPublicKey', 'PUBLIC_KEY')
                ->has('subscriptions', 1)
                ->where('subscriptions.0.device', 'Safari · iPhone')
            );
    }

    public function test_a_non_parent_cannot_open_the_notifications_page(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('parent.notifications.edit'))
            ->assertForbidden();
    }

    public function test_a_parent_can_send_a_test_notification(): void
    {
        $user = $this->parent();
        PushSubscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('parent.notifications.test'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_the_test_notification_reports_when_no_device_is_subscribed(): void
    {
        $user = $this->parent();

        $this->actingAs($user)
            ->post(route('parent.notifications.test'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_admin_must_pass_the_two_factor_challenge_before_the_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        // La 2FA étant obligatoire, la connexion ne donne pas
        // directement accès au tableau de bord.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge', [
                'return' => route('admin.dashboard'),
            ]));

        $this->assertAuthenticatedAs($user);

        // Tant que la 2FA n'est pas validée, la route protégée
        // reste inaccessible et renvoie vers le challenge.
        $this->get('/admin/dashboard')
            ->assertRedirect(route('two-factor.challenge', [
                'return' => url('/admin/dashboard'),
            ]));

        $this->verifyTwoFactorChallenge($user)
            ->assertRedirect(route('admin.dashboard'));

        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_teacher_lands_on_own_dashboard_after_the_two_factor_challenge(): void
    {
        $user = User::factory()->teacher()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge', [
                'return' => route('teacher.dashboard'),
            ]));

        $this->verifyTwoFactorChallenge($user)
            ->assertRedirect(route('teacher.dashboard'));
    }

    public function test_account_without_two_factor_configuration_is_sent_to_setup(): void
    {
        $user = User::factory()->admin()->withoutTwoFactor()->create();

        $this->actingAsWithoutTwoFactorVerification($user)
            ->get('/admin/dashboard')
            ->assertRedirect(route('two-factor.setup'));
    }

    public function test_invalid_two_factor_code_is_rejected(): void
    {
        $user = User::factory()->admin()->create();
        $this->enrolInTwoFactor($user);

        $this->actingAsWithoutTwoFactorVerification($user)
            ->from('/two-factor/challenge')
            ->post(route('two-factor.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        // La session reste authentifiée mais non vérifiée : la page
        // protégée est donc toujours redirigée vers le challenge.
        $this->get('/admin/dashboard')
            ->assertRedirect(route('two-factor.challenge', [
                'return' => url('/admin/dashboard'),
            ]));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->get('/teacher/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/settings')->assertRedirect(route('login'));
    }
}

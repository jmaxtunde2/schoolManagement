<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthReturnTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogle(): void
    {
        $this->mock(GoogleOAuthService::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('state')->andReturn('state-token');
            $mock->shouldReceive('authorizationUrl')->andReturn(
                'https://accounts.google.com/o/oauth2/v2/auth'
            );
        });
    }

    private function mockGoogleCallback(string $email, string $sub = 'google-sub-1'): void
    {
        $this->mock(GoogleOAuthService::class, function ($mock) use ($email, $sub) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('exchange')->andReturn(['access_token' => 'token']);
            $mock->shouldReceive('user')->andReturn([
                'sub' => $sub,
                'email' => $email,
                'email_verified' => true,
                'picture' => 'https://example.com/avatar.png',
            ]);
        });
    }

    public function test_a_protocol_relative_return_url_is_rejected(): void
    {
        $this->mockGoogle();

        $this->get(route('google.redirect', ['return' => '//evil.com']))
            ->assertSessionHas('google_oauth_return', route('login'));
    }

    public function test_an_internal_return_url_is_kept(): void
    {
        $this->mockGoogle();

        $this->get(route('google.redirect', ['return' => '/admin/dashboard']))
            ->assertSessionHas('google_oauth_return', '/admin/dashboard');
    }

    public function test_a_suspended_school_blocks_the_google_callback(): void
    {
        $school = School::factory()->create(['is_active' => false]);
        $user = User::factory()->admin()->forSchool($school)->create([
            'email' => 'blocked@example.test',
        ]);
        $this->mockGoogleCallback($user->email);

        $this->withSession(['google_oauth_state' => 'state-token'])
            ->get(route('google.callback', ['state' => 'state-token', 'code' => 'abc']))
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_an_active_school_user_passes_the_google_callback(): void
    {
        $school = School::factory()->create(['is_active' => true]);
        $user = User::factory()->admin()->forSchool($school)->create([
            'email' => 'allowed@example.test',
        ]);
        $this->mockGoogleCallback($user->email);

        $this->withSession(['google_oauth_state' => 'state-token'])
            ->get(route('google.callback', ['state' => 'state-token', 'code' => 'abc']))
            ->assertRedirect(route('two-factor.challenge', [
                'return' => route(Role::Admin->homeRoute()),
            ]));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_platform_admin_passes_the_google_callback(): void
    {
        $user = User::factory()->create([
            'role' => Role::PlatformAdmin,
            'school_id' => null,
            'email' => 'platform@example.test',
        ]);
        $this->mockGoogleCallback($user->email);

        $this->withSession(['google_oauth_state' => 'state-token'])
            ->get(route('google.callback', ['state' => 'state-token', 'code' => 'abc']))
            ->assertRedirect(route('two-factor.challenge', [
                'return' => route(Role::PlatformAdmin->homeRoute()),
            ]));

        $this->assertAuthenticatedAs($user);
    }
}

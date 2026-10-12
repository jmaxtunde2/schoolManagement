<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\GoogleOAuthService;
use Tests\TestCase;

class GoogleAuthReturnTest extends TestCase
{
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
}

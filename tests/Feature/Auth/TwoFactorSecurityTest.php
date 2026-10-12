<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TwoFactor\TotpService;
use App\Services\TwoFactor\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TwoFactorSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_two_factor_challenge_endpoint_is_rate_limited(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAsWithoutTwoFactorVerification($user);

        $status = null;
        foreach (range(1, 7) as $i) {
            $status = $this->post(route('two-factor.verify'), ['code' => '000000'])
                ->getStatusCode();
        }

        $this->assertSame(429, $status);
    }

    public function test_the_two_factor_service_locks_out_after_ten_failures(): void
    {
        $user = User::factory()->admin()->create();
        $twoFactor = app(TwoFactorService::class);
        $request = Request::create('/two-factor/challenge', 'POST');

        foreach (range(1, 10) as $i) {
            $this->assertFalse($twoFactor->verify($user, '000000', $request));
        }

        // La onzième tentative est bloquée avant même la vérification du code.
        $this->expectException(ValidationException::class);

        $twoFactor->verify($user, '000000', $request);
    }

    public function test_a_totp_code_cannot_be_replayed(): void
    {
        $user = User::factory()->admin()->create();

        $secret = app(TwoFactorService::class)->begin($user)['secret'];
        $code = app(TotpService::class)->currentCode($secret);

        $this->actingAsWithoutTwoFactorVerification($user->fresh());

        $this->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect();

        // Le même code, dans la même fenêtre de validité, est rejeté.
        $this->post(route('two-factor.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');
    }
}

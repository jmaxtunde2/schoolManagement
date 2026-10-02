<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TwoFactor\TotpService;
use App\Services\TwoFactor\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTotpTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        $user = User::factory()->admin()->create();
        $twoFactor = app(TwoFactorService::class);
        $totp = app(TotpService::class);

        $returned = $twoFactor->begin($user)['secret'];

        $inMemory = $user->fresh()->two_factor_secret;

        fwrite(STDERR, "\nreturned   : " . $returned . "\n");
        fwrite(STDERR, "in DB      : " . $inMemory . "\n");
        fwrite(STDERR, "equal      : " . var_export($returned === $inMemory, true) . "\n");
        fwrite(STDERR, "code       : " . $totp->currentCode($returned) . "\n");
        fwrite(STDERR, "verify     : " . var_export($totp->verify($inMemory, $totp->currentCode($returned)), true) . "\n");

        $this->assertTrue(true);
    }
}

<?php

namespace Tests;

use App\Models\User;
use App\Services\TwoFactor\TotpService;
use App\Services\TwoFactor\TwoFactorService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Les tests n'ont pas besoin du build Vite.
        $this->withoutVite();

        // La 2FA TOTP est obligatoire : la session de test est donc
        // pré-validée, comme après un challenge réussi. Les tests qui
        // exercent l'enforcement de la 2FA utilisent `withoutTwoFactor()`
        // et `actingAsWithoutTwoFactorVerification()`.
        $this->markTwoFactorVerified();
    }

    /**
     * Simule une session dont la vérification TOTP a déjà abouti.
     */
    protected function markTwoFactorVerified(): static
    {
        $this->withSession(['two_factor_verified_at' => now()->timestamp]);

        return $this;
    }

    /**
     * Simule une session authentifiée mais non vérifiée par la 2FA.
     */
    protected function actingAsWithoutTwoFactorVerification(User $user): static
    {
        $this->flushSession();

        return $this->actingAs($user);
    }

    /**
     * Simule une session dont une action sensible a été revalidée.
     */
    protected function markSensitiveTwoFactorVerified(string $purpose): static
    {
        $this->withSession([
            'two_factor_sensitive.'.$purpose => now()->timestamp,
        ]);

        return $this;
    }

    /**
     * Lance la configuration 2FA d'un utilisateur et renvoie
     * le code TOTP correspondant au compteur courant.
     */
    protected function enrolInTwoFactor(
        User $user,
        ?TwoFactorService $twoFactor = null,
        ?TotpService $totp = null
    ): string {
        $secret = ($twoFactor ?? app(TwoFactorService::class))->begin($user)['secret'];

        return ($totp ?? app(TotpService::class))->currentCode($secret);
    }

    /**
     * Soumet un challenge 2FA valide pour l'utilisateur donné.
     */
    protected function verifyTwoFactorChallenge(
        User $user,
        ?TwoFactorService $twoFactor = null,
        ?TotpService $totp = null
    ): TestResponse {
        $code = $this->enrolInTwoFactor($user, $twoFactor, $totp);

        /*
         * Le secret vient d'être écrit en base. Le guard a pu résoudre
         * une instance de User avant cet enregistrement (par exemple
         * pendant une connexion), encore dépourvue du secret : on
         * recharge l'utilisateur pour ne pas vérifier un secret périmé.
         */
        $this->actingAs($user->fresh());

        return $this->post(route('two-factor.verify'), ['code' => $code]);
    }
}

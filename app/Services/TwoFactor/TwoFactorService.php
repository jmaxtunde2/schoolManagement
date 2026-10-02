<?php

namespace App\Services\TwoFactor;

use App\Models\TwoFactorEvent;
use App\Models\User;
use Illuminate\Http\Request;

class TwoFactorService
{
    public function __construct(
        private TotpService $totp,
        private RecoveryCodeService $recovery
    ) {
    }

    /**
     * Commence la configuration de la 2FA.
     *
     * Génère le secret TOTP et les codes de récupération
     * si la 2FA n'est pas encore configurée.
     */
    public function begin(User $user): array
    {
        // Si un secret existe déjà mais que la configuration
        // n'a pas encore été confirmée, on le réutilise.
        if ($user->two_factor_secret && ! $user->two_factor_confirmed_at) {
            $secret = $user->two_factor_secret;

            // Les codes de récupération sont déjà stockés sous
            // forme chiffrée/hashée. Ils ne peuvent donc pas être
            // affichés à nouveau ici.
            $recoveryCodes = [];
        } else {
            // Première configuration : génération du secret TOTP.
            $secret = $this->totp->generateSecret();

            // Génération des codes de récupération.
            $codes = $this->recovery->generate();

            $recoveryCodes = $codes['plain'];

            $user->forceFill([
                'two_factor_secret' => $secret,
                'two_factor_recovery_codes' => $codes['hashes'],
            ])->save();

            $this->event(
                $user,
                'setup_started',
                request()
            );
        }

        return [
            'secret' => $secret,
            'recovery_codes' => $recoveryCodes,
            'otpauth_uri' => $this->totp->otpauthUri(
                $secret,
                $user->email,
                config('app.name')
            ),
        ];
    }

    /**
     * Confirme la configuration initiale de la 2FA
     * avec un code TOTP à 6 chiffres.
     */
    public function confirm(
        User $user,
        string $code,
        Request $request
    ): bool {
        if (
            ! $user->two_factor_secret ||
            ! $this->totp->verify(
                $user->two_factor_secret,
                $code
            )
        ) {
            $this->event(
                $user,
                'login_failed',
                $request
            );

            return false;
        }

        $user->forceFill([
            'two_factor_enabled_at' => now(),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_used_at' => now(),
        ])->save();

        $this->markLoginVerified($request);

        $this->event(
            $user,
            'setup_confirmed',
            $request
        );

        return true;
    }

    /**
     * Vérifie un code TOTP ou un code de récupération.
     */
    public function verify(
        User $user,
        string $code,
        Request $request
    ): bool {
        // Vérification avec l'application Authenticator.
        if (
            $user->two_factor_secret &&
            $this->totp->verify(
                $user->two_factor_secret,
                $code
            )
        ) {
            $user->forceFill([
                'two_factor_last_used_at' => now(),
            ])->save();

            $this->markLoginVerified($request);

            $this->event(
                $user,
                'login_verified',
                $request
            );

            return true;
        }

        // Si le code TOTP est invalide, on essaie un
        // code de récupération à usage unique.
        if ($user->two_factor_recovery_codes) {
            [$valid, $remainingCodes] = $this->recovery->consume(
                $user->two_factor_recovery_codes,
                $code
            );

            if ($valid) {
                $user->forceFill([
                    'two_factor_recovery_codes' => $remainingCodes,
                    'two_factor_last_used_at' => now(),
                ])->save();

                $this->markLoginVerified($request);

                $this->event(
                    $user,
                    'recovery_code_used',
                    $request
                );

                return true;
            }
        }

        // Aucun moyen de vérification valide.
        $this->event(
            $user,
            'login_failed',
            $request
        );

        return false;
    }

    /**
     * Marque la session comme ayant passé la vérification 2FA.
     *
     * Cette validation reste valable pendant 15 minutes.
     */
    public function markLoginVerified(Request $request): void
    {
        $request->session()->put(
            'two_factor_verified_at',
            now()->timestamp
        );
    }

    /**
     * Vérifie si la session possède une validation 2FA récente.
     */
    public function loginVerified(Request $request): bool
    {
        $verifiedAt = (int) $request->session()->get(
            'two_factor_verified_at',
            0
        );

        return $verifiedAt >= now()
            ->subMinutes(15)
            ->timestamp;
    }

    /**
     * Marque une action sensible comme ayant été
     * récemment vérifiée par 2FA.
     */
    public function markSensitiveVerified(
        Request $request,
        string $purpose
    ): void {
        $request->session()->put(
            'two_factor_sensitive.' . $purpose,
            now()->timestamp
        );
    }

    /**
     * Vérifie si une action sensible possède
     * une validation 2FA récente.
     *
     * Durée : 5 minutes.
     */
    public function sensitiveVerified(
        Request $request,
        string $purpose
    ): bool {
        $verifiedAt = (int) $request->session()->get(
            'two_factor_sensitive.' . $purpose,
            0
        );

        return $verifiedAt >= now()
            ->subMinutes(5)
            ->timestamp;
    }

    /**
     * Enregistre un événement lié à la 2FA.
     */
    public function event(
        User $user,
        string $event,
        Request $request
    ): void {
        TwoFactorEvent::create([
            'school_id' => $user->school_id,
            'user_id' => $user->id,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => substr(
                (string) $request->userAgent(),
                0,
                2000
            ),
        ]);
    }
}

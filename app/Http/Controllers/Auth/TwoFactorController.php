<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactor\TwoFactorService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    /**
     * Affiche la page de configuration du 2FA.
     */
    public function setup(
        Request $request,
        TwoFactorService $twoFactor
    ): Response|RedirectResponse {
        $user = $request->user();

        if ($user->two_factor_confirmed_at) {
            return redirect()->route('two-factor.challenge');
        }

        $data = $twoFactor->begin($user);

        // Les codes en clair ne sont disponibles que pendant la configuration.
        $request->session()->put(
            'two_factor_recovery_plain',
            $data['recovery_codes']
        );

        return Inertia::render(
            'Auth/TwoFactor/Setup',
            $data
        );
    }

    /**
     * Confirme la configuration du 2FA avec un code TOTP.
     */
    public function confirm(
        Request $request,
        TwoFactorService $twoFactor
    ): RedirectResponse {
        $request->validate([
            'code' => [
                'required',
                'digits:6',
            ],
        ]);

        $user = $request->user();

        if (! $twoFactor->confirm(
            $user,
            $request->input('code'),
            $request
        )) {
            return back()->withErrors([
                'code' => 'Code Authenticator invalide.',
            ]);
        }

        return redirect()
            ->route('two-factor.recovery')
            ->with(
                'success',
                'Authentification à deux facteurs activée.'
            );
    }

    /**
     * Affiche les codes de récupération une seule fois.
     */
    public function recovery(
        Request $request
    ): Response {
        return Inertia::render(
            'Auth/TwoFactor/Recovery',
            [
                'codes' => $request->session()->pull(
                    'two_factor_recovery_plain',
                    []
                ),
            ]
        );
    }

    /**
     * Affiche le challenge 2FA.
     *
     * Le challenge peut être utilisé :
     * - pour le login ;
     * - pour une action sensible ;
     * - pour toute autre action nécessitant une vérification récente.
     */
    public function challenge(
        Request $request
    ): Response|RedirectResponse {
        $user = $request->user();

        if (! $user->two_factor_confirmed_at) {
            return redirect()->route('two-factor.setup');
        }

        $purpose = $request
            ->string('purpose')
            ->toString();

        if ($purpose === '') {
            $purpose = 'login';
        }

        $return = $request
            ->string('return')
            ->toString();

        if ($return === '') {
            $return = route(
                $user->role->homeRoute(),
                [],
                false
            );
        }

        $pendingAction = null;

        if ($purpose !== 'login') {
            $pendingKey = 'two_factor_pending_actions.'.$purpose;
            $encryptedAction = $request->session()->get($pendingKey);

            if (is_string($encryptedAction)) {
                try {
                    $action = Crypt::decrypt($encryptedAction);

                    if (
                        is_array($action)
                        && ($action['created_at'] ?? 0) >= now()->subMinutes(5)->timestamp
                        && is_string($action['url'] ?? null)
                        && str_starts_with($action['url'], '/')
                        && ! str_starts_with($action['url'], '//')
                        && in_array($action['method'] ?? null, ['post', 'put', 'patch', 'delete'], true)
                        && is_array($action['data'] ?? null)
                    ) {
                        $pendingAction = [
                            'url' => $action['url'],
                            'method' => $action['method'],
                            'data' => $action['data'],
                        ];
                    } else {
                        $request->session()->forget($pendingKey);
                    }
                } catch (DecryptException) {
                    $request->session()->forget($pendingKey);
                }
            }
        }

        return Inertia::render(
            'Auth/TwoFactor/Challenge',
            [
                'purpose' => $purpose,
                'return' => $return,
                'pendingAction' => $pendingAction,
            ]
        );
    }

    /**
     * Vérifie le code TOTP ou un code de récupération.
     */
    public function verify(
        Request $request,
        TwoFactorService $twoFactor
    ): RedirectResponse {
        $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
            ],
            'purpose' => [
                'nullable',
                'string',
                'max:80',
            ],
            'return' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $user = $request->user();

        if (! $twoFactor->verify(
            $user,
            $request->input('code'),
            $request
        )) {
            return back()->withErrors([
                'code' => 'Code Authenticator ou code de récupération invalide.',
            ]);
        }

        $purpose = $request
            ->string('purpose')
            ->toString();

        /*
         * Pour une action sensible, on conserve une
         * validation fraîche pendant 5 minutes.
         */
        if ($purpose !== '' && $purpose !== 'login') {
            $twoFactor->markSensitiveVerified(
                $request,
                $purpose
            );

            if ($request->session()->has('two_factor_pending_actions.'.$purpose)) {
                return redirect()->route('two-factor.challenge', [
                    'purpose' => $purpose,
                    'return' => $request->input('return'),
                ]);
            }
        }

        /*
         * Toujours utiliser une URL interne relative.
         *
         * Cela évite :
         * - les redirections vers une URL absolue ;
         * - les problèmes avec str_starts_with();
         * - les risques d'open redirect.
         */
        $return = $request
            ->string('return')
            ->toString();

        if (
            $return === '' ||
            ! str_starts_with($return, '/') ||
            str_starts_with($return, '//')
        ) {
            $return = route(
                $user->role->homeRoute(),
                [],
                false
            );
        }

        return redirect()->to($return);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purpose' => ['nullable', 'string', 'max:80'],
            'return' => ['nullable', 'string', 'max:2000'],
        ]);

        $purpose = $validated['purpose'] ?? '';

        if ($purpose !== '' && $purpose !== 'login') {
            $request->session()->forget('two_factor_pending_actions.'.$purpose);
        }

        $return = $validated['return'] ?? '';

        if (
            $return === '' ||
            ! str_starts_with($return, '/') ||
            str_starts_with($return, '//')
        ) {
            $return = route($request->user()->role->homeRoute(), [], false);
        }

        return redirect()->to($return);
    }
}

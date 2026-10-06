<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    public function __construct(private GoogleOAuthService $google) {}

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless($this->google->enabled(), 503, 'Google Authentication n’est pas encore configurée.');
        $state = $this->google->state();
        $request->session()->put('google_oauth_state', $state);
        $request->session()->put('google_oauth_reauth', (bool) $request->boolean('reauth'));
        $return = $request->string('return')->toString();
        if (! str_starts_with($return, '/')) {
            $return = route('login');
        } $request->session()->put('google_oauth_return', $return);

        return redirect()->away($this->google->authorizationUrl($state, $request->boolean('reauth')));
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->google->enabled(), 503);
        abort_unless(hash_equals((string) $request->session()->pull('google_oauth_state'), (string) $request->string('state')), 419, 'Session Google invalide.');
        $tokens = $this->google->exchange($request->string('code')->toString());
        $profile = $this->google->user($tokens['access_token']);
        $user = User::withoutGlobalScopes()->where('google_id', $profile['sub'] ?? '')->orWhere('email', strtolower($profile['email'] ?? ''))->first();
        abort_unless(($profile['email_verified'] ?? false) && $user && $user->is_active && ($user->school_id || $user->isPlatformAdmin()), 403, 'Votre compte Google n’est pas autorisé sur la plateforme. Contactez votre établissement.');
        $user->forceFill(['google_id' => $profile['sub'] ?? $user->google_id, 'google_email' => strtolower($profile['email'] ?? $user->email), 'google_avatar' => $profile['picture'] ?? null, 'google_verified_at' => now(), 'google_reauthenticated_at' => now()])->save();
        Auth::login($user, true);
        $request->session()->regenerate();
        $return = $request->session()->pull('google_oauth_return');
        if ($request->session()->pull('google_oauth_reauth')) {
            return redirect()->to($return ?: route($user->role->homeRoute()));
        }

return redirect()->route('two-factor.challenge', ['return' => route($user->role->homeRoute())]);
    }
}

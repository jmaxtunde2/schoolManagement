<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleOAuthService
{
    public function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret')) && filled(config('services.google.redirect'));
    }

    public function authorizationUrl(string $state, bool $reauth = false): string
    {
        $params = http_build_query(['client_id' => config('services.google.client_id'), 'redirect_uri' => config('services.google.redirect'), 'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $state, 'access_type' => 'offline', 'prompt' => $reauth ? 'login' : 'select_account']);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.$params;
    }

    public function exchange(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', ['code' => $code, 'client_id' => config('services.google.client_id'), 'client_secret' => config('services.google.client_secret'), 'redirect_uri' => config('services.google.redirect'), 'grant_type' => 'authorization_code']);
        abort_unless($response->successful(), 502, 'Google OAuth token exchange failed.');

        return $response->json();
    }

    public function user(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get('https://openidconnect.googleapis.com/v1/userinfo');
        abort_unless($response->successful(), 502, 'Impossible de récupérer le profil Google.');

        return $response->json();
    }

    public function state(): string
    {
        return Str::random(64);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\School;
use App\Services\Tenancy\TenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request, TenantResolver $resolver): Response
    {
        $school = $resolver->resolve($request);

        // Fallback: sur le domaine plateforme (localhost, 127.0.0.1), prendre la 1ère école active
        if (! $school && $this->isPlatformHost($request)) {
            $school = School::query()->where('is_active', true)->first();
        }

        return Inertia::render('Auth/Login', [
            'school' => $school ? [
                'name' => $school->name,
                'primary_color' => $school->settings->primary_color ?? '#1E40AF',
                'secondary_color' => $school->settings->secondary_color ?? '#0F766E',
                'accent_color' => $school->settings->accent_color ?? '#D97706',
                'logo_url' => $school->settings->logo_url ?? null,
            ] : null,
        ]);
    }

    private function isPlatformHost(Request $request): bool
    {
        $host = strtolower($request->getHost());
        return in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true)
            || str_ends_with($host, ':8000');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->route('two-factor.challenge', ['return' => route($request->user()->role->homeRoute())]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

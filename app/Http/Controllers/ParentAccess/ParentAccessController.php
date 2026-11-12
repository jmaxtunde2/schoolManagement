<?php

namespace App\Http\Controllers\ParentAccess;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Services\ParentAccess\ParentAccessCodeService;
use App\Services\ParentAccess\ParentAccessSessionService;
use App\Services\Tenancy\TenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ParentAccessController extends Controller
{
    public function create(Request $request, TenantResolver $resolver, ParentAccessSessionService $sessionService): Response|RedirectResponse
    {
        $school = $resolver->resolve($request);
        $slug = $request->route('slug');
        if (! $school && $slug) {
            $school = $resolver->resolveBySlug($slug);
        }

        if (! $school) {
            abort(404);
        }

        // If already has valid parent access session, redirect to parent space
        $token = $request->cookie('parent_access_token') ?? $request->session()->get('parent_access_token');
        if ($sessionService->findValidForSchool($token, $school->id)) {
            return redirect()->route('parent-access.dashboard.slug', ['slug' => $school->slug]);
        }

        return Inertia::render('ParentAccess/Login', [
            'school' => [
                'name' => $school->name,
                'slug' => $school->slug,
                'logo_url' => $school->settings?->logo_url ?? null,
                'primary_color' => $school->settings?->primary_color ?? '#10b981',
            ],
        ]);
    }

    public function store(Request $request, TenantResolver $resolver, ParentAccessCodeService $codeService, ParentAccessSessionService $sessionService): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/'],
        ]);

        $school = $resolver->resolve($request);
        $slug = $request->route('slug');
        if (! $school && $slug) {
            $school = $resolver->resolveBySlug($slug);
        }

        if (! $school) {
            abort(404);
        }

        $plainCodeInput = strtoupper(trim($request->input('code')));
        $key = 'parent-access:'.$request->ip().':'.Str::slug($plainCodeInput);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors(['code' => 'Trop de tentatives. Veuillez réessayer dans '.max(1, ceil($seconds / 60)).' minute(s).']);
        }
        RateLimiter::hit($key, 300);

        $accessCode = $codeService->findByPlainCode($plainCodeInput);

        // Generic error message to prevent enumeration
        if (! $accessCode) {
            return back()->withErrors(['code' => 'Code d\'accès invalide ou expiré.']);
        }

        if (! $accessCode->isValid()) {
            return back()->withErrors(['code' => 'Code d\'accès invalide ou expiré.']);
        }

        if ((int) $accessCode->school_id !== (int) $school->id) {
            return back()->withErrors(['code' => 'Code d\'accès invalide ou expiré.']);
        }

        $parent = $accessCode->parent;
        if (! $parent) {
            return back()->withErrors(['code' => 'Code d\'accès invalide ou expiré.']);
        }

        if (! $parent->is_active) {
            return back()->withErrors(['code' => 'Code d\'accès invalide ou expiré.']);
        }

        // Check parent has at least one child in this school
        $hasChildInSchool = $parent->students()->where('students.school_id', $school->id)->exists();
        if (! $hasChildInSchool) {
            return back()->withErrors(['code' => 'Code d\'accès invalide ou expiré.']);
        }

        // Mark code as used
        $accessCode->update(['used_at' => now()]);

        // Get current academic year
        $academicYear = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('is_current', true)
            ->first();

        // Create session
        $sessionData = $sessionService->createSession($parent, $school, $academicYear, $request);
        $plainToken = $sessionData['plain_token'];

        // Set cookie
        $expiresMinutes = $academicYear && $academicYear->ends_on ?
            (int) ceil(now()->diffInMinutes($academicYear->ends_on->copy()->endOfDay())) :
            60 * 24 * 365 * 2;
        if ($expiresMinutes < 60) {
            $expiresMinutes = 60 * 24 * 365;
        }
        $cookie = cookie('parent_access_token', $plainToken,
            $expiresMinutes,
            '/',
            null,
            app()->environment('production'),
            true, // httpOnly
            false,
            'lax'
        );

        RateLimiter::clear($key);

        $redirectRoute = $slug ? route('parent-access.dashboard.slug', ['slug' => $slug]) : route('parent-access.dashboard');

        return redirect($redirectRoute)->withCookie($cookie);
    }
}

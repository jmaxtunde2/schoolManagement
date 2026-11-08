<?php

namespace App\Http\Controllers\ParentAccess;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Services\Tenancy\TenantResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParentAccessDashboardController extends Controller
{
    public function __invoke(Request $request, TenantResolver $resolver, string $slug = null): Response
    {
        $school = $slug ? $resolver->resolveBySlug($slug) : $resolver->resolve($request);
        if (! $school) {
            abort(404);
        }

        $parent = $request->attributes->get('parent_access_parent');
        if (! $parent) {
            abort(403);
        }

        // Verify parent belongs to this school
        if ((int)$parent->school_id !== (int)$school->id) {
            abort(403);
        }

        // Get children of this parent in this school
        $students = $parent->students()
            ->where('students.school_id', $school->id)
            ->with('classRoom')
            ->get();

        return Inertia::render('ParentAccess/Dashboard', [
            'school' => [
                'name' => $school->name,
                'slug' => $school->slug,
                'logo_url' => $school->settings?->logo_url ?? null,
            ],
            'parent' => [
                'name' => $parent->name,
            ],
            'children' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'matricule' => $student->matricule,
                    'class_name' => $student->classRoom?->name,
                ];
            })->values(),
        ]);
    }

    public function logout(Request $request, TenantResolver $resolver, string $slug = null)
    {
        $school = $slug ? $resolver->resolveBySlug($slug) : $resolver->resolve($request);
        $token = $request->cookie('parent_access_token') ?? $request->session()->get('parent_access_token');
        if ($token && $school) {
            $tokenHash = hash('sha256', $token);
            $session = \App\Models\ParentAccessSession::query()->where('token_hash', $tokenHash)->first();
            if ($session && (int)$session->school_id === (int)$school->id) {
                $session->update(['revoked_at' => now()]);
            }
        } elseif ($token) {
            $tokenHash = hash('sha256', $token);
            $session = \App\Models\ParentAccessSession::query()->where('token_hash', $tokenHash)->first();
            if ($session) {
                $session->update(['revoked_at' => now()]);
            }
        }

        $cookie = cookie()->forget('parent_access_token');
        $redirect = $slug ? route('parent-access.login.slug', ['slug' => $slug]) : route('parent-access.login');
        return redirect($redirect)->withCookie($cookie);
    }
}

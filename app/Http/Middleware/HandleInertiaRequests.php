<?php

namespace App\Http\Middleware;

use App\Support\SchoolBranding;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                ] : null,
            ],
            'school' => fn () => SchoolBranding::for($user?->school),
            'parentAccess' => function () use ($request) {
                $parent = $request->attributes->get('parent_access_parent');
                $school = $request->attributes->get('parent_access_school');

                if (! $parent) {
                    return null;
                }

                return [
                    'parent' => [
                        'id' => $parent->id,
                        'name' => $parent->name,
                    ],
                    'school' => [
                        'id' => $school?->id,
                        'name' => $school?->name,
                        'slug' => $school?->slug,
                        'logo_url' => $school?->settings?->logo_url,
                    ],
                    'dashboard_url' => $school
                        ? route('parent-access.dashboard.slug', ['slug' => $school->slug])
                        : route('parent-access.dashboard'),
                    'logout_url' => $school
                        ? route('parent-access.logout.slug', ['slug' => $school->slug])
                        : route('parent-access.logout'),
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'generated_code' => fn () => $request->session()->get('generated_code'),
                'parent_name' => fn () => $request->session()->get('parent_name'),
                'parent_access_bulk_errors' => fn () => $request->session()->get('parent_access_bulk_errors'),
            ],
            'import_results' => fn () => $request->session()->get('import_results'),
        ];
    }
}

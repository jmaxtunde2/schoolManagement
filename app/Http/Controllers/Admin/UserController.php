<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->where('school_id', $request->user()->school_id)
            ->where('id', '!=', $request->user()->id)
            ->latest()
            ->get([
                'id',
                'name',
                'email',
                'role',
                'is_active',
                'google_email',
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->map(
                fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'is_active' => $user->is_active,
                    'google_email' => $user->google_email,
                ]
            ),

            'roles' => collect([
                Role::Admin,
                Role::Censeur,
                Role::Secretary,
                Role::Teacher,
            ])->map(
                fn ($role) => [
                    'value' => $role->value,
                    'label' => $role->label(),
                ]
            ),
        ]);
    }

    public function store(
        Request $request,
        AuditService $audit
    ): RedirectResponse {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
            ],
            'role' => [
                'required',
                Rule::in([
                    'admin',
                    'censeur',
                    'secretary',
                ]),
            ],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make(
                $data['password'] ?? str()->random(32)
            ),
        ]);

        $user->forceFill([
            'school_id' => $request->user()->school_id,
            'role' => $data['role'],
            'is_active' => true,
        ])->save();

        $audit->log(
            'user.created',
            $user,
            [
                'role' => $data['role'],
            ]
        );

        return back()->with(
            'success',
            'Utilisateur créé. Il pourra se connecter avec Google lorsque son adresse sera associée.'
        );
    }

    public function update(
        Request $request,
        User $user,
        AuditService $audit
    ): RedirectResponse {
        abort_unless(
            (int) $user->school_id === (int) $request->user()->school_id
                && $user->id !== $request->user()->id,
            403
        );

        if (
            ! $request->session()->has(
                'two_factor_sensitive.manage_users'
            )
        ) {
            return redirect()
                ->route('two-factor.challenge', [
                    'purpose' => 'manage_users',
                    'return' => url()->previous(),
                ])
                ->with(
                    'error',
                    'Un code Authenticator est requis.'
                );
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'role' => [
                'required',
                Rule::in([
                    'admin',
                    'censeur',
                    'secretary',
                    'teacher',
                ]),
            ],
            'is_active' => [
                'boolean',
            ],
        ]);

        $user->update([
            'name' => $data['name'],
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? false,
        ]);

        $audit->log(
            'user.access_updated',
            $user,
            [
                'role' => $data['role'],
                'is_active' => $data['is_active'] ?? false,
            ]
        );

        return back()->with(
            'success',
            'Accès utilisateur mis à jour.'
        );
    }
}

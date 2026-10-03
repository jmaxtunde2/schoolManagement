<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'is_email_verified' => $user->email_verified_at !== null,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'school_name' => $user->school?->name,
                'created_at' => $user->created_at?->isoFormat('LL'),
                'avatar_url' => $user->google_avatar,
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                'two_factor_enabled_at' => $user->two_factor_enabled_at?->isoFormat('LL'),
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        /*
         * Aucune chaîne de vérification d'email n'existe dans l'application.
         * L'identité vient d'être prouvée par le mot de passe actuel : on
         * préserve donc l'état de vérification plutôt que de le perdre.
         */
        if ($user->isDirty('email') && $user->email_verified_at !== null) {
            $user->email_verified_at = now();
        }

        $user->save();

        return back()->with('success', 'Votre profil a été mis à jour.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        /*
         * On invalide les autres sessions AVANT de changer le mot de passe :
         * `logoutOtherDevices` compare le mot de passe fourni à celui stocké.
         */
        Auth::logoutOtherDevices($validated['current_password']);

        $user->update(['password' => $validated['password']]);

        /*
         * La rotation du jeton « se souvenir de moi » ne suffit pas : le
         * pilote de session étant « database », les sessions actives des
         * autres appareils restent en base jusqu'à leur expiration. On les
         * supprime, en conservant celle de l'appareil courant.
         */
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('success', 'Votre mot de passe a été modifié. Vos autres sessions ont été déconnectées.');
    }
}
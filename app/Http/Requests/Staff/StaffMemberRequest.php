<?php

namespace App\Http\Requests\Staff;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Création / modification d'un membre du personnel depuis Administration → Personnel.
 *
 * Points de sécurité :
 * - `school_id` n'est ni lu ni validé : il est toujours celui de l'utilisateur connecté,
 *   il est donc impossible d'attacher un membre du personnel à une autre école ;
 * - le rôle est restreint à Role::assignableStaff() (ni platform_admin, ni parent) ;
 * - l'email est unique globalement sur `users`, puisqu'il sert d'identifiant de connexion.
 */
class StaffMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        $member = $this->route('staff');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                // `staff` est un User : on ignore sa propre ligne, sinon un email
                // inchangé serait refusé à la modification.
                Rule::unique('users', 'email')->ignore($member?->id),
            ],

            'phone' => ['nullable', 'string', 'regex:/^[0-9+\s().-]{6,30}$/'],

            'role' => [
                'required',
                Rule::in(array_map(fn (Role $role) => $role->value, Role::assignableStaff())),
            ],

            'is_active' => ['required', 'boolean'],

            // Requis à la création seulement : en modification, un mot de passe
            // laissé vide conserve l'existant.
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:8'],

            'photo' => [
                'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:min_width=64,min_height=64,max_width=2000,max_height=2000',
            ],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'email',
            'phone' => 'téléphone',
            'role' => 'rôle',
            'is_active' => 'statut',
            'password' => 'mot de passe',
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Ce rôle ne fait pas partie du personnel de l’établissement.',
            'photo.mimes' => 'La photo doit être une image JPG, PNG ou WebP.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'photo.dimensions' => 'La photo doit mesurer entre 64×64 et 2000×2000 pixels.',
        ];
    }

    public function fullName(): string
    {
        return trim($this->validated('first_name').' '.$this->validated('last_name'));
    }

    /** Mot de passe à hasher, ou null s'il faut conserver l'existant. */
    public function hashedPassword(): ?string
    {
        $password = $this->validated('password');

        return filled($password) ? Hash::make($password) : null;
    }
}

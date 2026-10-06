<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Un profil est strictement personnel : seul l'intéressé peut l'éditer.
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }

        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],

            /*
             * L'adresse email sert d'identifiant de connexion : la modifier
             * exige de prouver que l'on est bien le titulaire du compte.
             */
            'current_password' => [
                Rule::requiredIf(fn () => $this->emailChanged()),
                'nullable',
                'current_password',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'email' => 'adresse email',
            'current_password' => 'mot de passe actuel',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre compte.',
        ];
    }

    /**
     * L'adresse email ne change-t-elle pas par rapport à celle enregistrée ?
     */
    private function emailChanged(): bool
    {
        return $this->input('email') !== $this->user()->email;
    }
}

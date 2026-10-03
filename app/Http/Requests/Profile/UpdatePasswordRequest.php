<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'different:current_password',
                Password::min(8)->letters()->numbers(),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'mot de passe actuel',
            'password' => 'nouveau mot de passe',
        ];
    }

    public function messages(): array
    {
        return [
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
            'password.letters' => 'Le nouveau mot de passe doit contenir au moins une lettre.',
            'password.numbers' => 'Le nouveau mot de passe doit contenir au moins un chiffre.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
        ];
    }
}
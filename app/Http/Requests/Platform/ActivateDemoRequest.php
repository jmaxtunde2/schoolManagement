<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Provisionnement du compte administrateur lors de l'activation d'une école.
 *
 * Le mot de passe est facultatif : s'il est laissé vide, un mot de passe
 * temporaire est généré puis affiché une seule fois au Super Admin.
 */
class ActivateDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('activate', $this->route('demoRequest')) ?? false;
    }

    public function rules(): array
    {
        return [
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['nullable', 'string', 'min:10', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_name.required' => 'Le nom de l\'administrateur est obligatoire.',
            'admin_email.required' => 'L\'adresse e-mail de l\'administrateur est obligatoire.',
            'admin_email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'admin_email.unique' => 'Un compte existe déjà avec cette adresse e-mail.',
            'admin_password.min' => 'Le mot de passe doit contenir au moins 10 caractères.',
        ];
    }
}

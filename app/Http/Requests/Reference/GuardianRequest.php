<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $phone = ['nullable', 'string', 'regex:/^[0-9+\s().-]{6,30}$/'];

        return [
            'name' => ['required', 'string', 'max:255'],

            // Numéro principal : cible du module SMS existant.
            'phone' => ['required', 'string', 'regex:/^[0-9+\s().-]{6,30}$/'],

            /*
             * Second numéro du responsable. Aucun basculement automatique vers ce
             * numéro n'est appliqué : le module SMS continue de cibler `phone`,
             * `phone_secondary` est simplement conservé et affiché.
             */
            'phone_secondary' => $phone,

            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('guardian')?->user_id)],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:8'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'numéro principal',
            'phone_secondary' => 'numéro secondaire',
        ];
    }
}

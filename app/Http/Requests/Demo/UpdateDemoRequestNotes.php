<?php

namespace App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;

/** Notes internes réservées à l'équipe Coriyase. */
class UpdateDemoRequestNotes extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('demoRequest')) === true;
    }

    public function rules(): array
    {
        return [
            'internal_notes' => ['required', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'internal_notes' => 'notes internes',
        ];
    }
}

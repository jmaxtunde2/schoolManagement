<?php

namespace App\Http\Requests\Demo;

use App\Enums\DemoRequestStatus;
use App\Services\Demo\DemoRequestService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Changement de statut d'une demande de démonstration depuis l'espace
 * Super Admin. Les transitions sont validées contre le workflow métier :
 * on ne peut pas passer directement de `pending` à `approved`.
 */
class UpdateDemoRequestStatus extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateStatus', $this->route('demoRequest')) === true;
    }

    public function rules(): array
    {
        $current = $this->route('demoRequest')?->status;

        $allowed = $current
            ? array_map(
                fn (DemoRequestStatus $status) => $status->value,
                DemoRequestService::allowedTransitions($current)
            )
            : [];

        return [
            'status' => [
                'required',
                Rule::in($allowed !== [] ? $allowed : DemoRequestStatus::values()),
            ],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'preferred_demo_date' => ['required_unless:status,rejected,cancelled', 'date'],
            'preferred_demo_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'statut',
            'internal_notes' => 'notes internes',
            'preferred_demo_date' => 'date de démonstration',
            'preferred_demo_time' => 'heure de démonstration',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Ce changement de statut n’est pas autorisé à partir du statut actuel.',
            'preferred_demo_date.required_unless' => 'La date de démonstration est requise pour planifier.',
        ];
    }
}

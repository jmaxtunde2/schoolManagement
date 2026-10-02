<?php

namespace App\Http\Requests\Evaluations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $evaluation = $this->route('evaluation');

        $max = (float) $evaluation->max_score;

        return [
            'results' => [
                'required',
                'array',
            ],

            'results.*' => [
                'required',
                'array',
            ],

            'results.*.student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'results.*.score' => [
                'nullable',
                'numeric',
                'min:0',
                "max:{$max}",
            ],

            'results.*.is_absent' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'results.required' =>
                'Les résultats sont obligatoires.',

            'results.array' =>
                'Les résultats doivent être transmis sous forme de liste.',

            'results.*.student_id.required' =>
                'L’élève est obligatoire.',

            'results.*.student_id.exists' =>
                'L’élève sélectionné est invalide.',

            'results.*.score.numeric' =>
                'La note doit être numérique.',

            'results.*.score.min' =>
                'La note ne peut pas être négative.',

            'results.*.score.max' =>
                'La note ne peut pas dépasser le barème de l’évaluation.',

            'results.*.is_absent.required' =>
                'Le statut de présence est obligatoire.',

            'results.*.is_absent.boolean' =>
                'Le statut de présence est invalide.',
        ];
    }
}
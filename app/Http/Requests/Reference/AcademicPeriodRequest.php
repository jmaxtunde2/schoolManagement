<?php

namespace App\Http\Requests\Reference;

use App\Models\AcademicPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $period = $this->route('academic_period');

        return [
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where('school_id', $this->user()->school_id),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('academic_periods', 'name')
                    ->where('academic_year_id', $this->input('academic_year_id'))
                    ->ignore($period),
            ],

            'position' => ['required', 'integer', 'min:1', 'max:60'],

            'starts_at' => ['nullable', 'date'],

            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'is_closed' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => "L'année scolaire est obligatoire.",
            'academic_year_id.exists' => "L'année scolaire sélectionnée est invalide.",
            'name.required' => 'Le nom de la période est obligatoire.',
            'name.unique' => 'Une période porte déjà ce nom pour cette année scolaire.',
            'position.required' => "L'ordre de la période est obligatoire.",
            'position.unique' => 'Une autre période occupe déjà cet ordre pour cette année scolaire.',
            'starts_at.date' => 'La date de début est invalide.',
            'ends_at.date' => 'La date de fin est invalide.',
            'ends_at.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }

    /**
     * L'ordre des périodes est unique au sein d'une année scolaire.
     *
     * La base de données impose déjà cette règle via un index unique
     * `(academic_year_id, position)` ; on la reflète ici pour renvoyer une
     * erreur de validation lisible plutôt qu'une violation SQL.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $period = $this->route('academic_period');

            $taken = AcademicPeriod::query()
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->where('position', $this->input('position'))
                ->when($period, fn ($query) => $query->whereKeyNot($period->getKey()))
                ->exists();

            if ($taken) {
                $validator->errors()->add(
                    'position',
                    $this->messages()['position.unique']
                );
            }
        });
    }
}

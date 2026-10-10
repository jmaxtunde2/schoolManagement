<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicYear extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() ?? false;
    }

    public function rules(): array
    {
        $school = $this->route('school');
        $id = $this->route('academicYear')?->id;

        return [
            'name' => ['required', 'string', 'max:20', Rule::unique('academic_years')->where('school_id', $school?->id)->ignore($id)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_current' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Le nom de l'année scolaire est obligatoire.",
            'name.unique' => 'Cette année scolaire existe déjà pour cette école.',
            'ends_on.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}

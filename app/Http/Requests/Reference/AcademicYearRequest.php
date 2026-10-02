<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $id = $this->route('academic_year')?->id;

        return [
            'name' => ['required', 'string', 'max:20', Rule::unique('academic_years')->where('school_id', $this->user()->school_id)->ignore($id)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_current' => ['boolean'],
        ];
    }
}

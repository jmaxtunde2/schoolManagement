<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $id = $this->route('student')?->id;

        return [
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],

            'last_name' => [
                'required',
                'string',
                'max:255',
            ],

            'matricule' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('students')
                    ->where('school_id', $this->user()->school_id)
                    ->ignore($id),
            ],

            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('classes', 'id')
                    ->where('school_id', $this->user()->school_id),
            ],

            'birth_date' => [
                'nullable',
                'date',
                'before_or_equal:' . now()->subYears(4)->toDateString(),
            ],

            'gender' => [
                'nullable',
                Rule::in(['M', 'F']),
            ],

            'is_active' => [
                'boolean',
            ],

            'guardian_ids' => [
                'nullable',
                'array',
                'max:1',
            ],

            'guardian_ids.*' => [
                'integer',
                Rule::exists('parents', 'id')
                    ->where('school_id', $this->user()->school_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.before_or_equal' =>
                'L’élève doit avoir au moins 4 ans.',
        ];
    }
}
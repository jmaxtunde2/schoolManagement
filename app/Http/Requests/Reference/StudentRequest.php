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
                'before_or_equal:'.now()->subYears(4)->toDateString(),
            ],

            'gender' => [
                'nullable',
                Rule::in(['M', 'F']),
            ],

            'is_active' => [
                'boolean',
            ],

            'photo' => [
                'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:min_width=64,min_height=64,max_width=2000,max_height=2000',
            ],

            'remove_photo' => ['nullable', 'boolean'],

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
            'birth_date.before_or_equal' => 'L’élève doit avoir au moins 4 ans.',
            'photo.mimes' => 'La photo doit être une image JPG, PNG ou WebP.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'photo.dimensions' => 'La photo doit mesurer entre 64×64 et 2000×2000 pixels.',
        ];
    }
}

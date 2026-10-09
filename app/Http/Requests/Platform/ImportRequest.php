<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('school')) === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys([
                'classes' => true,
                'subjects' => true,
                'teachers' => true,
                'students' => true,
                'parents' => true,
                'academic_years' => true,
                'academic_periods' => true,
            ]))],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'type d\'import',
            'file' => 'fichier Excel',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type d\'import est obligatoire.',
            'type.in' => 'Type d\'import non reconnu.',
            'file.required' => 'Le fichier Excel est obligatoire.',
            'file.mimes' => 'Le fichier doit être au format .xlsx, .xls ou .csv.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}
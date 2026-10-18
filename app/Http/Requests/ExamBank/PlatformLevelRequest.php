<?php

namespace App\Http\Requests\ExamBank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlatformLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPlatformAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('platform_levels', 'name')->ignore($this->route('platformLevel')?->id),
            ],
            'code' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}

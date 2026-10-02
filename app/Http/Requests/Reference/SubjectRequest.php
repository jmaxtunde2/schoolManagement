<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin()
            || ($this->user()->isCenseur() && $this->isMethod('post'));
    }

    public function rules(): array
    {
        $id = $this->route('subject')?->id;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('subjects')->where('school_id', $this->user()->school_id)->ignore($id)],
            'code' => ['nullable', 'string', 'max:20'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', Rule::exists('classes', 'id')->where('school_id', $this->user()->school_id)],
        ];
    }
}

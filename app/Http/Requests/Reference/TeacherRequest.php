<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $userId = $this->route('teacher')?->user_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'assignments' => ['nullable', 'array'],
            'assignments.*.class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('school_id', $this->user()->school_id)],
            'assignments.*.subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')->where('school_id', $this->user()->school_id)],
        ];
    }
}

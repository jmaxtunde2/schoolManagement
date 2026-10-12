<?php

namespace App\Http\Requests\Finance;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalarySheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isSecretary();
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'month' => ['required', 'date_format:Y-m'],
            'lines' => ['required', 'array'],
            'lines.*.staff_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('school_id', $schoolId)
                    ->whereIn('role', array_map(fn (Role $role) => $role->value, Role::assignableStaff())),
            ],
            'lines.*.amount' => ['nullable', 'integer', 'min:1', 'max:1000000000'],
        ];
    }
}
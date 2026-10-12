<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isSecretary();
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where('school_id', $schoolId),
            ],
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where('school_id', $schoolId),
            ],
            'amount_due' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'amount_paid' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
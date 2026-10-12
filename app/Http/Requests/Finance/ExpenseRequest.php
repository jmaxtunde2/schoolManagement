<?php

namespace App\Http\Requests\Finance;

use App\Enums\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isSecretary();
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ExpenseCategory::class), Rule::notIn([ExpenseCategory::Salaire->value])],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'justification' => ['nullable', 'string', 'max:500'],
        ];
    }
}
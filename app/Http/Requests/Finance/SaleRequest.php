<?php

namespace App\Http\Requests\Finance;

use App\Enums\SaleCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isSecretary();
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(SaleCategory::class)],
            'item' => ['required', 'string', 'max:160'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit_price' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'sold_at' => ['required', 'date', 'before_or_equal:today'],
            'buyer' => ['nullable', 'string', 'max:160'],
        ];
    }
}
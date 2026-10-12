<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'category' => $this->faker->randomElement(ExpenseCategory::createable()),
            'label' => $this->faker->sentence(3),
            'amount' => $this->faker->numberBetween(1000, 200000),
            'expense_date' => $this->faker->dateTimeBetween('-3 months', 'today')->format('Y-m-d'),
            'status' => ExpenseStatus::Approved,
            'justification' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => ExpenseStatus::Pending]);
    }

    public function salary(): static
    {
        return $this->state(fn () => ['category' => ExpenseCategory::Salaire]);
    }
}
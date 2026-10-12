<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\SalaryRecord;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalaryRecordFactory extends Factory
{
    protected $model = SalaryRecord::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'expense_id' => Expense::factory(),
            'staff_id' => User::factory(),
            'salary_month' => now()->startOfMonth()->format('Y-m-d'),
            'amount' => $this->faker->numberBetween(25000, 150000),
        ];
    }
}
<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        $startYear = now()->year;

        return [
            'school_id' => School::factory(),
            'name' => "{$startYear}-".($startYear + 1),
            'starts_on' => now()->startOfYear(),
            'ends_on' => now()->endOfYear(),
            'is_current' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicPeriodFactory extends Factory
{
    public function definition(): array
    {
        $position = $this->faker->unique()->numberBetween(1, 60);

        return [
            'school_id' => School::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'Période '.$position,
            'position' => $position,
            'starts_at' => now()->startOfYear(),
            'ends_at' => now()->startOfYear()->addMonths(3)->endOfMonth(),
            'is_closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(['is_closed' => true]);
    }

    public function forYear(AcademicYear $year): static
    {
        return $this->state([
            'school_id' => $year->school_id,
            'academic_year_id' => $year->id,
        ]);
    }
}

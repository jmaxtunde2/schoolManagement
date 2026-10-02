<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<School> */
class SchoolFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->company().' School', 'is_active' => true];
    }
}

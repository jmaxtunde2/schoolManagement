<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassRoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->unique()->randomElement(['6ème A', '5ème A', '4ème A', '3ème A', '3ème B', '2nde A']),
            'level' => null,
            'capacity' => 40,
        ];
    }
}

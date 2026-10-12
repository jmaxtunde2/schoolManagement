<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->unique()->randomElement(['Salle 1', 'Salle 2', 'Laboratoire Physique', 'Laboratoire Chimie', 'Salle informatique']),
            'building' => null,
            'capacity' => null,
        ];
    }
}
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlatformSubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Mathématiques', 'Français', 'Anglais', 'SVT', 'Physique-Chimie',
                'Histoire-Géographie', 'Philosophie', 'Informatique',
            ]),
            'code' => null,
            'color' => null,
            'position' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}

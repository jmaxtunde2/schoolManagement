<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlatformLevelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                '6e', '5e', '4e', '3e', '2nde', '1ère', 'Terminale',
            ]),
            'code' => null,
            'position' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}

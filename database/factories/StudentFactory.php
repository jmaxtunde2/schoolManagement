<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'class_id' => null,
            'matricule' => strtoupper(fake()->unique()->bothify('ELV-####')),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-16 years', '-10 years'),
            'gender' => fake()->randomElement(['M', 'F']),
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParentGuardianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->name(),
            'phone' => '+229'.fake()->numerify('#########'),
            'email' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\PreinscriptionStatus;
use App\Models\Preinscription;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Preinscription>
 */
class PreinscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-12 years', '-5 years'),
            'gender' => fake()->randomElement(['M', 'F']),
            'requested_level' => fake()->randomElement(['CP', 'CM2']),
            'parent_name' => fake()->name(),
            'parent_phone' => fake()->numerify('+229 01 #######'),
            'parent_email' => fake()->safeEmail(),
            'parent_relationship' => 'Père',
            'notes' => null,
            'documents' => null,
            'status' => PreinscriptionStatus::Pending,
        ];
    }
}

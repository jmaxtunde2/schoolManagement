<?php

namespace Database\Factories;

use App\Enums\LicenseStatus;
use App\Enums\LicenseType;
use App\Models\License;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<License> */
class LicenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'academic_year_id' => null,
            'type' => LicenseType::Initial,
            'amount' => 150000,
            'currency' => 'XOF',
            'status' => LicenseStatus::Active,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'payment_reference' => 'CORI-'.now()->format('Ymd').'-'.strtoupper($this->faker->bothify('??????')),
        ];
    }
}
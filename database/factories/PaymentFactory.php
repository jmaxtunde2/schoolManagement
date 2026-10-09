<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\License;
use App\Models\Payment;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'license_id' => License::factory(),
            'amount' => 150000,
            'currency' => 'XOF',
            'status' => PaymentStatus::Confirmed,
            'method' => PaymentMethod::Manual,
            'reference' => 'CORI-'.now()->format('Ymd').'-'.strtoupper($this->faker->unique()->bothify('??????')),
            'paid_at' => now(),
            'confirmed_at' => now(),
        ];
    }
}
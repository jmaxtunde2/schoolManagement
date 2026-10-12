<?php

namespace Database\Factories;

use App\Enums\SaleCategory;
use App\Models\Sale;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 20);
        $unitPrice = $this->faker->numberBetween(1000, 25000);

        return [
            'school_id' => School::factory(),
            'category' => $this->faker->randomElement(SaleCategory::cases()),
            'item' => $this->faker->words(2, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
            'sold_at' => $this->faker->dateTimeBetween('-3 months', 'today')->format('Y-m-d'),
            'buyer' => $this->faker->optional()->name(),
        ];
    }
}
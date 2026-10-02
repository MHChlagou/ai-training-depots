<?php

namespace Database\Factories;

use App\Models\DiscountTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountTier>
 */
class DiscountTierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'Palier '.fake()->unique()->numberBetween(1, 1000),
            'min_subtotal_cents' => fake()->unique()->numberBetween(1, 1000) * 10000,
            'rate_bp' => 500,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}

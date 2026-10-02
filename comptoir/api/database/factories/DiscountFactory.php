<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('CODE-????##')),
            'type' => 'percent',
            'value' => 10,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    /**
     * Remise d'un montant fixe, en euros.
     */
    public function fixed(): static
    {
        return $this->state(fn () => ['type' => 'fixed']);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subMonths(3)->toDateString(),
            'ends_at' => now()->subMonth()->toDateString(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'reference' => 'CMD-'.fake()->unique()->numberBetween(10000000, 99999999),
            'status' => 'pending',
            'total_cents' => 0,
            'placed_at' => fake()->dateTimeBetween('-90 days', 'now'),
        ];
    }
}

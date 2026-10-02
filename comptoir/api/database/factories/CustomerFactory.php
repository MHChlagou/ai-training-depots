<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->userName().'@example.test',
            'password' => 'password',
            'company' => fake()->company(),
            'type' => 'pro',
            'country' => 'FR',
            'vat_number' => null,
            'is_admin' => false,
            'credit_limit' => 0,
            'discount_rate' => 0,
        ];
    }

    /**
     * Client invité : créé par une commande sans compte.
     */
    public function guest(): static
    {
        return $this->state(fn () => ['password' => null]);
    }

    public function particulier(): static
    {
        return $this->state(fn () => ['type' => 'particulier', 'company' => null]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['is_admin' => true]);
    }
}

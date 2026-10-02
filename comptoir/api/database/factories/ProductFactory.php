<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'sku' => 'SKU-'.fake()->unique()->numerify('######'),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 999999),
            'description' => fake()->paragraph()."\n\n".fake()->paragraph(),
            'price_cents' => fake()->numberBetween(100, 50000),
            'stock' => 1000,
            'image_url' => null,
        ];
    }
}

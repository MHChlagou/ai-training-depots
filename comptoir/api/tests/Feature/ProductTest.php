<?php

use App\Models\Category;
use App\Models\Product;

it('liste les produits avec leur catégorie', function () {
    $category = Category::factory()->create(['name' => 'Outillage', 'slug' => 'outillage']);
    Product::factory()->for($category)->create(['name' => 'Tournevis', 'price_cents' => 1999]);
    Product::factory()->for($category)->create(['name' => 'Perceuse']);

    $this->getJson('/api/products')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Perceuse')
        ->assertJsonPath('data.1.price_cents', 1999)
        ->assertJsonPath('data.1.category.slug', 'outillage')
        ->assertJsonStructure([
            'data' => [
                ['id', 'sku', 'slug', 'name', 'description', 'price_cents', 'stock', 'image_url', 'category' => ['id', 'name', 'slug']],
            ],
        ]);
});

it('affiche la fiche d\'un produit par son slug', function () {
    $product = Product::factory()->create(['slug' => 'niveau-a-bulle-60cm', 'price_cents' => 1250]);

    $this->getJson('/api/products/niveau-a-bulle-60cm')
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.price_cents', 1250)
        ->assertJsonPath('data.category.id', $product->category_id);
});

it('renvoie 404 pour un slug inconnu', function () {
    $this->getJson('/api/products/inconnu')
        ->assertNotFound();
});

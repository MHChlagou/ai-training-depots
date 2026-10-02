<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;

it('crée une commande et calcule le total en centimes', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price_cents' => 1250]);

    $this->postJson('/api/orders', [
        'customer_id' => $customer->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 2]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.total_cents', 2500)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.customer.id', $customer->id)
        ->assertJsonPath('data.lines.0.unit_price_cents', 1250);

    expect(Order::first()->reference)->toMatch('/^CMD-\d{8}$/');
});

it('refuse une commande sans ligne', function () {
    $customer = Customer::factory()->create();

    $this->postJson('/api/orders', [
        'customer_id' => $customer->id,
        'lines' => [],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lines');
});

it('refuse un produit inexistant', function () {
    $customer = Customer::factory()->create();

    $this->postJson('/api/orders', [
        'customer_id' => $customer->id,
        'lines' => [['product_id' => 999999, 'quantity' => 1]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lines.0.product_id');
});

it('crée un client invité pour une commande sans compte', function () {
    $product = Product::factory()->create(['price_cents' => 1250]);

    $this->postJson('/api/orders', [
        'guest' => [
            'email' => 'invite@example.test',
            'name' => 'Sami Invité',
            'company' => 'Atelier Nord',
            'address' => '12 rue du Port, 2000 Le Bardo',
        ],
        'lines' => [['product_id' => $product->id, 'quantity' => 2]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.total_cents', 2500)
        ->assertJsonPath('data.customer.name', 'Sami Invité');

    $guest = Customer::where('email', 'invite@example.test')->firstOrFail();

    expect($guest->password)->toBeNull()
        ->and(Order::first()->shipping_address)->toBe('12 rue du Port, 2000 Le Bardo');
});

it('refuse l\'e-mail d\'un compte existant pour une commande invité', function () {
    Customer::factory()->create(['email' => 'client@example.test']);
    $product = Product::factory()->create();

    $this->postJson('/api/orders', [
        'guest' => [
            'email' => 'client@example.test',
            'name' => 'Client',
            'company' => 'Société',
            'address' => '1 rue de la Paix',
        ],
        'lines' => [['product_id' => $product->id, 'quantity' => 1]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('guest.email');
});

it('décrémente le stock', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);

    $this->postJson('/api/orders', [
        'customer_id' => $customer->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 3]],
    ])->assertCreated();

    expect($product->fresh()->stock)->toBe(7);
});

it('refuse une quantité supérieure au stock', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['stock' => 2]);

    $this->postJson('/api/orders', [
        'customer_id' => $customer->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 3]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lines.0.quantity');

    expect($product->fresh()->stock)->toBe(2)
        ->and(Order::count())->toBe(0);
});

it('liste les commandes avec pagination', function () {
    Order::factory()->count(2)->create();

    $this->getJson('/api/orders')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonPath('meta.per_page', 50);
});

it('filtre les commandes par client', function () {
    $customer = Customer::factory()->create();
    Order::factory()->count(2)->for($customer)->create();
    Order::factory()->create();

    $this->getJson('/api/orders?customer_id='.$customer->id)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.customer.id', $customer->id);
});

it('filtre les commandes par statut', function () {
    Order::factory()->create(['status' => 'paid']);
    Order::factory()->count(2)->create(['status' => 'pending']);

    $this->getJson('/api/orders?status=paid')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'paid');
});

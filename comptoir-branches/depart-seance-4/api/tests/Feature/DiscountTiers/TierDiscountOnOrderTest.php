<?php

use App\Models\Customer;
use App\Models\DiscountTier;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->customer = Customer::factory()->create();
    // Produit à 100,00 € : le sous-total se pilote par la quantité.
    $this->product = Product::factory()->create(['price_cents' => 10000]);

    DiscountTier::factory()->create(['label' => 'Bronze', 'min_subtotal_cents' => 50000, 'rate_bp' => 300]);
    DiscountTier::factory()->create(['label' => 'Argent', 'min_subtotal_cents' => 100000, 'rate_bp' => 500]);
    DiscountTier::factory()->create(['label' => 'Or', 'min_subtotal_cents' => 500000, 'rate_bp' => 800]);
});

function passerCommande(object $test, int $quantity, ?int $productId = null): TestResponse
{
    return $test->postJson('/api/orders', [
        'customer_id' => $test->customer->id,
        'lines' => [['product_id' => $productId ?? $test->product->id, 'quantity' => $quantity]],
    ]);
}

it("n'applique aucune remise sous le premier palier", function () {
    passerCommande($this, 4) // 400,00 €
        ->assertCreated()
        ->assertJsonPath('data.subtotal_cents', 40000)
        ->assertJsonPath('data.discount_cents', 0)
        ->assertJsonPath('data.discount_tier_id', null)
        ->assertJsonPath('data.total_cents', 40000);
});

it('applique le palier quand le seuil est atteint exactement (seuil inclusif)', function () {
    passerCommande($this, 5) // 500,00 € = seuil Bronze
        ->assertCreated()
        ->assertJsonPath('data.discount_cents', 1500)
        ->assertJsonPath('data.total_cents', 48500);
});

it('retient le palier le plus élevé atteint', function () {
    $or = DiscountTier::where('label', 'Or')->first();

    passerCommande($this, 60) // 6 000,00 €
        ->assertCreated()
        ->assertJsonPath('data.discount_tier_id', $or->id)
        ->assertJsonPath('data.discount_cents', 48000)
        ->assertJsonPath('data.total_cents', 552000);
});

it('ignore un palier inactif', function () {
    DiscountTier::where('label', 'Or')->update(['active' => false]);

    passerCommande($this, 60)
        ->assertCreated()
        ->assertJsonPath('data.discount_cents', 30000); // Argent 5 %
});

it('arrondit la remise au centime le plus proche, demi vers le haut', function () {
    // 501,50 € x 3 % = 15,045 € -> 15,05 €
    $produit = Product::factory()->create(['price_cents' => 50150]);

    passerCommande($this, 1, $produit->id)
        ->assertCreated()
        ->assertJsonPath('data.discount_cents', 1505)
        ->assertJsonPath('data.total_cents', 48645);
});

it('enregistre sous-total, remise, palier et total en base', function () {
    passerCommande($this, 10)->assertCreated();

    $this->assertDatabaseHas('orders', [
        'customer_id' => $this->customer->id,
        'subtotal_cents' => 100000,
        'discount_cents' => 5000,
        'total_cents' => 95000,
    ]);
});

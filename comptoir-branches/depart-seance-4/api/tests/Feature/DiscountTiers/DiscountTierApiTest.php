<?php

use App\Models\DiscountTier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('crée un palier et convertit le taux en points de base', function () {
    $this->postJson('/api/discount-tiers', [
        'label' => 'Argent',
        'min_subtotal_cents' => 100000,
        'rate_percent' => 7.5,
    ])
        ->assertCreated()
        ->assertJsonPath('data.label', 'Argent')
        ->assertJsonPath('data.rate_percent', 7.5)
        ->assertJsonPath('data.active', true);

    $this->assertDatabaseHas('discount_tiers', ['min_subtotal_cents' => 100000, 'rate_bp' => 750]);
});

it('liste les paliers par seuil croissant', function () {
    DiscountTier::factory()->create(['min_subtotal_cents' => 500000]);
    DiscountTier::factory()->create(['min_subtotal_cents' => 50000]);

    $this->getJson('/api/discount-tiers')
        ->assertOk()
        ->assertJsonPath('data.0.min_subtotal_cents', 50000)
        ->assertJsonPath('data.1.min_subtotal_cents', 500000);
});

it('refuse un palier invalide', function (array $payload, string $champ) {
    DiscountTier::factory()->create(['min_subtotal_cents' => 100000]);

    $this->postJson('/api/discount-tiers', array_merge([
        'label' => 'Test',
        'min_subtotal_cents' => 200000,
        'rate_percent' => 5,
    ], $payload))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($champ);
})->with([
    'taux nul' => [['rate_percent' => 0], 'rate_percent'],
    'taux supérieur à 50 %' => [['rate_percent' => 60], 'rate_percent'],
    'taux à trois décimales' => [['rate_percent' => 5.125], 'rate_percent'],
    'seuil déjà utilisé' => [['min_subtotal_cents' => 100000], 'min_subtotal_cents'],
    'seuil nul' => [['min_subtotal_cents' => 0], 'min_subtotal_cents'],
    'libellé manquant' => [['label' => ''], 'label'],
]);

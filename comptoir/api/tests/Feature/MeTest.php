<?php

use App\Models\Customer;

it('refuse l\'accès sans authentification', function () {
    $this->getJson('/api/me')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
});

it('renvoie le profil du client connecté', function () {
    $customer = Customer::factory()->create(['email' => 'karim@example.test']);

    $this->actingAs($customer)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.id', $customer->id)
        ->assertJsonPath('data.email', 'karim@example.test');
});

it('n\'expose aucun champ sensible', function () {
    $customer = Customer::factory()->admin()->create();

    $data = $this->actingAs($customer)
        ->getJson('/api/me')
        ->assertOk()
        ->json('data');

    expect($data)->not->toHaveKeys([
        'password',
        'remember_token',
        'is_admin',
        'credit_limit',
        'discount_rate',
        'logo_path',
    ]);
});

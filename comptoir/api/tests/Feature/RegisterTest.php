<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->payload = [
        'name' => 'Lina Mansour',
        'company' => 'Atelier Sud',
        'email' => 'lina@example.test',
        'password' => 'MotDePasse-Test-42',
        'password_confirmation' => 'MotDePasse-Test-42',
    ];
});

it('inscrit un client', function () {
    $this->postJson('/api/register', $this->payload)
        ->assertCreated()
        ->assertJsonPath('data.email', 'lina@example.test')
        ->assertJsonPath('data.type', 'pro')
        ->assertJsonPath('data.country', 'FR')
        ->assertJsonMissingPath('data.password');

    $this->assertDatabaseHas('customers', ['email' => 'lina@example.test']);
});

it('refuse une adresse e-mail déjà utilisée', function () {
    Customer::factory()->create(['email' => 'lina@example.test']);

    $this->postJson('/api/register', $this->payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('refuse une confirmation de mot de passe différente', function () {
    $this->postJson('/api/register', [...$this->payload, 'password_confirmation' => 'autre-chose'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

it('enregistre le mot de passe haché', function () {
    $this->postJson('/api/register', $this->payload)->assertCreated();

    $customer = Customer::where('email', 'lina@example.test')->firstOrFail();

    expect($customer->password)->not->toBe('MotDePasse-Test-42')
        ->and(Hash::check('MotDePasse-Test-42', $customer->password))->toBeTrue();
});

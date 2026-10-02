<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('liste les commandes avec un nombre de requêtes constant', function (int $orders) {
    $customer = Customer::factory()->create();
    Order::factory()
        ->count($orders)
        ->for($customer)
        ->has(OrderLine::factory()->count(3), 'lines')
        ->create();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->getJson('/api/orders?customer_id='.$customer->id)
        ->assertOk()
        ->assertJsonCount($orders, 'data')
        ->assertJsonPath('data.0.customer.id', $customer->id);

    // 1 count (pagination) + 1 orders + 1 customers + 1 order_lines + 1 products = 5, quel que soit le volume.
    expect(DB::getQueryLog())->toHaveCount(5);
})->with([1, 10, 40]);

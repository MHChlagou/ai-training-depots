<?php

/*
 * Test ajouté APRÈS le refactoring : la date du jour vient désormais de now(),
 * on peut donc figer l'horloge (impossible avec date() dans le code d'origine).
 */

use App\Services\Invoicing\InvoiceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::table('discounts')->insert([
        'code' => 'OCTOBRE', 'type' => 'percent', 'value' => 10,
        'starts_at' => '2026-10-01', 'ends_at' => '2026-10-31',
    ]);
});

afterEach(fn () => Carbon::setTestNow());

it('applique un code promo uniquement pendant sa période, bornes incluses', function (string $today, float $expectedDiscount) {
    Carbon::setTestNow($today);

    $result = (new InvoiceCalculator)->calculate([
        'customer' => ['type' => 'particulier', 'country' => 'FR', 'vat_number' => null],
        'lines' => [['sku' => 'OUT-2', 'category' => 'outillage', 'unit_price' => 200, 'qty' => 1]],
        'discount_code' => 'OCTOBRE',
    ]);

    expect($result['discount'])->toEqualWithDelta($expectedDiscount, 0.0001);
})->with([
    'la veille' => ['2026-09-30 23:59:00', 0.0],
    'premier jour' => ['2026-10-01 00:00:00', 20.0],
    'dernier jour' => ['2026-10-31 23:59:00', 20.0],
    'le lendemain' => ['2026-11-01 00:00:00', 0.0],
]);

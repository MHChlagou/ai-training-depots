<?php

/*
 * Tests de caractérisation de LegacyInvoiceCalculator.
 *
 * Ils figent le comportement ACTUEL, y compris ses bizarreries (signalées par « CONSTAT »).
 * Ils ne disent pas ce qui est juste : ils disent ce qui est. Un refactoring ne doit en
 * faire échouer aucun. Toute correction de comportement fera l'objet d'un ticket séparé.
 */

use App\Services\LegacyInvoiceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Dates fixes loin dans le passé ou sans borne : résultats indépendants du jour d'exécution.
    DB::table('discounts')->insert([
        ['code' => 'DIX', 'type' => 'percent', 'value' => 10, 'starts_at' => null, 'ends_at' => null],
        ['code' => 'MOINS50', 'type' => 'fixed', 'value' => 50, 'starts_at' => null, 'ends_at' => null],
        ['code' => 'EXPIRE', 'type' => 'percent', 'value' => 20, 'starts_at' => '2020-01-01', 'ends_at' => '2020-12-31'],
    ]);
});

function client(string $type = 'particulier', string $country = 'FR', ?string $vatNumber = null): array
{
    return ['type' => $type, 'country' => $country, 'vat_number' => $vatNumber];
}

function ligne(string $sku, string $category, float $unitPrice, int $qty): array
{
    return ['sku' => $sku, 'category' => $category, 'unit_price' => $unitPrice, 'qty' => $qty];
}

// round() sur un flottant à la demi-unité : PHP 8.4 et plus arrondit la valeur binaire réelle
// (31.6349999… -> 31.63) ; PHP 8.3 pré-arrondit d'abord (-> 31.64). On fige ce que le code produit.
function selonPhp(float $php84, float $php83): float
{
    return PHP_VERSION_ID >= 80400 ? $php84 : $php83;
}

function attendu(array $lines, float $subtotal, float $discount, float $shipping, array $vat, float $vatTotal, float $total): array
{
    return [
        'lines' => $lines,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'vat' => $vat,
        'vat_total' => $vatTotal,
        'total' => $total,
    ];
}

it('reproduit le calcul actuel', function (array $order, array $expected) {
    $result = (new LegacyInvoiceCalculator)->calculate($order);

    // Égalité au centime près : les montants sont des flottants (0.1 + 0.2 != 0.3).
    expect($result)->toEqualWithDelta($expected, 0.0001);
})->with([
    'particulier FR, petit panier : port facturé et taxé' => [
        ['customer' => client(), 'lines' => [ligne('OUT-1', 'outillage', 19.99, 2)]],
        attendu([['sku' => 'OUT-1', 'qty' => 2, 'total' => 39.98, 'vat_rate' => 0.2]], 39.98, 0, 12.9, ['0.2' => 10.58], 10.58, 63.46),
    ],
    'deux taux de TVA (livre 5,5 %), port offert dès 150 €' => [
        ['customer' => client(), 'lines' => [ligne('LIV-1', 'livre', 24.9, 3), ligne('OUT-2', 'outillage', 89.5, 1)]],
        attendu([
            ['sku' => 'LIV-1', 'qty' => 3, 'total' => 74.7, 'vat_rate' => 0.055],
            ['sku' => 'OUT-2', 'qty' => 1, 'total' => 89.5, 'vat_rate' => 0.2],
        ], 164.2, 0, 0, ['0.055' => 4.11, '0.2' => 17.9], 22.01, 186.21),
    ],
    'pro au-dessus de 1 000 € : remise de 3 %' => [
        ['customer' => client('pro', 'FR', 'FR12345678901'), 'lines' => [ligne('OUT-3', 'outillage', 250, 5)]],
        attendu([['sku' => 'OUT-3', 'qty' => 5, 'total' => 1250.0, 'vat_rate' => 0.2]], 1250, 37.5, 0, ['0.2' => 242.5], 242.5, 1455),
    ],
    'CONSTAT : pro à exactement 1 000 € : pas de remise (seuil strict)' => [
        ['customer' => client('pro', 'FR', 'FR12345678901'), 'lines' => [ligne('OUT-3', 'outillage', 250, 4)]],
        attendu([['sku' => 'OUT-3', 'qty' => 4, 'total' => 1000.0, 'vat_rate' => 0.2]], 1000, 0, 0, ['0.2' => 200.0], 200, 1200),
    ],
    'remise quantité 5 % dès 10 unités, arrondi flottant (31,635 -> 31,63 ou 31,64 selon PHP)' => [
        ['customer' => client(), 'lines' => [ligne('VIS-1', 'quincaillerie', 3.33, 10)]],
        attendu([['sku' => 'VIS-1', 'qty' => 10, 'total' => selonPhp(31.63, 31.64), 'vat_rate' => 0.2]], selonPhp(31.63, 31.64), 0, 12.9, ['0.2' => 8.91], 8.91, selonPhp(53.44, 53.45)),
    ],
    'CONSTAT : 60 unités : toujours 5 %, la branche 10 % n\'est jamais atteinte' => [
        ['customer' => client(), 'lines' => [ligne('VIS-1', 'quincaillerie', 3.33, 60)]],
        attendu([['sku' => 'VIS-1', 'qty' => 60, 'total' => 189.81, 'vat_rate' => 0.2]], 189.81, 0, 0, ['0.2' => 37.96], 37.96, 227.77),
    ],
    'UE avec numéro de TVA : autoliquidation, port 25 € non taxé' => [
        ['customer' => client('pro', 'BE', 'BE0123456789'), 'lines' => [ligne('OUT-2', 'outillage', 89.5, 2)]],
        attendu([['sku' => 'OUT-2', 'qty' => 2, 'total' => 179.0, 'vat_rate' => 0]], 179, 0, 25, [0 => 0.0], 0, 204),
    ],
    'CONSTAT : UE sans numéro de TVA : TVA française, mais port non taxé' => [
        ['customer' => client('particulier', 'DE'), 'lines' => [ligne('LIV-1', 'livre', 24.9, 2)]],
        attendu([['sku' => 'LIV-1', 'qty' => 2, 'total' => 49.8, 'vat_rate' => 0.055]], 49.8, 0, 25, ['0.055' => 2.74], 2.74, 77.54),
    ],
    'hors UE : export sans TVA' => [
        ['customer' => client('pro', 'CH'), 'lines' => [ligne('OUT-2', 'outillage', 89.5, 2)]],
        attendu([['sku' => 'OUT-2', 'qty' => 2, 'total' => 179.0, 'vat_rate' => 0]], 179, 0, 25, [0 => 0.0], 0, 204),
    ],
    'pro + code pourcentage : cumul, le code s\'applique après la remise pro' => [
        ['customer' => client('pro', 'FR', 'FR12345678901'), 'lines' => [ligne('OUT-3', 'outillage', 250, 5)], 'discount_code' => 'DIX'],
        attendu([['sku' => 'OUT-3', 'qty' => 5, 'total' => 1250.0, 'vat_rate' => 0.2]], 1250, 158.75, 0, ['0.2' => 218.25], 218.25, 1309.5),
    ],
    'CONSTAT : code fixe : le port est recalculé sur le montant remisé' => [
        ['customer' => client(), 'lines' => [ligne('OUT-2', 'outillage', 89.5, 2)], 'discount_code' => 'MOINS50'],
        attendu([['sku' => 'OUT-2', 'qty' => 2, 'total' => 179.0, 'vat_rate' => 0.2]], 179, 50, 12.9, ['0.2' => 28.38], 28.38, 170.28),
    ],
    'code fixe supérieur au panier : remise plafonnée au sous-total' => [
        ['customer' => client(), 'lines' => [ligne('OUT-1', 'outillage', 19.99, 2)], 'discount_code' => 'MOINS50'],
        attendu([['sku' => 'OUT-1', 'qty' => 2, 'total' => 39.98, 'vat_rate' => 0.2]], 39.98, 39.98, 12.9, ['0.2' => 2.58], 2.58, 15.48),
    ],
    'code expiré : ignoré' => [
        ['customer' => client(), 'lines' => [ligne('OUT-2', 'outillage', 89.5, 2)], 'discount_code' => 'EXPIRE'],
        attendu([['sku' => 'OUT-2', 'qty' => 2, 'total' => 179.0, 'vat_rate' => 0.2]], 179, 0, 0, ['0.2' => 35.8], 35.8, 214.8),
    ],
    'code inconnu : ignoré sans erreur' => [
        ['customer' => client(), 'lines' => [ligne('OUT-2', 'outillage', 89.5, 2)], 'discount_code' => 'NEXISTEPAS'],
        attendu([['sku' => 'OUT-2', 'qty' => 2, 'total' => 179.0, 'vat_rate' => 0.2]], 179, 0, 0, ['0.2' => 35.8], 35.8, 214.8),
    ],
    'CONSTAT : panier vide : on facture le port' => [
        ['customer' => client(), 'lines' => []],
        attendu([], 0, 0, 12.9, ['0.2' => 2.58], 2.58, 15.48),
    ],
    'arrondis sur petits montants flottants' => [
        ['customer' => client(), 'lines' => [ligne('P-1', 'outillage', 0.1, 3), ligne('P-2', 'livre', 0.7, 3)]],
        attendu([
            ['sku' => 'P-1', 'qty' => 3, 'total' => 0.3, 'vat_rate' => 0.2],
            ['sku' => 'P-2', 'qty' => 3, 'total' => 2.1, 'vat_rate' => 0.055],
        ], 2.4, 0, 12.9, ['0.2' => 2.64, '0.055' => 0.12], 2.76, 18.06),
    ],
]);

it('conserve le dernier résultat dans lastResult (utilisé par l\'export PDF)', function () {
    $calculator = new LegacyInvoiceCalculator;
    $result = $calculator->calculate(['customer' => client(), 'lines' => [ligne('OUT-1', 'outillage', 19.99, 2)]]);

    expect($calculator->lastResult)->toBe($result);
});

it('ne modifie pas le tableau reçu', function () {
    $order = ['customer' => client(), 'lines' => [ligne('OUT-1', 'outillage', 19.99, 2)]];
    $copie = $order;

    (new LegacyInvoiceCalculator)->calculate($order);

    expect($order)->toBe($copie);
});

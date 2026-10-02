<?php

namespace App\Services\Invoicing;

use App\Models\Discount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Calcul de facture, issu du refactoring de LegacyInvoiceCalculator.
 *
 * Refactoring à comportement constant : mêmes entrées, mêmes sorties (voir les tests de
 * caractérisation). Les montants restent des flottants en euros ; le passage aux centimes
 * entiers change certains arrondis et fera l'objet d'une décision métier séparée.
 */
final class InvoiceCalculator
{
    private const EU_COUNTRIES = ['BE', 'DE', 'ES', 'IT', 'LU', 'NL', 'PT', 'AT', 'IE', 'FI', 'GR'];

    private const HOME_COUNTRY = 'FR';

    private const VAT_STANDARD = 0.2;

    private const VAT_BOOKS = 0.055;

    private const VAT_NONE = 0;

    private const BULK_MIN_QTY = 10;

    private const BULK_FACTOR = 0.95;

    private const PRO_DISCOUNT_THRESHOLD = 1000;

    private const PRO_DISCOUNT_RATE = 0.03;

    private const FREE_SHIPPING_THRESHOLD = 150;

    private const SHIPPING_HOME = 12.9;

    private const SHIPPING_ABROAD = 25;

    /**
     * @param  array{customer: array{type: string, country: string, vat_number: ?string}, lines: list<array{sku: string, category?: string, unit_price: float, qty: int}>, discount_code?: ?string}  $order
     */
    public function calculate(array $order): array
    {
        $customer = $order['customer'];
        $subtotal = 0;
        $lines = [];
        $vatBases = [];

        foreach ($order['lines'] as $line) {
            $lineTotal = $this->lineTotal($line['unit_price'], $line['qty']);
            $rate = $this->vatRate($customer, $line['category'] ?? null);

            // Clé en chaîne ('0.2', '0.055') : une clé flottante serait tronquée en entier par PHP.
            $key = (string) $rate;
            $vatBases[$key] = ($vatBases[$key] ?? 0) + $lineTotal;
            $subtotal += $lineTotal;
            $lines[] = ['sku' => $line['sku'], 'qty' => $line['qty'], 'total' => $lineTotal, 'vat_rate' => $rate];
        }

        $discount = $this->proDiscount($customer, $subtotal);
        $discount += $this->promoDiscount($order['discount_code'] ?? null, $subtotal - $discount);
        $discount = min($discount, $subtotal);

        $shipping = $this->shipping($customer, $subtotal - $discount);

        $vat = $this->vatBreakdown($vatBases, $subtotal, $discount);
        $vatTotal = array_sum($vat);
        if ($shipping > 0 && $customer['country'] === self::HOME_COUNTRY) {
            $shippingVat = round($shipping * self::VAT_STANDARD, 2);
            $vatTotal += $shippingVat;
            $vat[(string) self::VAT_STANDARD] = ($vat[(string) self::VAT_STANDARD] ?? 0) + $shippingVat;
        }

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'vat' => $vat,
            'vat_total' => $vatTotal,
            'total' => round($subtotal - $discount + $shipping + $vatTotal, 2),
        ];
    }

    private function lineTotal(float|int $unitPrice, int $qty): float
    {
        $total = $unitPrice * $qty;

        // Le code d'origine prévoyait 10 % dès 50 unités, mais cette branche n'était jamais
        // atteinte (elseif après >= 10). Comportement conservé ; correction : ticket à ouvrir.
        if ($qty >= self::BULK_MIN_QTY) {
            $total *= self::BULK_FACTOR;
        }

        return round($total, 2);
    }

    private function vatRate(array $customer, ?string $category): float|int
    {
        $country = $customer['country'];
        $isEu = in_array($country, self::EU_COUNTRIES, true);

        if ($country !== self::HOME_COUNTRY && ! $isEu) {
            return self::VAT_NONE; // export hors UE
        }
        if ($isEu && ($customer['vat_number'] ?? '') !== '') {
            return self::VAT_NONE; // autoliquidation intracommunautaire
        }

        return $category === 'livre' ? self::VAT_BOOKS : self::VAT_STANDARD;
    }

    private function proDiscount(array $customer, float|int $subtotal): float|int
    {
        if ($customer['type'] === 'pro' && $subtotal > self::PRO_DISCOUNT_THRESHOLD) {
            return round($subtotal * self::PRO_DISCOUNT_RATE, 2);
        }

        return 0;
    }

    private function promoDiscount(?string $code, float|int $base): float|int
    {
        if (! $code) { // même règle que l'original : null, '' et '0' sont ignorés
            return 0;
        }

        $promo = Discount::query()->where('code', $code)->first();
        if ($promo === null) {
            return 0;
        }

        $today = now()->toDateString();
        $startsAt = $this->dateOrNull($promo->starts_at);
        $endsAt = $this->dateOrNull($promo->ends_at);
        $started = $startsAt === null || $startsAt <= $today;
        $notEnded = $endsAt === null || $endsAt >= $today;
        if (! $started || ! $notEnded) {
            Log::debug('Code promo hors période : '.$code);

            return 0;
        }

        return $promo->type === 'percent'
            ? round($base * (float) $promo->value / 100, 2)
            : (float) $promo->value;
    }

    /** Normalise une date (chaîne ou Carbon selon les casts du modèle) au format Y-m-d. */
    private function dateOrNull(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toDateString();
    }

    private function shipping(array $customer, float|int $netAmount): float|int
    {
        if ($customer['country'] !== self::HOME_COUNTRY) {
            return self::SHIPPING_ABROAD;
        }

        return $netAmount < self::FREE_SHIPPING_THRESHOLD ? self::SHIPPING_HOME : 0;
    }

    /**
     * Répartit la remise globale au prorata de chaque base de TVA, puis arrondit chaque montant de TVA.
     *
     * @param  array<string|int, float>  $bases
     * @return array<string|int, float>
     */
    private function vatBreakdown(array $bases, float|int $subtotal, float|int $discount): array
    {
        $vat = [];
        foreach ($bases as $rate => $base) {
            if ($subtotal > 0) {
                $base -= $discount * $base / $subtotal;
            }
            $vat[$rate] = round($base * (float) $rate, 2);
        }

        return $vat;
    }
}

<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DiscountTier;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Enregistre une commande (client existant ou invité) et décrémente le stock.
     *
     * @param  array{customer_id?: int, guest?: array{email: string, name: string, company: string, address: string}, lines: array<int, array{product_id: int, quantity: int}>, shipping_address?: string|null, discount_code?: string|null}  $data
     */
    public function place(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $customer = $this->resolveCustomer($data);

            $products = Product::query()
                ->whereIn('id', collect($data['lines'])->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($data['lines'] as $index => $line) {
                if ($products[$line['product_id']]->stock < $line['quantity']) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => 'Stock insuffisant.',
                    ]);
                }
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'status' => 'pending',
                'placed_at' => now(),
                'shipping_address' => $data['guest']['address'] ?? $data['shipping_address'] ?? null,
                'discount_code' => $data['discount_code'] ?? null,
            ]);

            $subtotal = 0;

            foreach ($data['lines'] as $line) {
                $product = $products[$line['product_id']];

                $order->lines()->create([
                    'product_id' => $product->id,
                    'quantity' => $line['quantity'],
                    'unit_price_cents' => $product->price_cents,
                ]);

                $product->decrement('stock', $line['quantity']);

                $subtotal += $line['quantity'] * $product->price_cents;
            }

            // Remise par palier (docs/specs/remises-par-palier.md) : palier actif le plus élevé atteint.
            $tier = DiscountTier::applicableTo($subtotal);
            $discount = $tier?->discountFor($subtotal) ?? 0;

            $order->update([
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'discount_tier_id' => $tier?->id,
                'total_cents' => $subtotal - $discount,
            ]);

            return $order;
        });
    }

    private function resolveCustomer(array $data): Customer
    {
        if (isset($data['customer_id'])) {
            return Customer::findOrFail($data['customer_id']);
        }

        $guest = $data['guest'];
        $existing = Customer::query()->where('email', $guest['email'])->first();

        if ($existing !== null && $existing->password !== null) {
            throw ValidationException::withMessages([
                'guest.email' => 'Un compte client existe déjà pour cette adresse e-mail.',
            ]);
        }

        if ($existing !== null) {
            return $existing;
        }

        $customer = new Customer([
            'name' => $guest['name'],
            'company' => $guest['company'],
            'email' => $guest['email'],
            'password' => null,
        ]);
        $customer->type = 'pro';
        $customer->country = 'FR';
        $customer->save();

        return $customer;
    }
}

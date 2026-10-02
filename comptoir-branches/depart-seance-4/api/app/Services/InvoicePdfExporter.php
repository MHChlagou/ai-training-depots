<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\File;

class InvoicePdfExporter
{
    public function __construct(private LegacyInvoiceCalculator $calculator) {}

    /**
     * Écrit la facture de la commande dans storage/app/invoices et retourne le chemin du fichier.
     */
    public function export(Order $order): string
    {
        // Relations chargées explicitement : le chargement paresseux est interdit hors production.
        $order->loadMissing(['customer', 'lines.product.category']);

        $input = [
            'customer' => [
                'type' => $order->customer->type,
                'country' => $order->customer->country,
                'vat_number' => $order->customer->vat_number,
            ],
            'lines' => [],
            'discount_code' => $order->discount_code,
        ];

        foreach ($order->lines as $line) {
            $input['lines'][] = [
                'sku' => $line->product->sku,
                'category' => $line->product->category->slug,
                'unit_price' => $line->unit_price_cents / 100,
                'qty' => $line->quantity,
            ];
        }

        $this->calculator->calculate($input);
        $invoice = $this->calculator->lastResult;

        $content = [
            '%PDF-1.4',
            'FACTURE '.$order->reference,
            'Date : '.($order->placed_at?->format('d/m/Y') ?? now()->format('d/m/Y')),
            'Client : '.($order->customer->company ?? $order->customer->name).' <'.$order->customer->email.'>',
            '',
        ];

        foreach ($invoice['lines'] as $line) {
            $content[] = sprintf('%-12s x %4d  %s', $line['sku'], $line['qty'], $this->money($line['total']));
        }

        $content[] = '';
        $content[] = 'Sous-total HT : '.$this->money($invoice['subtotal']);
        $content[] = 'Remise : '.$this->money($invoice['discount']);
        $content[] = 'Port : '.$this->money($invoice['shipping']);

        foreach ($invoice['vat'] as $rate => $amount) {
            $content[] = 'TVA '.str_replace('.', ',', (string) ($rate * 100)).' % : '.$this->money($amount);
        }

        $content[] = 'Total TVA : '.$this->money($invoice['vat_total']);
        $content[] = 'Total TTC : '.$this->money($invoice['total']);
        $content[] = '%%EOF';

        $path = storage_path("app/invoices/facture-{$order->id}.pdf");

        File::ensureDirectoryExists(dirname($path));
        File::put($path, implode("\n", $content)."\n");

        return $path;
    }

    private function money(float|int $amount): string
    {
        return number_format($amount, 2, ',', ' ').' €';
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\InvoicePdfExporter;
use Illuminate\Console\Command;

class ExportInvoice extends Command
{
    protected $signature = 'invoices:export {order : Identifiant de la commande}';

    protected $description = 'Exporte la facture d\'une commande dans storage/app/invoices';

    public function handle(InvoicePdfExporter $exporter): int
    {
        $order = Order::find($this->argument('order'));

        if ($order === null) {
            $this->error('Commande introuvable.');

            return self::FAILURE;
        }

        $path = $exporter->export($order);

        $this->info($path);

        return self::SUCCESS;
    }
}

<?php

namespace App\Services;

use App\Services\Invoicing\InvoiceCalculator;

/**
 * Façade conservée pour les appelants existants (contrôleurs, export PDF).
 * Le calcul vit désormais dans App\Services\Invoicing\InvoiceCalculator.
 *
 * @deprecated Utiliser InvoiceCalculator directement dans le nouveau code.
 */
class LegacyInvoiceCalculator
{
    /** Dernier résultat calculé, lu par l'export PDF. */
    public ?array $lastResult = null;

    public function __construct(private readonly InvoiceCalculator $calculator = new InvoiceCalculator) {}

    public function calculate(array $order): array
    {
        return $this->lastResult = $this->calculator->calculate($order);
    }
}

const priceFormatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });

/**
 * Formate un montant en centimes pour l'affichage : 1250 -> « 12,50 € ».
 */
export function formatPrice(cents: number): string {
  return priceFormatter.format(cents / 100);
}

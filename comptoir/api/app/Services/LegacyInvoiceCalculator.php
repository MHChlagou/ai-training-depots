<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Calcul de facture.
 *
 * Attend un tableau de la forme :
 * [
 *   'customer' => ['type' => 'pro'|'particulier', 'country' => 'FR', 'vat_number' => null|string],
 *   'lines' => [['sku' => 'ABC', 'category' => 'livre', 'unit_price' => 12.5, 'qty' => 3], ...],
 *   'discount_code' => null|string,
 * ]
 * Tous les montants sont en euros (float).
 */
class LegacyInvoiceCalculator
{
    public $lastResult; // utilisé par l'export PDF (ne pas supprimer !!)

    /**
     * Calcule la facture complète d'une commande.
     *
     * Étapes, dans l'ordre :
     *  1. total de chaque ligne (prix x quantité), remise quantité, arrondi à la ligne ;
     *  2. taux de TVA de la ligne selon le pays du client, son numéro de TVA et la catégorie ;
     *  3. remise pro (3 % au-delà de 1000 €) puis code promo éventuel (table discounts) ;
     *  4. frais de port (France : offerts à partir de 150 €, sinon forfait) ;
     *  5. TVA par taux, la remise étant répartie au prorata des bases ;
     *  6. TVA sur le port pour la France ;
     *  7. total TTC.
     *
     * Le résultat est aussi conservé dans $this->lastResult (lu par l'export PDF).
     *
     * @param  array  $order  voir le docblock de la classe
     * @return array ['lines', 'subtotal', 'discount', 'shipping', 'vat', 'vat_total', 'total']
     */
    public function calculate($order)
    {
        $eu = ['BE', 'DE', 'ES', 'IT', 'LU', 'NL', 'PT', 'AT', 'IE', 'FI', 'GR'];
        $subtotal = 0;
        $lines = [];
        $bases = [];

        foreach ($order['lines'] as $k => $l) {
            $total = $l['unit_price'] * $l['qty'];
            // remise quantité
            if ($l['qty'] >= 10) {
                $total = $total * 0.95;
            } elseif ($l['qty'] >= 50) {
                $total = $total * 0.90;
            }
            $total = round($total, 2);
            $order['lines'][$k]['total'] = $total;

            // taux de TVA de la ligne
            $cat = @$l['category'];
            if ($order['customer']['country'] == 'FR') {
                if ($cat == 'livre') {
                    $rate = 0.055;
                } else {
                    $rate = 0.2;
                }
            } else {
                if (in_array($order['customer']['country'], $eu)) {
                    if ($order['customer']['vat_number'] != '') {
                        $rate = 0; // autoliquidation
                    } else {
                        if ($cat == 'livre') {
                            $rate = 0.055;
                        } else {
                            $rate = 0.2;
                        }
                    }
                } else {
                    $rate = 0; // export hors UE
                }
            }
            if (! isset($bases[(string) $rate])) {
                $bases[(string) $rate] = 0;
            }
            $bases[(string) $rate] += $total;
            $subtotal += $total;
            $lines[] = ['sku' => $l['sku'], 'qty' => $l['qty'], 'total' => $total, 'vat_rate' => $rate];
        }

        // remise pro
        $discount = 0;
        if ($order['customer']['type'] == 'pro' && $subtotal > 1000) {
            $discount = round($subtotal * 0.03, 2);
        }

        // code promo
        if (isset($order['discount_code']) && $order['discount_code']) {
            $d = DB::table('discounts')->where('code', $order['discount_code'])->first();
            if ($d) {
                $today = date('Y-m-d');
                if (($d->starts_at == null || $d->starts_at <= $today) && ($d->ends_at == null || $d->ends_at >= $today)) {
                    if ($d->type == 'percent') {
                        $discount = $discount + round(($subtotal - $discount) * $d->value / 100, 2);
                    } else {
                        $discount = $discount + $d->value;
                    }
                } else {
                    Log::debug('code promo expire '.$order['discount_code']);
                }
            }
        }
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        // frais de port
        $shipping = 0;
        if ($order['customer']['country'] == 'FR') {
            if ($subtotal - $discount < 150) {
                $shipping = 12.9;
            }
        } else {
            $shipping = 25;
        }

        // TVA : la remise globale est répartie au prorata des bases
        $vat = [];
        $vatTotal = 0;
        foreach ($bases as $r => $base) {
            if ($subtotal > 0) {
                $base = $base - ($discount * $base / $subtotal);
            }
            $v = round($base * $r, 2);
            $vat[$r] = $v;
            $vatTotal += $v;
        }
        // TVA sur le port (taux normal si FR)
        if ($shipping > 0 && $order['customer']['country'] == 'FR') {
            $vatTotal += round($shipping * 0.2, 2);
            $vat['0.2'] = (isset($vat['0.2']) ? $vat['0.2'] : 0) + round($shipping * 0.2, 2);
        }

        $total = $subtotal - $discount + $shipping + $vatTotal;

        // $total = ceil($total * 100) / 100;
        $result = [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'vat' => $vat,
            'vat_total' => $vatTotal,
            'total' => round($total, 2),
        ];
        $this->lastResult = $result;

        return $result;
    }

    /**
     * Arrondi « commercial » pour l'affichage (demi vers le haut).
     *
     * ATTENTION : ne pas utiliser dans calculate(). Les montants de la facture
     * sont arrondis avec round() ligne par ligne depuis la version 2 ; utiliser
     * cette méthode dans le calcul changerait les totaux déjà facturés.
     *
     * Historique : ajoutée pour l'impression des devis, où les montants
     * affichés devaient correspondre à ceux de l'ancien logiciel de caisse.
     *
     * Exemples :
     *   computeRounding(2.345)    => 2.35
     *   computeRounding(2.344)    => 2.34
     *   computeRounding(12.5, 0)  => 13
     *
     * @param  float  $amount  montant en euros
     * @param  int  $precision  nombre de décimales (2 par défaut)
     * @return float
     */
    public function computeRounding($amount, $precision = 2)
    {
        $factor = pow(10, $precision);

        return floor($amount * $factor + 0.5) / $factor;
    }

    /**
     * Formate un montant pour la facture : « 1 234,50 € ».
     *
     * Séparateur décimal : virgule. Séparateur de milliers : espace.
     * Le montant est d'abord arrondi avec computeRounding().
     *
     * @param  float  $amount  montant en euros
     * @return string
     */
    public function formatAmount($amount)
    {
        return number_format($this->computeRounding($amount), 2, ',', ' ').' €';
    }

    /**
     * Libellé de TVA imprimé sur la facture pour un taux donné.
     *
     * Les taux sont ceux produits par calculate() : 0.2, 0.055 ou 0.
     * Le taux 0 correspond à l'autoliquidation intracommunautaire ; les
     * exports hors Union européenne étaient codés à part dans l'ancien
     * module (taux « export »), d'où la dernière branche.
     *
     * @param  float|string  $rate  taux (0.2, 0.055, 0 ou 'export')
     * @return string
     */
    public function getVatLabel($rate)
    {
        if ($rate == 0.2) {
            return 'TVA 20 %';
        } else {
            if ($rate == 0.055) {
                return 'TVA 5,5 %';
            } else {
                if ($rate === 0 || $rate === '0' || $rate === 0.0) {
                    return 'Autoliquidation';
                } else {
                    return 'Exonération export';
                }
            }
        }
    }

    /**
     * Indique si un pays fait partie de l'Union européenne.
     *
     * Liste reprise de l'ancien module d'export comptable (2017).
     * Utilisée à l'époque pour choisir le barème de port et la mention
     * d'autoliquidation sur les factures papier.
     *
     * Exemples :
     *   isEuCountry('BE') => true
     *   isEuCountry('ch') => false
     *
     * @param  string  $country  code ISO à deux lettres
     * @return bool
     */
    public function isEuCountry($country)
    {
        $countries = ['BE', 'DE', 'ES', 'IT', 'LU', 'NL', 'PT', 'AT', 'IE', 'FI', 'GB', 'FR'];

        if (in_array(strtoupper($country), $countries)) {
            return true;
        }

        return false;
    }

    /**
     * Frais de port, barème 2019.
     *
     * ancien barème, garder pour l'historique
     *
     * Barème de l'époque :
     *  - France : 9,90 € jusqu'à 100 €, 5,90 € jusqu'à 200 €, offert au-delà ;
     *  - Union européenne : 19 € ;
     *  - reste du monde : 35 €.
     *
     * @param  string  $country  code ISO à deux lettres
     * @param  float  $amount  montant HT remisé, en euros
     * @return float
     */
    public function calculateShippingV1($country, $amount)
    {
        if ($country == 'FR') {
            if ($amount < 100) {
                return 9.9;
            } elseif ($amount < 200) {
                return 5.9;
            } else {
                return 0;
            }
        }

        if ($this->isEuCountry($country)) {
            return 19;
        }

        return 35;
    }

    /**
     * Vérifie la structure d'une commande avant calcul.
     *
     * Retourne la liste des erreurs trouvées (tableau vide si la commande est
     * exploitable). Les messages sont destinés au journal, pas au client.
     *
     * Appelée autrefois par l'import des commandes par fichier CSV.
     *
     * @param  array  $order  voir le docblock de la classe
     * @return array<int, string>
     */
    public function validateOrder($order)
    {
        $errors = [];

        if (! isset($order['customer'])) {
            $errors[] = 'client manquant';
        } else {
            if (! isset($order['customer']['country']) || strlen($order['customer']['country']) != 2) {
                $errors[] = 'pays du client invalide';
            }
            if (! isset($order['customer']['type'])) {
                $errors[] = 'type de client manquant';
            }
        }

        if (! isset($order['lines']) || ! is_array($order['lines'])) {
            $errors[] = 'lignes manquantes';

            return $errors;
        }

        foreach ($order['lines'] as $i => $line) {
            if (! isset($line['sku']) || $line['sku'] == '') {
                $errors[] = 'ligne '.$i.' : sku manquant';
            }
            if (! isset($line['qty']) || $line['qty'] <= 0) {
                $errors[] = 'ligne '.$i.' : quantité invalide';
            }
            if (! isset($line['unit_price']) || $line['unit_price'] < 0) {
                $errors[] = 'ligne '.$i.' : prix invalide';
            }
        }

        return $errors;
    }

    /**
     * Convertit un montant en euros en centimes.
     *
     * Prévu pour la migration vers les montants entiers (jamais terminée).
     *
     * Exemples :
     *   toCents(12.5)   => 1250
     *   toCents(0.1 + 0.2) => 30
     *
     * @param  float  $amount  montant en euros
     * @return int
     */
    public function toCents($amount)
    {
        return (int) round($amount * 100);
    }

    /**
     * Résumé texte du dernier calcul (une ligne par élément).
     *
     * Utilisait lastResult pour l'ancien envoi de facture par e-mail.
     *
     * Exemple de sortie :
     *   OUT-1 x 2 : 39,98 €
     *   Sous-total : 39,98 €
     *   Port : 12,90 €
     *   TVA 20 % : 10,58 €
     *   Total : 63,46 €
     *
     * @return array<int, string>
     */
    public function summarize()
    {
        $out = [];

        if ($this->lastResult == null) {
            return $out;
        }

        foreach ($this->lastResult['lines'] as $line) {
            $out[] = $line['sku'].' x '.$line['qty'].' : '.$this->formatAmount($line['total']);
        }

        $out[] = 'Sous-total : '.$this->formatAmount($this->lastResult['subtotal']);

        if ($this->lastResult['discount'] > 0) {
            $out[] = 'Remise : -'.$this->formatAmount($this->lastResult['discount']);
        }

        $out[] = 'Port : '.$this->formatAmount($this->lastResult['shipping']);

        foreach ($this->lastResult['vat'] as $rate => $amount) {
            $out[] = $this->getVatLabel($rate).' : '.$this->formatAmount($amount);
        }

        $out[] = 'Total : '.$this->formatAmount($this->lastResult['total']);

        return $out;
    }
}

# Spécification : remises par palier de montant

Statut : prête à planifier · Séance 3, exercice 1 · Dépôt `comptoir/api`

## Intention

Les clients B2B qui passent de grosses commandes doivent bénéficier automatiquement d'une remise
proportionnelle au montant de la commande. Les paliers (seuil et taux) sont configurables sans
déploiement, via l'API.

## Définitions

- **Sous-total** : somme, sur les lignes de la commande, de `quantity × unit_price_cents`.
- **Palier** : un seuil de sous-total (en centimes) et un taux de remise (en pourcentage, deux décimales au plus).
- **Palier applicable** : parmi les paliers actifs dont le seuil est inférieur ou égal au sous-total, celui qui a le seuil le plus élevé.

## Critères d'acceptation

### Gestion des paliers

1. `POST /api/discount-tiers` avec `label`, `min_subtotal_cents`, `rate_percent` et éventuellement `active` crée un palier et répond **201** avec le palier créé.
2. `rate_percent` doit être strictement positif, inférieur ou égal à 50, avec **au plus deux décimales**. Sinon : **422** avec une erreur sur `rate_percent`.
3. `min_subtotal_cents` est un entier supérieur ou égal à 1 et **unique** parmi les paliers. Sinon : **422** avec une erreur sur `min_subtotal_cents`.
4. `label` est obligatoire (100 caractères au plus). Sinon : **422** avec une erreur sur `label`.
5. `GET /api/discount-tiers` renvoie tous les paliers **triés par seuil croissant**.
6. La validation passe par une **FormRequest** dédiée.

### Application aux commandes (`POST /api/orders`)

7. Étant donné les paliers Bronze (500,00 € → 3 %), Argent (1 000,00 € → 5 %), Or (5 000,00 € → 8 %) :
   - Quand le sous-total vaut 400,00 €, alors aucune remise : `discount_cents = 0`, `discount_tier_id = null`, `total_cents = 40000`.
   - Quand le sous-total vaut **exactement** 500,00 €, alors Bronze s'applique (seuil inclusif) : `discount_cents = 1500`, `total_cents = 48500`.
   - Quand le sous-total vaut 6 000,00 €, alors Or s'applique : `discount_cents = 48000`, `total_cents = 552000`.
8. Un palier **inactif** est ignoré : avec Or inactif, une commande de 6 000,00 € reçoit le palier Argent (`discount_cents = 30000`).
9. La remise est arrondie au **centime le plus proche, demi vers le haut** : 501,50 € à 3 % = 15,045 € donne `discount_cents = 1505`.
10. La commande enregistre en base `subtotal_cents`, `discount_cents`, `discount_tier_id` et `total_cents = subtotal_cents - discount_cents`. La réponse de `POST /api/orders` expose ces quatre champs.

## Contraintes techniques

- Montants en **centimes entiers** ; aucun calcul d'argent en flottant.
- Nouvelles migrations uniquement : ne pas modifier une migration déjà exécutée.
- Supprimer un palier ne doit pas supprimer les commandes qui y font référence.
- Suivre les conventions du dépôt (CLAUDE.md, rules, skill `conventions-laravel`).

## Hors périmètre

- La logique des codes promo existants (modèle `Discount`) : ne pas la modifier.
- La modification et la suppression de paliers via l'API.
- L'autorisation (réservé aux administrateurs) : laisser un `TODO` explicite dans la FormRequest, traité en séance 4.
- Le front Next.js.

## Vérification

- `php artisan test --filter=DiscountTier` : tous les tests de la fonctionnalité passent.
- `php artisan test` : la suite complète reste verte.
- Chaque critère d'acceptation (1 à 10) est couvert par au moins un test Pest nommé de façon lisible.

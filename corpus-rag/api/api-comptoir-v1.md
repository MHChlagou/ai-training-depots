# API comptoir v1

Documentation de référence de l'API REST de l'application comptoir. Base : `/api`.
Format : JSON, encodage UTF-8. Les montants sont exprimés **en centimes** (entiers).

## Authentification

Les routes protégées exigent un jeton Sanctum transmis dans l'en-tête
`Authorization: Bearer <jeton>`. Les jetons sont créés depuis le portail professionnel
(rubrique *Accès API*) et peuvent être révoqués à tout moment.

## Limite de débit

**60 requêtes par minute et par jeton.** Au-delà, l'API répond `429 Too Many Requests` avec
un en-tête `Retry-After` indiquant le nombre de secondes à attendre.

## Produits

### `GET /api/products`

Route publique. Liste paginée des produits du catalogue.

| Paramètre | Type | Description |
|---|---|---|
| `search` | chaîne | Filtre sur le nom ou la référence (`sku`). |
| `category` | entier | Identifiant de catégorie. |
| `sort` | chaîne | `name`, `price` ou `-price` (tri décroissant). |
| `page` | entier | Numéro de page, à partir de 1. |
| `per_page` | entier | Nombre d'éléments par page. **Par défaut 20, maximum 100.** |

La pagination renvoie **20 éléments par défaut**. Le paramètre **`per_page` est limité à
100** : une valeur supérieure est refusée avec une erreur `422`.

Exemple de réponse :

```json
{
  "data": [
    {"id": 12, "category_id": 3, "sku": "GOB-C20", "name": "Gobelet carton 20 cl", "price_cents": 4850}
  ],
  "meta": {"current_page": 1, "per_page": 20, "total": 46},
  "links": {"next": "/api/products?page=2"}
}
```

### `GET /api/products/{id}`

Route publique. Détail d'un produit ; `404` si l'identifiant n'existe pas.

## Commandes

### `GET /api/orders`

Route protégée. Liste paginée (20 par page) des commandes du client authentifié, de la plus
récente à la plus ancienne, avec leurs lignes.

### `POST /api/orders`

Route protégée. Crée une commande pour le client authentifié.

```json
{
  "lines": [
    {"product_id": 12, "quantity": 4},
    {"product_id": 31, "quantity": 1}
  ]
}
```

Réponse `201` avec la commande créée (`reference`, `status`, `total_cents`, `placed_at`).
Erreur `422` si la liste de lignes est vide ou si une quantité est inférieure à 1.

## Codes d'erreur

| Code | Signification |
|---|---|
| 401 | Jeton absent ou invalide. |
| 403 | Ressource appartenant à un autre client. |
| 404 | Ressource introuvable. |
| 422 | Données invalides (détail dans `errors`). |
| 429 | Limite de débit dépassée. |

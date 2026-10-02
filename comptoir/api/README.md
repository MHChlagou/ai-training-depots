# Comptoir · API

API REST JSON de Comptoir : Laravel 12, PHP 8.3, PostgreSQL 16, Laravel Sanctum 4, tests Pest 3.
Installation et commandes : voir le `README.md` à la racine du dépôt.

## Architecture

```text
routes/api.php                  déclaration des routes (préfixe /api)
app/Http/Controllers/Api/       contrôleurs fins : validation déléguée, réponse via une Resource
app/Http/Requests/              FormRequest : règles de validation des entrées
app/Http/Resources/             forme exacte des réponses JSON ({"data": ...})
app/Services/                   logique métier (création de commande, facturation)
app/Models/                     modèles Eloquent : Category, Product, Customer, Order, OrderLine, Discount
app/Console/Commands/           commandes Artisan (invoices:export)
database/migrations/            schéma PostgreSQL
database/factories/             factories des 6 modèles (tests et seeders)
database/seeders/               données de démonstration (DatabaseSeeder)
tests/Unit, tests/Feature       tests Pest (Feature : base comptoir_test, RefreshDatabase)
```

Principes :

- un contrôleur reçoit une `FormRequest` validée, appelle un modèle ou un service, renvoie une `JsonResource` ;
- la logique qui touche plusieurs tables vit dans un service (`OrderService::place()` : client invité, stock, lignes, total, dans une transaction) ;
- les erreurs suivent le format JSON standard de Laravel (`404`, `401` `{"message": "Unauthenticated."}`, `422` `{"message", "errors"}`).

### Routes

| Méthode | Chemin | Contrôleur | Accès |
|---|---|---|---|
| GET | `/api/products` | `ProductController@index` | public |
| GET | `/api/products/{slug}` | `ProductController@show` | public |
| GET | `/api/orders` (`?customer_id=`, `?status=`, paginé par 50) | `OrderController@index` | public |
| POST | `/api/orders` (client existant `customer_id` ou invité `guest`) | `OrderController@store` | public |
| POST | `/api/register` | `RegisterController@store` | public |
| GET | `/api/me` | `ProfileController@show` | `auth:sanctum` |

`GET /` (hors API) renvoie `{"name":"Comptoir API","status":"ok"}`.

### Authentification

Le modèle authentifié est `App\Models\Customer` (Sanctum, trait `HasApiTokens`) ; il n'y a pas de modèle `User`. Le garde `web` utilise le provider `customers`, et Sanctum s'appuie sur ce garde : dans un test, `$this->actingAs($customer)` authentifie aussi les routes `auth:sanctum`. Un client dont le `password` est `NULL` est un client invité, créé par une commande sans compte.

### Facturation

`php artisan invoices:export {order}` appelle `App\Services\InvoicePdfExporter`, qui s'appuie sur `App\Services\LegacyInvoiceCalculator` (TVA, remises, port) et écrit `storage/app/invoices/facture-{id}.pdf`. `POST /api/orders` n'utilise pas ce calculateur : `total_cents` y est la somme HT des lignes.

## Montants

Convention : **entiers en centimes**, colonnes suffixées `_cents` (`price_cents`, `unit_price_cents`, `total_cents`, `credit_limit`), jamais de `float` pour de l'argent, arrondi une seule fois et au dernier moment.

Trois exceptions assumées :

1. `LegacyInvoiceCalculator` : entrées et sorties en **euros flottants** (code historique) ; `InvoicePdfExporter` convertit `unit_price_cents / 100` avant l'appel.
2. `discounts.value` : pourcentage pour un code de type `percent`, **montant en euros** pour un code de type `fixed` (`REMISE20` = 20 € de remise).
3. `customers.discount_rate` : pourcentage (décimal à deux chiffres), pas un montant.

## Bases de données

| Base | Usage |
|---|---|
| `comptoir` | développement (`php artisan migrate --seed`) |
| `comptoir_test` | tests Pest (`phpunit.xml`, `RefreshDatabase`) ; ne jamais y pointer la base de développement |
| `comptoir_e2e` | tests de bout en bout en CI |

Toutes appartiennent au rôle `comptoir` (mot de passe `comptoir`), qui exécute les migrations. Les tests utilisent PostgreSQL (pas SQLite).

Tables métier : `categories`, `products`, `customers`, `orders`, `order_lines`, `discounts`. Relations : une commande appartient à un client et possède plusieurs lignes ; une ligne référence un produit ; un produit appartient à une catégorie ; une commande est liée à un code promo par `orders.discount_code` → `discounts.code`, sans contrainte de clé étrangère.

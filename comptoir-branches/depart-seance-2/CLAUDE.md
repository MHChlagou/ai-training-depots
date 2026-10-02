# comptoir

Application e-commerce B2B. Monodépôt en deux parties indépendantes :
- `api/` : API REST Laravel 12, PHP 8.3, PostgreSQL, tests Pest.
- `web/` : front Next.js (App Router), TypeScript, tests Vitest (unitaires) et Playwright (E2E).

Le front consomme l'API uniquement via HTTP (`/api/...`). Aucun code partagé entre les deux dossiers.

## Commandes

Toujours lancer les commandes depuis le bon sous-dossier.

### api/
- Installer : `composer install`
- Base de données (développement) : `php artisan migrate --seed`
- Serveur local : `php artisan serve`
- Tous les tests : `php artisan test`
- Un seul fichier de test : `php artisan test tests/Feature/OrderTest.php`
- Formater : `./vendor/bin/pint`
- Lister les routes : `php artisan route:list --path=api`

### web/
- Installer : `npm ci`
- Serveur local : `npm run dev`
- Tests unitaires : `npm run test`
- Tests E2E : `npx playwright test` (API et front démarrés)
- Lint : `npm run lint`
- Vérification des types : `npx tsc --noEmit`

## Architecture de l'API

- Modèles Eloquent dans `api/app/Models/` : Product, Category, Customer, Order, OrderLine, Discount.
  - Order appartient à Customer et possède plusieurs OrderLine ; OrderLine référence Product.
  - Product appartient à Category ; Discount (code promo) est lié à Order par `orders.discount_code` → `discounts.code` (sans clé étrangère) et lu par le calcul de facture.
- Routes dans `api/routes/api.php` ; contrôleurs dans `api/app/Http/Controllers/Api/`.
- Endpoints : `GET /api/products`, `GET /api/products/{slug}`, `GET /api/orders`, `POST /api/orders` (client existant ou invité), `POST /api/register` (publics) ; `GET /api/me` (seule route protégée par `auth:sanctum`).
- Calcul de facture : `api/app/Services/LegacyInvoiceCalculator.php` (remises, TVA, arrondis).
- Migrations : `api/database/migrations/` ; factories et seeders : `api/database/`.
- Tests : `api/tests/Feature/` (HTTP, base réelle) et `api/tests/Unit/`.

## Architecture du front

- Pages dans `web/app/` (App Router) : accueil, catalogue, fiche produit, panier, inscription, commande.
- Composants dans `web/components/` ; appels à l'API regroupés dans `web/lib/`.
- L'URL de l'API vient d'une variable d'environnement, jamais codée en dur.

## Conventions

### PHP / Laravel
- `declare(strict_types=1);` en tête de chaque nouveau fichier PHP.
- Types de paramètres et de retour obligatoires.
- Validation des entrées dans une FormRequest, jamais dans le contrôleur.
- Réponses JSON via des API Resources.
- Charger les relations explicitement (`with()`) dans les requêtes de liste pour éviter les N+1.
- Montants en centimes (entiers). Pas de `float` pour l'argent.
- Toute nouvelle migration est réversible (`down()` implémentée).

### TypeScript / Next.js
- TypeScript strict : pas de `any` ; typer les réponses de l'API.
- Composants serveur par défaut ; `"use client"` seulement si l'interactivité l'exige.
- Tests Vitest à côté du fichier testé (`*.test.ts` / `*.test.tsx`).

### Git
- Messages de commit en français, à l'impératif, préfixés : `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`.
- Une modification = une intention. Pas de reformatage de fichiers hors sujet.

## Points fragiles connus

- `GET /api/orders` est lent : requêtes N+1 sur `lines`, `lines.product` et `customer`, index manquant sur `orders.customer_id`. Mesurer avant et après toute optimisation.
- `LegacyInvoiceCalculator.php` n'a aucun test. Avant de le modifier : écrire des tests de caractérisation qui figent le comportement actuel, arrondis compris.
- `POST /api/orders` touche au stock et aux montants : couvrir chaque changement par un test Feature.

## Règles de travail

- Avant de modifier du code : lire les fichiers concernés, proposer un plan court, attendre validation si le changement touche plus de 3 fichiers.
- Après une modification PHP : lancer `php artisan test` ; après une modification front : `npm run test` et `npm run lint`.
- Ne jamais lire ni afficher `.env`, `.env.*` (sauf `.env.example`), ni aucun secret. Ne jamais écrire de secret dans le code ou les tests.
- Données de test fictives uniquement (factories). Aucune donnée client réelle.
- Ne pas ajouter de dépendance Composer ou npm sans le signaler explicitement.
- Si une information n'est pas vérifiable dans le code, le dire au lieu de la supposer.

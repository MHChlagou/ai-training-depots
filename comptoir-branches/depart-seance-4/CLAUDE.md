# Comptoir : instructions projet

<!-- Fichier versionné, partagé par toute l'équipe. Cible : moins de 200 lignes.
     Les commentaires HTML comme celui-ci sont retirés avant l'injection dans le contexte de Claude. -->

Comptoir est une application e-commerce B2B en deux parties :

- `api/` : Laravel 12, PHP 8.3, PostgreSQL, tests Pest
- `web/` : Next.js (App Router), TypeScript, tests Vitest (unitaires) et Playwright (bout en bout)

Modèles métier : Product, Category, Customer, Order, OrderLine, Discount.
Scripts front disponibles : voir @web/package.json

## Commandes

Toujours lancer les commandes depuis le bon sous-dossier.

| Action | Commande |
|---|---|
| Installer l'API | `cd api && composer install` |
| Migrer et remplir la base de dev | `cd api && php artisan migrate --seed` |
| Tests API (Pest) | `cd api && php artisan test` |
| Un seul fichier de test | `cd api && php artisan test tests/Feature/OrderTest.php` |
| Formater le PHP | `cd api && ./vendor/bin/pint` |
| Vérifier le formatage sans modifier | `cd api && ./vendor/bin/pint --test` |
| Installer le front | `cd web && npm ci` |
| Lint front | `cd web && npm run lint` |
| Tests unitaires front (Vitest) | `cd web && npm run test` |
| Tests bout en bout (Playwright) | `cd web && npx playwright test` |
| Build front | `cd web && npm run build` |

## Conventions API (Laravel)

- Contrôleurs minces : validation dans une FormRequest, logique métier dans `app/Services/`, sérialisation par une API Resource.
- Routes d'API dans `routes/api.php`, préfixe `/api`, noms au pluriel (`/api/orders`).
- Toute relation lue dans une boucle ou une collection est chargée avec `with()` (pas de N+1).
- Montants stockés en centimes (entiers). Jamais de `float` pour de l'argent.
- Types stricts : `declare(strict_types=1);` en tête de chaque nouveau fichier PHP, types de retour explicites.
- Pour les conventions détaillées, utiliser le skill `conventions-laravel`.

## Conventions front (Next.js)

- App Router : composants serveur par défaut, `"use client"` seulement si nécessaire (état, événements).
- TypeScript strict : pas de `any` ; les types partagés vont dans `web/types/`.
- Appels à l'API via le client centralisé `web/lib/api.ts`, jamais de `fetch` dispersé dans les composants.

## Façon de travailler

- Avant une modification de plus d'un fichier : proposer un plan et attendre la validation.
- Toute nouvelle fonctionnalité ou tout correctif s'accompagne d'un test (Pest côté API, Vitest ou Playwright côté front).
- Terminer chaque tâche en lançant les tests concernés et en donnant le résultat.
- Messages de commit au format Conventional Commits (`feat:`, `fix:`, `test:`, `refactor:`), en français.

## Interdits

- Ne jamais lire, afficher ni modifier les fichiers `.env` (secrets). Utiliser `.env.example` comme référence.
- Ne jamais faire `git push`, ni de commit sans demande explicite.
- Ne jamais modifier une migration déjà fusionnée : créer une nouvelle migration.
- Ne jamais lancer `php artisan migrate:fresh` ni `db:wipe` (base de développement utilisée par les exercices).
- Ne pas ajouter de dépendance (Composer ou npm) sans le justifier et demander.

## Pièges connus

- Calcul de facture : `app/Services/Invoicing/InvoiceCalculator.php` ; `app/Services/LegacyInvoiceCalculator.php` n'est plus qu'une façade (`calculate()` et `lastResult`, lu par l'export PDF). Les tests de caractérisation de `tests/Feature/Invoicing/` figent le comportement actuel, bizarreries « CONSTAT » comprises : ne jamais modifier une valeur attendue pour faire passer un test.
- PostgreSQL ne crée pas d'index automatiquement sur une clé étrangère : ajouter l'index explicitement dans la migration.
- `Model::preventLazyLoading()` est actif hors production : charger les relations avec `with()` ou `loadMissing()`, sinon `LazyLoadingViolationException`. `tests/Feature/OrdersIndexQueryCountTest.php` verrouille le nombre de requêtes de `GET /api/orders`.
- Remises par palier (`docs/specs/remises-par-palier.md`) : taux en points de base entiers, calcul dans `OrderService::place()`.
- Les tests API utilisent une base dédiée (`comptoir_test`) avec `RefreshDatabase` : ne jamais les pointer sur la base de dev.

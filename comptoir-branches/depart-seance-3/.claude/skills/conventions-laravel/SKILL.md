---
name: conventions-laravel
description: Conventions de code de l'API Laravel 12 / PHP 8.3 du dépôt comptoir (contrôleurs, FormRequest, API Resources, services, Eloquent, montants, tests Pest). À utiliser pour écrire, modifier ou relire du code PHP dans api/.
when_to_use: Création d'un endpoint, d'un modèle, d'un service, d'une migration ou d'un test Pest ; relecture de code Laravel.
---

# Conventions Laravel du projet comptoir

Appliquez ces conventions à tout code PHP écrit ou relu dans `api/`.
Pour des exemples commentés (bon / mauvais), lisez [exemples.md](exemples.md).
Pour démarrer un nouveau fichier, partez des gabarits du dossier `gabarits/` :

| Besoin | Gabarit |
|---|---|
| Contrôleur d'API | [gabarits/Controller.stub](gabarits/Controller.stub) |
| Validation d'entrée | [gabarits/StoreRequest.stub](gabarits/StoreRequest.stub) |
| Sérialisation JSON | [gabarits/Resource.stub](gabarits/Resource.stub) |
| Test de fonctionnalité Pest | [gabarits/FeatureTest.stub](gabarits/FeatureTest.stub) |
| Migration | [gabarits/migration.stub](gabarits/migration.stub) |

Remplacez les marqueurs par les vrais noms : `{{ Model }}` (classe, ex. `Order`), `{{ model }}` (variable et paramètre de route, ex. `order`), `{{ models }}` (URL, ex. `orders`), `{{ table }}` (table, ex. `orders`).

## Architecture d'une requête

`routes/api.php` → Contrôleur (mince) → FormRequest (validation + autorisation) → Service (`app/Services/`, logique métier) → Modèle Eloquent → API Resource (réponse JSON).

- Un contrôleur ne contient ni règle de validation, ni calcul métier, ni requête complexe.
- Un service reçoit des données validées (tableau ou DTO) et retourne un modèle ou une valeur ; il ne connaît pas `Request`.
- Les réponses JSON passent toujours par une API Resource, jamais par `->toArray()` ou un tableau construit à la main.

## Règles de code

- `declare(strict_types=1);` en tête de chaque nouveau fichier, types de paramètres et de retour partout.
- Classes `final` par défaut pour les contrôleurs, services et requêtes.
- Injection de dépendances par le constructeur (`private readonly`), pas de façade dans les services.
- Noms : contrôleur `OrderController`, requête `StoreOrderRequest`, ressource `OrderResource`, service `OrderService`.

## Eloquent et base de données

- Charger les relations utilisées avec `with()` (ou `load()`) : aucune relation lue dans une boucle sans chargement préalable.
- Paginer les listes (`paginate()`), jamais `all()` sur une table métier.
- Transactions (`DB::transaction()`) dès qu'une opération écrit dans plusieurs tables (ex. Order + OrderLine).
- Montants en centimes (entiers) : `total_cents`, `unit_price_cents`. Arrondi une seule fois, au dernier moment.
- Clé étrangère : `foreignId()->constrained()` plus un index explicite (PostgreSQL n'indexe pas les FK).

## Réponses HTTP

- Création : `201` avec la ressource créée. Validation : `422` (automatique via FormRequest). Introuvable : `404` (route model binding).
- Pas de message d'exception brut dans la réponse JSON.

## Tests

- Tout endpoint a un test Pest dans `tests/Feature/` : cas nominal, erreur de validation, accès refusé si la route est protégée.
- Factories pour les données, `RefreshDatabase` via `tests/Pest.php`.
- Vérifier la forme JSON avec `assertJsonStructure` ou `assertJsonPath`, pas seulement le code HTTP.

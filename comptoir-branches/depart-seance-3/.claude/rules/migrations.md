---
paths:
  - "api/database/migrations/**/*.php"
---

# Règles pour les migrations (PostgreSQL)

- Ne jamais modifier une migration déjà fusionnée sur `main` : créer une nouvelle migration.
- Générer le fichier avec `php artisan make:migration <nom_explicite>` pour obtenir un horodatage correct.
- Format Laravel 12 : classe anonyme (`return new class extends Migration`), méthodes `up(): void` et `down(): void`.
- `down()` doit annuler exactement `up()` : chaque migration est réversible.
- Clés étrangères : `foreignId('customer_id')->constrained()` puis un index explicite (`$table->index('customer_id')`),
  car PostgreSQL n'indexe pas automatiquement les clés étrangères.
- Montants : colonnes `unsignedBigInteger` en centimes (ex. `total_cents`), jamais `float` ni `double`.
- Pas de données métier dans une migration : utiliser un seeder.
- Une migration = un changement cohérent (une table ou un ensemble de colonnes liées).
- Après écriture : `cd api && php artisan migrate` puis `php artisan migrate:rollback --step=1` puis `php artisan migrate`
  pour vérifier la réversibilité. Ne jamais lancer `migrate:fresh`.

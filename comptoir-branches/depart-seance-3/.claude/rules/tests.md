---
paths:
  - "api/tests/**/*.php"
  - "web/**/*.test.{ts,tsx}"
  - "web/e2e/**/*.ts"
---

# Règles pour les tests

## API (Pest)

- Syntaxe Pest uniquement (`it()` / `test()` + `expect()`), pas de classes PHPUnit.
- Tests de fonctionnalité dans `api/tests/Feature/`, tests unitaires purs dans `api/tests/Unit/`.
- Un fichier par ressource ou par service : `OrderTest.php`, `LegacyInvoiceCalculatorTest.php`.
- Données créées avec les factories (`Order::factory()->for($customer)->create()`), jamais d'insertion SQL brute.
- Base réinitialisée par `RefreshDatabase` (déclaré dans `tests/Pest.php`), jamais sur la base de dev.
- Nommer le test par le comportement attendu : `it('refuse une commande sans ligne')`.
- Couvrir au moins : cas nominal, cas d'erreur de validation (422), cas non autorisé (401/403) quand la route est protégée.
- Montants : comparer des entiers en centimes, jamais des flottants.

## Front (Vitest et Playwright)

- Tests unitaires Vitest à côté du fichier testé : `panier.ts` → `panier.test.ts`.
- Tests Playwright dans `web/e2e/`, sélecteurs par rôle ou libellé (`getByRole`, `getByLabel`), pas de sélecteurs CSS fragiles.
- Pas de `waitForTimeout` : attendre un état visible (`expect(locator).toBeVisible()`).

## Toujours

- Lancer le test écrit et montrer qu'il échoue avant le correctif, puis qu'il passe après.
- Ne jamais supprimer ni désactiver (`skip`, `todo`) un test existant pour faire passer la suite.

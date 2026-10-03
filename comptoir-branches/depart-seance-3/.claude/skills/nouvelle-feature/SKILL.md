---
name: nouvelle-feature
description: Développe une nouvelle fonctionnalité dans comptoir en imposant le cycle plan, validation, tests, code, vérification, revue. Se lance à la main avec /nouvelle-feature suivi de la description.
argument-hint: "[description de la fonctionnalité]"
disable-model-invocation: true
allowed-tools: Bash(git status *) Bash(git branch *) Bash(git diff *)
---

# Nouvelle fonctionnalité : $ARGUMENTS

## État du dépôt au lancement

- Branche courante : !`git branch --show-current`
- Fichiers modifiés non commités :

```!
git status --short
```

Si des fichiers sont déjà modifiés, signalez-le avant de commencer et demandez s'il faut continuer.

## Cycle obligatoire

Suivez ces étapes dans l'ordre. Ne passez jamais à l'étape suivante sans avoir terminé la précédente.

### Étape 1 : explorer et planifier (aucune modification de fichier)

- Lisez le code concerné (modèles, contrôleurs, routes, composants, tests existants).
- Rédigez un plan court :
  - objectif en une phrase et critères d'acceptation vérifiables ;
  - fichiers à créer ou modifier, avec une ligne d'explication chacun ;
  - migration éventuelle (nouvelle migration uniquement) ;
  - tests à écrire : Pest pour l'API, Vitest ou Playwright pour le front ;
  - risques et questions ouvertes.
- **Arrêtez-vous et demandez la validation du plan.** N'écrivez aucun fichier tant que la réponse
  ne contient pas un accord explicite (par exemple « OK plan »).

### Étape 2 : écrire les tests d'abord

- Écrivez les tests décrits dans le plan validé.
- Lancez-les et montrez qu'ils échouent pour la bonne raison (fonctionnalité absente, pas erreur de syntaxe).

### Étape 3 : implémenter

- Écrivez le code minimal qui fait passer les tests, en respectant `CLAUDE.md`, les règles de `.claude/rules/`
  et le skill `conventions-laravel` pour l'API.
- Restez dans le périmètre du plan. Toute idée hors périmètre va dans le résumé final, pas dans le code.

### Étape 4 : vérifier

- API : `cd api && php artisan test` puis `cd api && ./vendor/bin/pint --test`.
- Front (si concerné) : `cd web && npm run lint` puis `cd web && npm run test`.
- Si une vérification échoue, corrigez puis relancez. Ne désactivez aucun test.

### Étape 5 : faire relire

- Déléguez la relecture du diff au subagent `reviewer`.
- Corrigez les points classés « Bloquant », puis relancez l'étape 4.

### Étape 6 : résumer

Terminez par :

- la liste des fichiers créés ou modifiés ;
- le résultat des tests (nombre de tests, succès) ;
- les points « À corriger » ou « Suggestions » du reviewer laissés de côté ;
- un message de commit proposé au format Conventional Commits, en français.

Ne faites ni `git commit` ni `git push` : l'utilisateur relit le diff et commite lui-même.

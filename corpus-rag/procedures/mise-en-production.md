# Procédure de mise en production

Équipe technique Comptoir. Dernière mise à jour : 09/02/2026.

## Jours autorisés

Les **mises en production ont lieu le mardi et le jeudi uniquement**, entre 10 h et 16 h.
**Jamais le vendredi**, ni la veille d'un jour férié : une anomalie découverte en fin de
semaine serait traitée par l'astreinte, avec moins de monde disponible.

## Prérequis

- **Revue de code obligatoire** : la demande de fusion est approuvée par au moins une autre
  personne de l'équipe ; l'auteur ne peut pas approuver sa propre demande.
- Intégration continue au vert (tests Pest, analyse statique, lint du front).
- Note de version rédigée dans la demande de fusion (fonctionnalités, migrations, risques).
- Plan de retour arrière identifié.

## Déroulé

1. Annoncer la mise en production dans le canal #deploiements (heure, contenu, responsable).
2. Fusionner sur la branche `main` ; le pipeline construit l'image et la publie.
3. **Faire une sauvegarde de la base de production avant toute migration** (sauvegarde à la
   demande depuis l'espace client de l'hébergeur, ou `pg_dump` vers le stockage de secours),
   puis vérifier qu'elle est bien présente.
4. Déployer l'image, puis exécuter `php artisan migrate --force` (jamais sans la sauvegarde de
   l'étape 3).
5. Vider et reconstruire les caches : `php artisan optimize`.
6. Vérifier : page de santé de l'API, `GET /api/products`, création d'une commande de test en
   préproduction, tableau de bord de supervision pendant 30 minutes.
7. Annoncer la fin de la mise en production dans #deploiements.

## Retour arrière

En cas d'anomalie bloquante, redéployer l'image précédente. Si une migration a modifié des
données, restaurer la sauvegarde de l'étape 3 après accord du responsable technique.

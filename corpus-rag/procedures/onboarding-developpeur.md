# Onboarding développeur

Parcours d'intégration d'une nouvelle développeuse ou d'un nouveau développeur dans l'équipe
technique Comptoir.

## Avant l'arrivée (manager)

- Demander l'**accès VPN par ticket IT** (catégorie « Accès réseau ») : **délai de traitement
  de 48 h**, la demande est donc à faire au moins deux jours ouvrés avant l'arrivée.
- Demander la création des comptes : messagerie, forge Git, outil de tickets, supervision
  (lecture seule).
- **Désigner un parrain** ou une marraine dans l'équipe, disponible pendant les deux
  premières semaines.

## Programme de la première semaine

| Jour | Programme |
|---|---|
| J1 | Accueil par le manager, remise du poste, présentation de l'équipe et du parrain, lecture de la procédure de gestion des incidents de sécurité. |
| J2 | **Clonage du dépôt `comptoir`**, installation de l'environnement local (Docker, PHP, Node), `php artisan migrate --seed`, lancement des tests. |
| J3 | Présentation de l'architecture (API Laravel, front Next.js, PostgreSQL) et de la documentation de l'API ; première tâche simple avec le parrain. |
| J4 | Lecture des procédures de mise en production et de retour de marchandise ; participation à une revue de code. |
| J5 | Première demande de fusion personnelle, point de fin de semaine avec le manager et le parrain. |

## Récupérer le code

```bash
git clone git@forge.comptoir.internal:tech/comptoir.git
cd comptoir
cp .env.example .env
docker compose up -d
```

## Après la première semaine

- Intégration à la rotation d'astreinte après la période d'essai, en binôme la première fois.
- Point d'étape avec le parrain à la fin du premier mois.

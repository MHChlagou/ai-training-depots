# comptoir-vitrine

Site vitrine de **Comptoir**, grossiste en ligne de fournitures et d'équipements pour les professionnels de la restauration (cafés, hôtels, restaurants). Il présente l'offre et renvoie vers l'application `comptoir` pour commander.

Dépôt de travail de la séance 7 de la formation « L'IA au service du développement avec Claude et Claude Code » (SEO et GEO).

## Prérequis

- Node.js 20.9 ou plus récent (Node 22 recommandé)
- npm
- Pour les mesures : Chrome ou Chromium (Lighthouse), `curl`

## Lancement

```bash
npm install
npm run build
npm run start      # http://localhost:3000
```

> Pendant l'atelier, le site tourne toujours en build de production (`npm run build && npm run start`), jamais en `npm run dev`.

## Scripts

| Commande | Rôle |
|---|---|
| `npm run dev` | Serveur de développement (rechargement à chaud) |
| `npm run build` | Build de production |
| `npm run start` | Sert le build de production sur le port 3000 |
| `npm run lint` | ESLint (configuration `eslint-config-next`) |
| `bash scripts/mesurer.sh <etiquette>` | Mesure Lighthouse, 3 passages par page, résultats dans `mesures/<etiquette>/` |
| `node scripts/comparer-lighthouse.mjs mesures/avant mesures/apres` | Tableau comparatif (médianes) |
| `node kit-seance-7/extraire-seo.mjs http://localhost:3000 / /produits` | Relevé SEO/GEO du HTML brut servi |

## Pages

| Chemin | Contenu |
|---|---|
| `/` | Accueil |
| `/en` | Accueil en anglais |
| `/produits` | Liste des produits |
| `/produits/<slug>` | Fiche produit (6 produits, données dans `lib/produits.ts`) |
| `/livraison` | Livraison et retours |
| `/a-propos` | Présentation de l'entreprise |
| `/contact` | Coordonnées du service client |

## Arborescence

```text
app/                  pages (App Router), layout racine, styles globaux
app/api/produits/     API JSON des produits
components/           Header, Footer, ChatWidget
lib/produits.ts       données des produits
public/               images, fichiers statiques
kit-seance-7/         kit de l'atelier : script de relevé, gabarit de rapport
scripts/              mesure Lighthouse et comparaison avant / après
.mcp.json             serveurs MCP Playwright et Chrome DevTools
```

Stack : Next.js 16 (App Router), React 19, TypeScript. Domaine de production (fictif) : `https://www.comptoir.example`.

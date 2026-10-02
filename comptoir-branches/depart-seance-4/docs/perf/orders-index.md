# Mesures de performance : GET /api/orders

Rapport de référence (état `depart-seance-4`). Valeurs relevées sur le banc d'essai de référence :
l'ordre de grandeur compte, pas la valeur exacte.

## Protocole (identique avant et après)

| Élément | Valeur |
|---|---|
| Jeu de données | `PERF_ORDERS=200000 php artisan db:seed --class=PerfSeeder` (200 000 commandes, environ 700 000 lignes, 5 000 clients) |
| Commande de mesure | `php artisan perf:measure "/api/orders?customer_id=42" --runs=10 --label=<avant/apres> --explain` |
| Appels | 1 appel d'échauffement ignoré + 10 appels mesurés, médiane retenue |
| Machine / environnement | Portable de développement, PostgreSQL 16 local, aucune autre charge |
| `APP_DEBUG` / Telescope | `APP_DEBUG=true`, sans Telescope (même réglage avant et après) |

## Résultats

| Mesure | Avant | Étape 1 (`with`) | Après (`with` + index) |
|---|---|---|---|
| Temps médian (ms) | ~126 | ~34 | ~11 |
| Requêtes SQL par appel | 216 | 5 | 5 |
| Temps SQL cumulé (ms) | ~75 | ~22 | ~3 |
| Requête principale : type de parcours (EXPLAIN) | `Parallel Seq Scan on orders` | `Parallel Seq Scan on orders` | `Bitmap Index Scan on orders_customer_id_placed_at_index` |
| Requête principale : Execution Time (ms) | ~11 | ~11 | ~0,15 |

Les deux corrections ont des effets distincts : l'eager loading supprime les allers-retours,
l'index supprime le parcours complet de la table.

## Diagnostic

- Requêtes répétées observées avant :

```text
   138 x  select * from "products" where "products"."id" = ? limit 1
    38 x  select * from "customers" where "customers"."id" = ? limit 1
    38 x  select * from "order_lines" where "order_lines"."order_id" = ? and "order_lines"."order_id" is not null
```

- Plan d'exécution avant (lignes significatives) :

```text
->  Parallel Seq Scan on orders  (actual time=0.603..5.369 rows=13 loops=3)
      Filter: (customer_id = '42'::bigint)
      Rows Removed by Filter: 66654
```

## Corrections appliquées (une ligne par correction, dans l'ordre)

1. `OrderController@index` : `with(['customer:id,name,company', 'lines:id,order_id,product_id,quantity,unit_price_cents', 'lines.product:id,sku,name'])`.
2. Migration `add_customer_placed_at_index_to_orders_table` : index composite `orders_customer_id_placed_at_index` sur (`customer_id`, `placed_at`).

## Verrou de non-régression

- Test ajouté : `tests/Feature/OrdersIndexQueryCountTest.php` (5 requêtes pour 1, 10 et 40 commandes).
- `Model::preventLazyLoading()` activé hors production : oui (`AppServiceProvider::boot()`).

## Rapports bruts

`php artisan perf:measure` écrit `storage/perf/<label>.json` (non versionné).

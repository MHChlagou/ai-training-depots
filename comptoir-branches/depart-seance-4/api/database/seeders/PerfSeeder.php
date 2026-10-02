<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Jeu de données volumineux pour mesurer GET /api/orders (séance 3, exercice 2).
 *
 * PostgreSQL uniquement (generate_series). Données déterministes grâce à setseed().
 * Volume réglable : PERF_ORDERS=200000 php artisan db:seed --class=PerfSeeder
 * Durée indicative : quelques dizaines de secondes sur un portable récent.
 */
class PerfSeeder extends Seeder
{
    public function run(): void
    {
        $orders = (int) env('PERF_ORDERS', 200000);
        $customers = max(100, intdiv($orders, 40));
        $products = 2000;

        DB::statement('TRUNCATE order_lines, orders, customers, products, categories RESTART IDENTITY CASCADE');
        DB::statement('SELECT setseed(0.42)');

        DB::statement(<<<'SQL'
            INSERT INTO categories (name, slug, created_at, updated_at)
            SELECT 'Catégorie ' || g, 'categorie-' || g, now(), now()
            FROM generate_series(1, 40) AS g
        SQL);

        DB::statement(<<<SQL
            INSERT INTO products (category_id, sku, name, slug, price_cents, created_at, updated_at)
            SELECT 1 + (g % 40), 'SKU-' || lpad(g::text, 6, '0'), 'Produit ' || g, 'produit-' || g,
                   100 + floor(random() * 49900)::int, now(), now()
            FROM generate_series(1, {$products}) AS g
        SQL);

        DB::statement(<<<SQL
            INSERT INTO customers (name, email, company, type, country, created_at, updated_at)
            SELECT 'Client ' || g, 'client' || g || '@example.test', 'Société ' || g,
                   CASE WHEN g % 3 = 0 THEN 'particulier' ELSE 'pro' END, 'FR', now(), now()
            FROM generate_series(1, {$customers}) AS g
        SQL);

        DB::statement(<<<SQL
            INSERT INTO orders (customer_id, reference, status, total_cents, placed_at, created_at, updated_at)
            SELECT 1 + floor(random() * {$customers})::int,
                   'CMD-' || lpad(g::text, 8, '0'),
                   (ARRAY['pending','paid','shipped','cancelled'])[1 + floor(random() * 4)::int],
                   0,
                   now() - (random() * interval '730 days'),
                   now(), now()
            FROM generate_series(1, {$orders}) AS g
        SQL);

        // 1 à 6 lignes par commande.
        DB::statement(<<<SQL
            INSERT INTO order_lines (order_id, product_id, quantity, unit_price_cents, created_at, updated_at)
            SELECT o.id, 1 + floor(random() * {$products})::int, 1 + floor(random() * 20)::int,
                   100 + floor(random() * 49900)::int, now(), now()
            FROM orders o
            CROSS JOIN LATERAL generate_series(1, 1 + (o.id % 6)) AS l
        SQL);

        DB::statement(<<<'SQL'
            UPDATE orders o SET total_cents = s.total
            FROM (SELECT order_id, sum(quantity * unit_price_cents) AS total FROM order_lines GROUP BY order_id) s
            WHERE s.order_id = o.id
        SQL);

        DB::statement('ANALYZE');
    }
}

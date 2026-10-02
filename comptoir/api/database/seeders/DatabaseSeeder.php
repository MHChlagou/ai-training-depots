<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Données de démonstration fictives et déterministes.
     */
    public function run(): void
    {
        $products = $this->seedCatalogue();
        $customers = $this->seedCustomers();
        $this->seedDiscounts();
        $this->seedOrders($customers, $products);
    }

    /**
     * @return array<int, Product>
     */
    private function seedCatalogue(): array
    {
        $catalogue = [
            ['Outillage', 'outillage', [
                ['OUT-1', 'Tournevis cruciforme PH2', 'tournevis-cruciforme-ph2', 1999,
                    'Tournevis cruciforme à pointe PH2 aimantée, lame en acier chrome-vanadium.',
                    'Manche bi-matière antidérapant pour un vissage précis en atelier comme sur chantier.'],
                ['OUT-2', 'Perceuse sans fil 18 V', 'perceuse-sans-fil-18v', 8950,
                    'Perceuse-visseuse 18 V livrée avec deux batteries 2 Ah et un chargeur rapide.',
                    'Mandrin autoserrant de 13 mm, couple réglable sur 21 positions et éclairage LED.'],
                ['OUT-3', 'Scie circulaire 1 400 W', 'scie-circulaire-1400w', 25000,
                    'Scie circulaire de 1 400 W avec lame carbure de 190 mm pour les coupes droites et biaises.',
                    'Profondeur de coupe de 66 mm à 90°, raccord d\'aspiration et guide parallèle fournis.'],
                ['OUT-4', 'Niveau à bulle 60 cm', 'niveau-a-bulle-60cm', 1250,
                    'Niveau en aluminium de 60 cm avec trois fioles antichoc : horizontale, verticale et 45°.',
                    'Précision de 0,5 mm/m, semelle usinée et embouts de protection en caoutchouc.'],
                ['OUT-5', 'Mètre ruban 5 m', 'metre-ruban-5m', 890,
                    'Mètre ruban de 5 m, ruban de 19 mm de large avec double graduation.',
                    'Boîtier compact et antichoc, blocage du ruban et clip ceinture.'],
            ]],
            ['Quincaillerie', 'quincaillerie', [
                ['VIS-1', 'Vis bois 4 x 40 (boîte de 200)', 'vis-bois-4x40-boite-200', 333,
                    'Vis à bois tête fraisée 4 x 40 mm, empreinte Pozidriv, filetage partiel.',
                    'Acier zingué blanc pour les travaux d\'intérieur. Boîte refermable de 200 vis.'],
                ['CHE-1', 'Chevilles nylon 8 mm (lot de 100)', 'chevilles-nylon-8mm-lot-100', 650,
                    'Chevilles nylon universelles de 8 mm pour matériaux pleins et creux.',
                    'Ailettes anti-rotation. Lot de 100 chevilles, vis de 4,5 à 6 mm.'],
                ['CHA-1', 'Charnière inox 80 mm', 'charniere-inox-80mm', 420,
                    'Charnière rectangulaire en acier inoxydable de 80 mm, axe fixe.',
                    'Adaptée aux portes de placard et aux coffres. Vis non fournies.'],
            ]],
            ['Livres techniques', 'livre', [
                ['LIV-1', 'Guide pratique de l\'électricité', 'guide-pratique-electricite', 2490,
                    'Guide illustré des installations électriques domestiques selon la norme en vigueur.',
                    'Schémas pas à pas, tableaux de sections de câbles et exemples de chantiers.'],
                ['LIV-2', 'Menuiserie : les bases', 'menuiserie-les-bases', 3200,
                    'Les techniques fondamentales de la menuiserie : traçage, sciage, assemblages.',
                    'Plus de 300 photos et dix projets complets pour s\'exercer en atelier.'],
            ]],
            ['Fournitures de bureau', 'fournitures-bureau', [
                ['BUR-1', 'Ramette papier A4 80 g', 'ramette-papier-a4-80g', 549,
                    'Ramette de 500 feuilles de papier blanc A4, grammage 80 g/m².',
                    'Compatible imprimantes laser et jet d\'encre, photocopieurs et télécopieurs.'],
                ['BUR-2', 'Stylos bille bleus (lot de 10)', 'stylos-bille-bleus-lot-10', 390,
                    'Stylos bille à encre bleue, pointe moyenne de 1 mm, capuchon ventilé.',
                    'Lot de 10 stylos pour le bureau, l\'atelier ou le comptoir de vente.'],
            ]],
        ];

        $products = [];

        foreach ($catalogue as [$categoryName, $categorySlug, $items]) {
            $category = Category::create(['name' => $categoryName, 'slug' => $categorySlug]);

            foreach ($items as [$sku, $name, $slug, $priceCents, $firstParagraph, $secondParagraph]) {
                $products[] = Product::create([
                    'category_id' => $category->id,
                    'sku' => $sku,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $firstParagraph."\n\n".$secondParagraph,
                    'price_cents' => $priceCents,
                    'stock' => 1000,
                    'image_url' => "/images/produits/{$slug}.svg",
                ]);
            }
        }

        return $products;
    }

    /**
     * @return array<int, Customer>
     */
    private function seedCustomers(): array
    {
        $customers = [
            ['Karim Haddad', 'karim@atelier-nord.example.test', 'Atelier Nord', 'pro', 'FR', 'FR12345678901', false],
            ['Lina Mansour', 'lina.mansour@example.test', null, 'particulier', 'FR', null, false],
            ['Jonas Weber', 'einkauf@bau-werkzeuge.example.test', 'Bau Werkzeuge GmbH', 'pro', 'DE', 'DE123456789', false],
            ['Admin Comptoir', 'admin@comptoir.example.test', null, 'pro', 'FR', null, true],
        ];

        $created = [];

        foreach ($customers as [$name, $email, $company, $type, $country, $vatNumber, $isAdmin]) {
            $customer = new Customer([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'company' => $company,
            ]);
            $customer->forceFill([
                'type' => $type,
                'country' => $country,
                'vat_number' => $vatNumber,
                'is_admin' => $isAdmin,
            ])->save();

            $created[] = $customer;
        }

        return $created;
    }

    private function seedDiscounts(): void
    {
        Discount::create(['code' => 'BIENVENUE10', 'type' => 'percent', 'value' => 10]);
        Discount::create(['code' => 'REMISE20', 'type' => 'fixed', 'value' => 20]);
        Discount::create([
            'code' => 'ETE2025',
            'type' => 'percent',
            'value' => 15,
            'starts_at' => '2025-06-01',
            'ends_at' => '2025-08-31',
        ]);
    }

    /**
     * 30 commandes réparties 12 / 8 / 10 sur les trois premiers clients.
     *
     * @param  array<int, Customer>  $customers
     * @param  array<int, Product>  $products
     */
    private function seedOrders(array $customers, array $products): void
    {
        $statuses = ['pending', 'paid', 'shipped', 'cancelled'];
        $distribution = [12, 8, 10];
        $today = Carbon::today();
        $number = 0;

        foreach ($distribution as $customerIndex => $count) {
            for ($i = 0; $i < $count; $i++) {
                $order = Order::create([
                    'customer_id' => $customers[$customerIndex]->id,
                    'reference' => 'CMD-'.(48213907 + $number * 104729),
                    'status' => $statuses[$number % count($statuses)],
                    'placed_at' => $today->copy()->subDays(($number * 3) % 90)->setTime(8 + $number % 10, ($number * 17) % 60),
                    'shipping_address' => null,
                ]);

                $total = 0;
                $lineCount = 1 + $number % 3;

                for ($l = 0; $l < $lineCount; $l++) {
                    $product = $products[($number * 5 + $l * 7) % count($products)];
                    $quantity = 1 + ($number + $l) % 4;

                    $order->lines()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price_cents' => $product->price_cents,
                    ]);

                    $total += $quantity * $product->price_cents;
                }

                $order->update(['total_cents' => $total]);
                $number++;
            }
        }
    }
}

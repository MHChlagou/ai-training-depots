import Link from 'next/link';
import { getProducts } from '@/lib/api';
import { formatPrice } from '@/lib/money';
import type { Product } from '@/types/api';

export const dynamic = 'force-dynamic';

export default async function HomePage() {
  let featured: Product[] = [];
  let unavailable = false;

  try {
    featured = (await getProducts()).slice(0, 4);
  } catch {
    unavailable = true;
  }

  return (
    <main>
      <h1>Comptoir, fournitures professionnelles</h1>
      <section aria-labelledby="a-la-une">
        <h2 id="a-la-une">Produits à la une</h2>
        {unavailable ? (
          <p>Les produits sont momentanément indisponibles.</p>
        ) : (
          <ul className="grid">
            {featured.map((product) => (
              <li key={product.id} className="card">
                <Link href={`/produits/${product.slug}`}>{product.name}</Link>
                <p>{formatPrice(product.price_cents)}</p>
              </li>
            ))}
          </ul>
        )}
      </section>
      <p>
        <Link href="/catalogue">Voir le catalogue</Link>
      </p>
    </main>
  );
}

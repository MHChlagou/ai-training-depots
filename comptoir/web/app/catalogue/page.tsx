'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import _ from 'lodash';
import { addToCart } from '@/lib/cart';

type Product = {
  id: number;
  sku: string;
  slug: string;
  name: string;
  price_cents: number;
  image_url: string | null;
  category: { name: string };
};

export default function CataloguePage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/products`)
      .then((response) => response.json())
      .then((payload: { data: Product[] }) => {
        setProducts(payload.data);
        setLoading(false);
      });
  }, []);

  const byCategory = _.groupBy(_.sortBy(products, 'name'), (product) => product.category.name);

  if (loading) {
    return <p>Chargement…</p>;
  }

  return (
    <main>
      <h1>Catalogue</h1>
      {Object.entries(byCategory).map(([category, items]) => (
        <section key={category}>
          <h2>{category}</h2>
          <ul className="grid">
            {items.map((product) => (
              <li key={product.id} data-testid="product-card">
                <img
                  src={product.image_url ?? '/images/produits/placeholder.svg'}
                  alt={product.name}
                />
                <h3>
                  <Link href={`/produits/${product.slug}`}>{product.name}</Link>
                </h3>
                <p>{(product.price_cents / 100).toFixed(2)} €</p>
                <button
                  onClick={() =>
                    addToCart(
                      {
                        productId: product.id,
                        slug: product.slug,
                        sku: product.sku,
                        name: product.name,
                        unitPriceCents: product.price_cents,
                      },
                      1,
                    )
                  }
                >
                  Ajouter au panier
                </button>
              </li>
            ))}
          </ul>
        </section>
      ))}
    </main>
  );
}

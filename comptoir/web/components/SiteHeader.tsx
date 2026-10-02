'use client';

import Link from 'next/link';
import { useSyncExternalStore } from 'react';
import { cartCount, subscribeToCart } from '@/lib/cart';

export function SiteHeader() {
  const count = useSyncExternalStore(subscribeToCart, cartCount, () => 0);

  return (
    <header className="site-header">
      <Link href="/" className="site-header__brand">
        Comptoir
      </Link>
      <nav aria-label="Navigation principale">
        <ul className="site-header__links">
          <li>
            <Link href="/">Accueil</Link>
          </li>
          <li>
            <Link href="/catalogue">Catalogue</Link>
          </li>
          <li>
            <Link href="/panier">
              Panier{' '}
              <span className="badge" data-testid="cart-count">
                {count}
              </span>
            </Link>
          </li>
          <li>
            <Link href="/inscription">Créer un compte</Link>
          </li>
        </ul>
      </nav>
    </header>
  );
}

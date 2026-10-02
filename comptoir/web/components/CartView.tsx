'use client';

import Link from 'next/link';
import { useMemo, useSyncExternalStore } from 'react';
import {
  computeCartTotal,
  getCartSnapshot,
  parseCart,
  removeFromCart,
  subscribeToCart,
  updateQuantity,
} from '@/lib/cart';
import { formatPrice } from '@/lib/money';

export function CartView() {
  const snapshot = useSyncExternalStore(subscribeToCart, getCartSnapshot, () => null);
  const lines = useMemo(() => parseCart(snapshot), [snapshot]);

  if (snapshot === null) {
    return <p>Chargement du panier…</p>;
  }

  if (lines.length === 0) {
    return (
      <div className="empty">
        <p>Votre panier est vide</p>
        <Link href="/catalogue">Voir le catalogue</Link>
      </div>
    );
  }

  return (
    <div className="cart">
      <ul className="cart__lines">
        {lines.map((line) => (
          <li key={line.productId} className="cart-line" data-testid="cart-line">
            <Link href={`/produits/${line.slug}`} className="cart-line__name">
              {line.name}
            </Link>
            <span className="cart-line__price">{formatPrice(line.unitPriceCents)}</span>
            <span className="field field--inline">
              <label htmlFor={`quantite-${line.productId}`}>Quantité pour {line.name}</label>
              <input
                id={`quantite-${line.productId}`}
                type="number"
                min="1"
                value={line.quantity}
                onChange={(event) => updateQuantity(line.productId, Number(event.target.value))}
              />
            </span>
            <span className="cart-line__total">
              {formatPrice(line.unitPriceCents * line.quantity)}
            </span>
            <button
              type="button"
              className="button button--secondary"
              onClick={() => removeFromCart(line.productId)}
            >
              Retirer
            </button>
          </li>
        ))}
      </ul>
      <p className="cart__total" data-testid="cart-total">
        Total HT : <strong>{formatPrice(computeCartTotal(lines))}</strong>
      </p>
      <button
        type="button"
        className="button"
        onClick={() => window.location.assign(new URL('/commande', window.location.origin))}
      >
        Commander sans compte
      </button>
    </div>
  );
}

'use client';

import { useState, type FormEvent } from 'react';
import { addToCart, type CartItem } from '@/lib/cart';

type AddToCartFormProps = {
  item: CartItem;
};

export function AddToCartForm({ item }: AddToCartFormProps) {
  const [message, setMessage] = useState<string | null>(null);

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const quantity = Number(data.get('quantite'));

    addToCart(item, quantity);
    setMessage(`Ajouté au panier : ${quantity} × ${item.name}`);
  }

  return (
    <form className="add-to-cart" onSubmit={handleSubmit}>
      <div className="field field--inline">
        <label htmlFor="quantite">Quantité</label>
        <input id="quantite" name="quantite" type="number" min="1" defaultValue="1" />
      </div>
      <button type="submit" className="button">
        Ajouter au panier
      </button>
      {message ? <p role="status">{message}</p> : null}
    </form>
  );
}

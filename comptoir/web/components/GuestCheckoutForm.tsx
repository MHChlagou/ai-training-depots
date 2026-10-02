'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useMemo, useState, useSyncExternalStore, type FormEvent } from 'react';
import { createOrder } from '@/lib/api';
import { clearCart, getCartSnapshot, parseCart, subscribeToCart } from '@/lib/cart';
import {
  validateGuestCheckout,
  type GuestCheckoutField,
  type GuestCheckoutValues,
} from '@/lib/validation';

type FieldErrors = Partial<Record<GuestCheckoutField, string>>;

const FIELDS: GuestCheckoutField[] = ['email', 'name', 'company', 'address'];

const EMPTY_VALUES: GuestCheckoutValues = { email: '', name: '', company: '', address: '' };

export function GuestCheckoutForm() {
  const router = useRouter();
  const snapshot = useSyncExternalStore(subscribeToCart, getCartSnapshot, () => null);
  const lines = useMemo(() => parseCart(snapshot), [snapshot]);

  const [values, setValues] = useState<GuestCheckoutValues>(EMPTY_VALUES);
  const [errors, setErrors] = useState<FieldErrors>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [placed, setPlaced] = useState(false);

  if (placed) {
    return <p>Redirection vers la confirmation…</p>;
  }

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

  function update(field: GuestCheckoutField, value: string) {
    setValues((current) => ({ ...current, [field]: value }));
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFormError(null);

    const clientErrors = validateGuestCheckout(values);
    setErrors(clientErrors);
    if (Object.keys(clientErrors).length > 0) {
      return;
    }

    setSubmitting(true);
    const result = await createOrder({
      guest: {
        email: values.email.trim(),
        name: values.name.trim(),
        company: values.company.trim(),
        address: values.address.trim(),
      },
      lines: lines.map((line) => ({ product_id: line.productId, quantity: line.quantity })),
    });
    setSubmitting(false);

    if (result.ok) {
      setPlaced(true);
      router.push(`/commande/confirmation?reference=${encodeURIComponent(result.data.reference)}`);
      clearCart();
      return;
    }

    const apiErrors: FieldErrors = {};
    for (const field of FIELDS) {
      const message = result.errors[`guest.${field}`]?.[0];
      if (message) {
        apiErrors[field] = message;
      }
    }
    setErrors(apiErrors);

    if (Object.keys(apiErrors).length === 0) {
      setFormError(result.message);
    }
  }

  function fieldProps(field: GuestCheckoutField) {
    const error = errors[field];

    return {
      id: `commande-${field}`,
      name: field,
      value: values[field],
      'aria-invalid': error ? true : undefined,
      'aria-describedby': error ? `commande-${field}-erreur` : undefined,
    };
  }

  function fieldError(field: GuestCheckoutField) {
    const error = errors[field];

    return error ? (
      <p id={`commande-${field}-erreur`} className="field__error">
        {error}
      </p>
    ) : null;
  }

  return (
    <form className="form" noValidate onSubmit={handleSubmit}>
      <div className="field">
        <label htmlFor="commande-email">E-mail</label>
        <input
          type="email"
          autoComplete="email"
          {...fieldProps('email')}
          onChange={(event) => update('email', event.target.value)}
        />
        {fieldError('email')}
      </div>
      <div className="field">
        <label htmlFor="commande-name">Nom</label>
        <input
          type="text"
          autoComplete="name"
          {...fieldProps('name')}
          onChange={(event) => update('name', event.target.value)}
        />
        {fieldError('name')}
      </div>
      <div className="field">
        <label htmlFor="commande-company">Société</label>
        <input
          type="text"
          autoComplete="organization"
          {...fieldProps('company')}
          onChange={(event) => update('company', event.target.value)}
        />
        {fieldError('company')}
      </div>
      <div className="field">
        <label htmlFor="commande-address">Adresse</label>
        <textarea
          rows={3}
          autoComplete="street-address"
          {...fieldProps('address')}
          onChange={(event) => update('address', event.target.value)}
        />
        {fieldError('address')}
      </div>
      {formError ? (
        <p role="alert" className="form__error">
          {formError}
        </p>
      ) : null}
      <button type="submit" className="button" disabled={submitting}>
        Valider la commande
      </button>
    </form>
  );
}

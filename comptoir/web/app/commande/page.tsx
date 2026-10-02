'use client';

import { GuestCheckoutForm } from '@/components/GuestCheckoutForm';

export default function CheckoutPage() {
  return (
    <main>
      <h1>Commande sans compte</h1>
      <GuestCheckoutForm />
    </main>
  );
}

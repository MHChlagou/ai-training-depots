import Link from 'next/link';

type ConfirmationPageProps = {
  searchParams: Promise<{ reference?: string | string[] }>;
};

export default async function ConfirmationPage({ searchParams }: ConfirmationPageProps) {
  const { reference } = await searchParams;
  const value = Array.isArray(reference) ? reference[0] : reference;

  if (!value) {
    return (
      <main>
        <p>Aucune commande à afficher.</p>
        <p>
          <Link href="/catalogue">Retour au catalogue</Link>
        </p>
      </main>
    );
  }

  return (
    <main>
      <h1>Commande confirmée</h1>
      <p data-testid="order-reference">Commande n° {value}</p>
      <p>Merci pour votre commande.</p>
      <p>
        <Link href="/catalogue">Retour au catalogue</Link>
      </p>
    </main>
  );
}

import Link from 'next/link';

export default function NotFound() {
  return (
    <main>
      <h1>Page introuvable</h1>
      <p>La page demandée n’existe pas ou plus.</p>
      <p>
        <Link href="/catalogue">Voir le catalogue</Link>
      </p>
    </main>
  );
}

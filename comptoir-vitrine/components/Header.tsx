import Link from "next/link";

export function Header() {
  return (
    <header className="entete">
      <Link href="/" className="logo">
        Comptoir
      </Link>
      <nav className="navigation" aria-label="Navigation principale">
        <Link href="/">Accueil</Link>
        <Link href="/produits">Produits</Link>
        <Link href="/livraison">Livraison</Link>
        <Link href="/a-propos">À propos</Link>
        <Link href="/contact">Contact</Link>
        <Link href="/en">English</Link>
      </nav>
    </header>
  );
}

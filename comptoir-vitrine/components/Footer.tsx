import Link from "next/link";

export function Footer() {
  return (
    <footer className="pied">
      <p>
        © 2026 Comptoir, fournitures et équipements pour cafés, hôtels et
        restaurants.
      </p>
      <p>
        <Link href="/contact">Contact</Link> · <Link href="/a-propos">À propos</Link> ·{" "}
        <Link href="/livraison">Livraison</Link>
      </p>
    </footer>
  );
}

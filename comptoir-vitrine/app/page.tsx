import Link from "next/link";

export default function Accueil() {
  return (
    <>
      <div className="bandeau">Livraison en 24 h partout en France</div>

      <img src="/images/hero.png" alt="Comptoir de café équipé" className="hero" />

      <section className="page">
        <h1>Équipements et fournitures pour cafés, hôtels et restaurants</h1>
        <p>
          Comptoir accompagne les professionnels de la restauration avec une
          sélection de machines à café, de moulins, d&apos;arts de la table et de
          produits d&apos;entretien, au meilleur prix professionnel.
        </p>
        <p>
          <Link href="/produits/machine-expresso-2-groupes?ref=salon">
            Vu au salon : notre machine à expresso 2 groupes
          </Link>
        </p>
      </section>

      <section className="page">
        <h2>Nos catégories</h2>
        <ul>
          <li>
            <Link href="/produits">Machines à café et moulins</Link>
          </li>
          <li>
            <Link href="/produits">Arts de la table et accessoires barista</Link>
          </li>
          <li>
            <Link href="/produits">Traitement de l&apos;eau et entretien</Link>
          </li>
        </ul>
      </section>
    </>
  );
}

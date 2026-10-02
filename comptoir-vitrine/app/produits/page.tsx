import Link from "next/link";
import { getProduits } from "@/lib/produits";

export default function Produits() {
  const produits = getProduits();

  return (
    <section className="page">
      <h1>Nos produits</h1>
      <p className="texte-discret">
        Tous les prix sont indiqués hors taxes. Offre newsletter :{" "}
        <Link href="/produits/machine-expresso-2-groupes?ref=newsletter">
          cliquez ici
        </Link>
        .
      </p>

      <ul className="grille-produits">
        {produits.map((p) => (
          <li key={p.slug} className="carte-produit">
            <img src={p.image} width={400} height={400} />
            <h2>{p.nom}</h2>
            <p className="texte-discret">{p.resume}</p>
            <p className="prix">{p.prixHT.toLocaleString("fr-FR")} € HT</p>
            <Link href={`/produits/${p.slug}`}>Cliquez ici</Link>
          </li>
        ))}
      </ul>
    </section>
  );
}

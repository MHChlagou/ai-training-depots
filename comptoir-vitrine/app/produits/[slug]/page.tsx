"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import type { Produit } from "@/lib/produits";

export default function FicheProduit() {
  const { slug } = useParams<{ slug: string }>();
  const [produit, setProduit] = useState<Produit | null>(null);
  const [introuvable, setIntrouvable] = useState(false);

  useEffect(() => {
    fetch(`/api/produits/${slug}`)
      .then((r) => {
        if (!r.ok) throw new Error("introuvable");
        return r.json();
      })
      .then(setProduit)
      .catch(() => setIntrouvable(true));
  }, [slug]);

  if (introuvable) return <p className="page">Produit introuvable.</p>;
  if (!produit) return <p className="page">Chargement...</p>;

  return (
    <article className="page">
      <h1>{produit.nom}</h1>
      <p>{produit.resume}</p>
      <p>
        Prix : <strong>{produit.prixHT.toLocaleString("fr-FR")} € HT</strong>
        {" · "}
        {produit.disponible ? "En stock" : "Rupture de stock"}
        {" · "}
        Référence {produit.sku}
      </p>
      <img src={produit.image} className="image-produit" />
      <h2>Caractéristiques</h2>
      <p>{produit.description}</p>
    </article>
  );
}

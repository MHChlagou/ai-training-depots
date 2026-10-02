// lib/produits.ts
// Données des produits du site vitrine. Fichier fourni tel quel aux participants.

export type Produit = {
  slug: string;
  sku: string;
  nom: string;
  categorie: string;
  resume: string; // une phrase, utilisée pour la meta description
  description: string;
  prixHT: number; // en euros
  image: string; // chemin sous public/
  disponible: boolean;
  majLe: string; // date ISO de dernière mise à jour de la fiche
};

export const produits: Produit[] = [
  {
    slug: "machine-expresso-2-groupes",
    sku: "CPT-EXP-2G",
    nom: "Machine à expresso professionnelle 2 groupes",
    categorie: "Machines à café",
    resume:
      "Machine à expresso 2 groupes pour cafés et restaurants, chaudière 11 litres, jusqu'à 300 tasses par jour.",
    description:
      "Machine à expresso semi-automatique à deux groupes, chaudière cuivre de 11 litres, buse vapeur orientable et eau chaude séparée. Dimensionnée pour 150 à 300 tasses par jour. Alimentation 230 V ou 400 V selon configuration.",
    prixHT: 3890,
    image: "/images/produits/machine-expresso-2-groupes.jpg",
    disponible: true,
    majLe: "2026-09-01",
  },
  {
    slug: "moulin-cafe-a-la-demande",
    sku: "CPT-MOU-64",
    nom: "Moulin à café à la demande, meules 64 mm",
    categorie: "Machines à café",
    resume:
      "Moulin à café professionnel à la demande, meules plates de 64 mm, dosage programmable simple et double.",
    description:
      "Moulin à la demande à meules plates de 64 mm, réglage micrométrique de la mouture, deux doses programmables, trémie de 1,2 kg.",
    prixHT: 790,
    image: "/images/produits/moulin-cafe-a-la-demande.jpg",
    disponible: true,
    majLe: "2026-09-01",
  },
  {
    slug: "lot-12-tasses-expresso",
    sku: "CPT-TAS-12",
    nom: "Lot de 12 tasses à expresso en porcelaine",
    categorie: "Arts de la table",
    resume:
      "Lot de 12 tasses à expresso 7 cl en porcelaine épaisse, compatibles lave-vaisselle professionnel.",
    description:
      "Tasses à expresso de 7 cl en porcelaine épaisse qui garde la chaleur, empilables, avec soucoupes. Compatibles lave-vaisselle professionnel.",
    prixHT: 54,
    image: "/images/produits/lot-12-tasses-expresso.jpg",
    disponible: true,
    majLe: "2026-08-15",
  },
  {
    slug: "adoucisseur-eau-8-litres",
    sku: "CPT-ADO-8L",
    nom: "Adoucisseur d'eau 8 litres pour machine à café",
    categorie: "Traitement de l'eau",
    resume:
      "Adoucisseur d'eau manuel de 8 litres qui protège la chaudière des machines à expresso du calcaire.",
    description:
      "Adoucisseur à résine régénérable au sel, capacité 8 litres, raccords 3/8 pouces. Protège la chaudière et les groupes du calcaire.",
    prixHT: 129,
    image: "/images/produits/adoucisseur-eau-8-litres.jpg",
    disponible: true,
    majLe: "2026-07-10",
  },
  {
    slug: "pichet-lait-inox-60cl",
    sku: "CPT-PIC-60",
    nom: "Pichet à lait inox 60 cl",
    categorie: "Accessoires barista",
    resume:
      "Pichet à lait en inox 18/10 de 60 cl avec bec verseur fin pour le latte art.",
    description:
      "Pichet en inox 18/10, contenance 60 cl, bec verseur fin, graduations intérieures.",
    prixHT: 19,
    image: "/images/produits/pichet-lait-inox-60cl.jpg",
    disponible: false,
    majLe: "2026-06-30",
  },
  {
    slug: "kit-nettoyage-machine-expresso",
    sku: "CPT-NET-KIT",
    nom: "Kit de nettoyage pour machine à expresso",
    categorie: "Entretien",
    resume:
      "Kit d'entretien quotidien pour machine à expresso : pastilles de nettoyage, brosse de groupe et filtre aveugle.",
    description:
      "100 pastilles de nettoyage des groupes, une brosse de groupe, un filtre aveugle et un guide d'entretien quotidien.",
    prixHT: 39,
    image: "/images/produits/kit-nettoyage-machine-expresso.jpg",
    disponible: true,
    majLe: "2026-09-10",
  },
];

export function getProduits(): Produit[] {
  return produits;
}

export function getProduit(slug: string): Produit | undefined {
  return produits.find((p) => p.slug === slug);
}

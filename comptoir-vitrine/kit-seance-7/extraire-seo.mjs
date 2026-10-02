#!/usr/bin/env node
// extraire-seo.mjs : relevé déterministe des signaux SEO et GEO d'une ou plusieurs pages.
//
// Usage :
//   node extraire-seo.mjs http://localhost:3000 / /produits /livraison
//   (premier argument : origine du site ; suivants : chemins à analyser, "/" par défaut)
//
// Le script lit le HTML BRUT renvoyé par le serveur, sans exécuter JavaScript :
// c'est ce que voit un robot qui ne rend pas les pages. Un contenu absent ici
// (par exemple un produit chargé dans un useEffect) est invisible pour lui.
// Node 18 ou plus récent (fetch natif). Aucune dépendance.

const [origine = "http://localhost:3000", ...chemins] = process.argv.slice(2);
const pages = chemins.length ? chemins : ["/"];

// Propriétés minimales vérifiées par type schema.org (contrôle de présence, pas validation complète).
const PROPRIETES_ATTENDUES = {
  Organization: ["name", "url", "logo"],
  Product: ["name", "offers"],
  Offer: ["price", "priceCurrency", "availability"],
  FAQPage: ["mainEntity"],
  Article: ["headline", "author", "datePublished"],
  BreadcrumbList: ["itemListElement"],
};

const texte = (s) =>
  s.replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/&nbsp;/g, " ")
    .replace(/&#x27;|&#39;/g, "'")
    .replace(/&quot;/g, '"')
    .replace(/&amp;/g, "&")
    .replace(/\s+/g, " ")
    .trim();

const attr = (balise, nom) => {
  const m = balise.match(new RegExp(`${nom}\\s*=\\s*("([^"]*)"|'([^']*)')`, "i"));
  return m ? (m[2] ?? m[3]) : null;
};

const balises = (html, regex) => html.match(regex) ?? [];

function meta(html, nom) {
  const b = balises(html, /<meta\b[^>]*>/gi).find(
    (t) => (attr(t, "name") ?? attr(t, "property") ?? "").toLowerCase() === nom
  );
  return b ? attr(b, "content") : null;
}

function verifierJsonLd(objet, anomalies, chemin = "") {
  if (Array.isArray(objet)) {
    objet.forEach((o, i) => verifierJsonLd(o, anomalies, `${chemin}[${i}]`));
    return;
  }
  if (!objet || typeof objet !== "object") return;
  const type = objet["@type"];
  const attendues = PROPRIETES_ATTENDUES[type];
  if (attendues) {
    for (const p of attendues) {
      if (!(p in objet)) anomalies.push(`${type}${chemin} : propriété "${p}" absente`);
    }
  }
  for (const [cle, valeur] of Object.entries(objet)) {
    if (typeof valeur === "object") verifierJsonLd(valeur, anomalies, `${chemin}.${cle}`);
  }
}

function typesJsonLd(objet, acc = []) {
  if (Array.isArray(objet)) objet.forEach((o) => typesJsonLd(o, acc));
  else if (objet && typeof objet === "object") {
    if (objet["@type"]) acc.push(objet["@type"]);
    if (objet["@graph"]) typesJsonLd(objet["@graph"], acc);
  }
  return acc;
}

async function analyserPage(chemin) {
  const url = new URL(chemin, origine).toString();
  const rep = await fetch(url, { redirect: "manual" });
  const html = await rep.text();

  const h1 = balises(html, /<h1\b[^>]*>[\s\S]*?<\/h1>/gi).map(texte);
  const niveaux = balises(html, /<h[1-6]\b/gi).map((t) => Number(t[2]));
  const sauts = niveaux.filter((n, i) => i > 0 && n - niveaux[i - 1] > 1).length;

  const imgs = balises(html, /<img\b[^>]*>/gi);
  const imgsSansAlt = imgs.filter((t) => attr(t, "alt") === null).length;
  const imgsSansDimensions = imgs.filter((t) => !attr(t, "width") || !attr(t, "height")).length;

  const canoniques = balises(html, /<link\b[^>]*rel=["']canonical["'][^>]*>/gi).map((t) => attr(t, "href"));
  const hreflang = balises(html, /<link\b[^>]*hreflang=[^>]*>/gi).map(
    (t) => `${attr(t, "hreflang")} -> ${attr(t, "href")}`
  );

  const jsonld = [];
  const anomaliesJsonLd = [];
  for (const bloc of balises(html, /<script\b[^>]*type=["']application\/ld\+json["'][^>]*>[\s\S]*?<\/script>/gi)) {
    const contenu = bloc.replace(/^<script[^>]*>/i, "").replace(/<\/script>$/i, "");
    try {
      const objet = JSON.parse(contenu);
      jsonld.push(...typesJsonLd(objet));
      verifierJsonLd(objet, anomaliesJsonLd);
    } catch {
      anomaliesJsonLd.push("bloc JSON-LD illisible (JSON invalide)");
    }
  }

  const corps = texte(html.match(/<body[\s\S]*<\/body>/i)?.[0] ?? html);

  return {
    url,
    statut: rep.status,
    xRobotsTag: rep.headers.get("x-robots-tag"),
    langHtml: attr(html.match(/<html\b[^>]*>/i)?.[0] ?? "", "lang"),
    title: texte(html.match(/<title[^>]*>([\s\S]*?)<\/title>/i)?.[1] ?? "") || null,
    metaDescription: meta(html, "description"),
    metaRobots: meta(html, "robots"),
    canoniques,
    hreflang,
    ogTitle: meta(html, "og:title"),
    h1,
    sautsDeNiveauTitres: sauts,
    images: imgs.length,
    imagesSansAlt: imgsSansAlt,
    imagesSansDimensions: imgsSansDimensions,
    typesJsonLd: jsonld,
    anomaliesJsonLd,
    balisesTime: balises(html, /<time\b/gi).length,
    motsDansHtmlInitial: corps ? corps.split(" ").length : 0,
    debutTexte: corps.slice(0, 200),
  };
}

async function fichier(chemin) {
  try {
    const rep = await fetch(new URL(chemin, origine), { redirect: "manual" });
    const corps = rep.ok ? await rep.text() : "";
    return { chemin, statut: rep.status, extrait: corps.slice(0, 400) };
  } catch (e) {
    return { chemin, statut: "erreur", extrait: String(e) };
  }
}

const resultat = {
  origine,
  analyseLe: new Date().toISOString(),
  site: await Promise.all(["/robots.txt", "/sitemap.xml", "/llms.txt"].map(fichier)),
  pages: [],
};
for (const p of pages) {
  try {
    resultat.pages.push(await analyserPage(p));
  } catch (e) {
    resultat.pages.push({ url: p, erreur: String(e) });
  }
}
console.log(JSON.stringify(resultat, null, 2));

#!/usr/bin/env node
// scripts/comparer-lighthouse.mjs : compare deux séries de mesures Lighthouse.
//
// Usage : node scripts/comparer-lighthouse.mjs mesures/avant mesures/apres
// Lit les fichiers <page>-<n>.report.json produits par scripts/mesurer.sh,
// calcule la MÉDIANE des passages par page et affiche un tableau Markdown.

import { readdirSync, readFileSync } from "node:fs";
import { join } from "node:path";

const [dossierAvant, dossierApres] = process.argv.slice(2);
if (!dossierAvant || !dossierApres) {
  console.error("Usage : node scripts/comparer-lighthouse.mjs <dossier-avant> <dossier-apres>");
  process.exit(1);
}

const mediane = (valeurs) => {
  const v = valeurs.filter((x) => typeof x === "number").sort((a, b) => a - b);
  if (!v.length) return null;
  const m = Math.floor(v.length / 2);
  return v.length % 2 ? v[m] : (v[m - 1] + v[m]) / 2;
};

function lire(dossier) {
  const parPage = {};
  for (const f of readdirSync(dossier).filter((f) => f.endsWith(".report.json"))) {
    const page = f.replace(/-\d+\.report\.json$/, "");
    const r = JSON.parse(readFileSync(join(dossier, f), "utf8"));
    (parPage[page] ??= []).push({
      perf: r.categories.performance?.score * 100,
      seo: r.categories.seo?.score * 100,
      a11y: r.categories.accessibility?.score * 100,
      lcp: r.audits["largest-contentful-paint"]?.numericValue / 1000,
      cls: r.audits["cumulative-layout-shift"]?.numericValue,
      tbt: r.audits["total-blocking-time"]?.numericValue,
    });
  }
  const res = {};
  for (const [page, mesures] of Object.entries(parPage)) {
    res[page] = {};
    for (const cle of ["perf", "seo", "a11y", "lcp", "cls", "tbt"]) {
      res[page][cle] = mediane(mesures.map((m) => m[cle]));
    }
    res[page].passages = mesures.length;
  }
  return res;
}

const avant = lire(dossierAvant);
const apres = lire(dossierApres);
const fmt = (x, d = 0) => (x === null || x === undefined ? "n/d" : x.toFixed(d));
const cellule = (a, b, d = 0) => `${fmt(a, d)} -> ${fmt(b, d)}`;

console.log("| Page | Perf | SEO | Accessibilité | LCP (s) | CLS | TBT (ms) | Passages |");
console.log("|---|---|---|---|---|---|---|---|");
for (const page of Object.keys({ ...avant, ...apres }).sort()) {
  const a = avant[page] ?? {};
  const b = apres[page] ?? {};
  console.log(
    `| ${page} | ${cellule(a.perf, b.perf)} | ${cellule(a.seo, b.seo)} | ${cellule(a.a11y, b.a11y)} | ` +
      `${cellule(a.lcp, b.lcp, 2)} | ${cellule(a.cls, b.cls, 3)} | ${cellule(a.tbt, b.tbt)} | ` +
      `${a.passages ?? 0} / ${b.passages ?? 0} |`
  );
}
console.log("\nMédiane des passages, mesures de laboratoire (émulation mobile). L'INP n'est pas mesuré en laboratoire.");

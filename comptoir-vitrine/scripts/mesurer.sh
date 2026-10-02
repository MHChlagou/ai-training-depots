#!/usr/bin/env bash
# scripts/mesurer.sh : mesure Lighthouse reproductible (3 passages par page).
#
# Usage : bash scripts/mesurer.sh <etiquette> [origine]
#   bash scripts/mesurer.sh avant
#   bash scripts/mesurer.sh apres http://localhost:3000
#
# Prérequis : site lancé en build de production (npm run build && npm run start),
# Chrome ou Chromium installé (sinon définir CHROME_PATH).
# Résultats : mesures/<etiquette>/<page>-<n>.report.json et .report.html

set -euo pipefail

ETIQUETTE="${1:?Indiquez une étiquette, par exemple avant ou apres}"
ORIGINE="${2:-http://localhost:3000}"
PASSAGES=3
PAGES=("/" "/produits" "/produits/machine-expresso-2-groupes" "/livraison")

DOSSIER="mesures/${ETIQUETTE}"
mkdir -p "${DOSSIER}"

# Le site doit répondre avant de mesurer.
if ! curl -sf -o /dev/null "${ORIGINE}/"; then
  echo "Le site ne répond pas sur ${ORIGINE}. Lancez npm run build && npm run start." >&2
  exit 1
fi

for page in "${PAGES[@]}"; do
  # "/" devient "accueil", "/produits/x" devient "produits_x"
  nom="$(echo "${page#/}" | tr '/' '_')"
  nom="${nom:-accueil}"
  for n in $(seq 1 "${PASSAGES}"); do
    echo "Mesure ${page} (passage ${n}/${PASSAGES})"
    npx --yes lighthouse "${ORIGINE}${page}" \
      --only-categories=performance,accessibility,best-practices,seo \
      --output=json --output=html \
      --output-path="${DOSSIER}/${nom}-${n}" \
      --chrome-flags="--headless=new" \
      --quiet
  done
done

echo "Terminé : ${DOSSIER}"

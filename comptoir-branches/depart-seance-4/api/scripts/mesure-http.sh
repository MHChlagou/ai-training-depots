#!/usr/bin/env bash
# Mesure complémentaire « vue du client » : temps de réponse HTTP réel via curl.
# Prérequis : l'API tourne (php artisan serve, ou votre conteneur) et répond sur BASE_URL.
#
# Usage : scripts/mesure-http.sh "/api/orders?customer_id=42" 20
#         BASE_URL=http://localhost:8080 TOKEN=xxx scripts/mesure-http.sh "/api/orders" 20
set -euo pipefail

URI="${1:?Usage : $0 <uri> [nombre_appels]}"
RUNS="${2:-20}"
BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
AUTH=()
if [[ -n "${TOKEN:-}" ]]; then
  AUTH=(-H "Authorization: Bearer ${TOKEN}")
fi

# Appel d'échauffement, ignoré.
curl -s -o /dev/null "${AUTH[@]}" -H 'Accept: application/json' "${BASE_URL}${URI}"

TIMES=()
for _ in $(seq 1 "$RUNS"); do
  t=$(curl -s -o /dev/null -w '%{http_code} %{time_total}' "${AUTH[@]}" -H 'Accept: application/json' "${BASE_URL}${URI}")
  code="${t%% *}"
  if [[ "$code" != "200" ]]; then
    echo "Réponse HTTP ${code} : arrêt." >&2
    exit 1
  fi
  TIMES+=("${t##* }")
done

printf '%s\n' "${TIMES[@]}" | sort -n | awk -v uri="$URI" '
  { v[NR] = $1 * 1000 }
  END {
    med = (NR % 2) ? v[(NR + 1) / 2] : (v[NR / 2] + v[NR / 2 + 1]) / 2
    printf "GET %s : %d appels, médiane %.1f ms, min %.1f ms, max %.1f ms\n", uri, NR, med, v[1], v[NR]
  }'

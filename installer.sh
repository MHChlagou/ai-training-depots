#!/usr/bin/env bash
# installer.sh
#
# Installe les dépôts d'exercice de la formation sur le poste du participant, à partir de ce clone
# d'ai-training-depots. Chaque dépôt est un vrai dépôt git avec ses branches de départ, cloné depuis
# un dépôt « origin » local : git fetch origin, git switch depart-seance-N et origin/HEAD fonctionnent
# comme avec un dépôt distant.
#
#   comptoir             main, depart-seance-2 à depart-seance-5
#   comptoir-vulnerable  main (= depart-seance-4) et sprint-12
#   comptoir-vitrine     main et depart-seance-7
#   corpus-rag           main
#
# Usage : bash installer.sh [DOSSIER]       (défaut : ~/formation)
# Résultat : DOSSIER/<dépôt>/ (copies de travail) et DOSSIER/.origines/<dépôt>.git (leurs « origin »).
# Un dépôt déjà présent dans DOSSIER n'est jamais écrasé : il est ignoré.

set -euo pipefail

SOURCE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOSSIER="${1:-$HOME/formation}"
AUTEUR_NOM="Formation Comptoir"
AUTEUR_EMAIL="formation@exemple.test"
DATE_EPOCH=1756710000   # 2026-09-01T09:00:00+02:00 : dates fixes, mêmes identifiants de commit sur tous les postes
BRANCHES_COMPTOIR=(depart-seance-2 depart-seance-3 depart-seance-4 depart-seance-5)

die() { echo "installer.sh : $*" >&2; exit 1; }
info() { echo "==> $*"; }

command -v git >/dev/null || die "git est introuvable"
command -v tar >/dev/null || die "tar est introuvable"
git -C "$SOURCE" rev-parse --is-inside-work-tree >/dev/null 2>&1 \
  || die "lancez ce script depuis un clone git d'ai-training-depots"

mkdir -p "$DOSSIER/.origines"
DOSSIER="$(cd "$DOSSIER" && pwd)"
TRAVAIL="$(mktemp -d)"
trap 'rm -rf "$TRAVAIL"' EXIT

# Extrait le contenu versionné de SOURCE/$1 (état du dernier commit) dans $2.
extraire() {
  mkdir -p "$2"
  git -C "$SOURCE" archive "HEAD:$1" | tar -x -C "$2"
}

NUMERO_COMMIT=0
committer() {
  local depot="$1" message="$2" date
  date="$(TZ=UTC date -d "@$((DATE_EPOCH + 60 * NUMERO_COMMIT))" '+%Y-%m-%dT%H:%M:%S+00:00')"
  NUMERO_COMMIT=$((NUMERO_COMMIT + 1))
  git -C "$depot" add -A
  GIT_AUTHOR_NAME="$AUTEUR_NOM" GIT_AUTHOR_EMAIL="$AUTEUR_EMAIL" GIT_AUTHOR_DATE="$date" \
  GIT_COMMITTER_NAME="$AUTEUR_NOM" GIT_COMMITTER_EMAIL="$AUTEUR_EMAIL" GIT_COMMITTER_DATE="$date" \
    git -C "$depot" commit --quiet --allow-empty -m "$message"
}

init_depot() {
  mkdir -p "$1"
  git -C "$1" init --quiet -b main
  git -C "$1" config core.autocrlf false
}

# Applique une surcouche : fichiers ajoutés ou remplacés, puis chemins de SUPPRESSIONS.txt retirés.
appliquer_surcouche() {
  local surcouche="$1" depot="$2" chemin
  extraire "$surcouche" "$depot"
  if [[ -f "$depot/SUPPRESSIONS.txt" ]]; then
    while IFS= read -r chemin || [[ -n "$chemin" ]]; do
      chemin="${chemin%%#*}"; chemin="$(echo "$chemin" | xargs)"
      [[ -z "$chemin" ]] && continue
      case "$chemin" in /*|*..*) die "SUPPRESSIONS.txt : chemin refusé ($chemin)" ;; esac
      rm -rf "${depot:?}/$chemin"
    done < "$depot/SUPPRESSIONS.txt"
    rm -f "$depot/SUPPRESSIONS.txt"
  fi
}

message_branche() {
  case "$1" in
    main) printf '%s\n\n%s\n' "chore: import initial de comptoir" \
      "API Laravel 12 (api/) et front Next.js 16 (web/). État de départ de la séance 1." ;;
    depart-seance-2) printf '%s\n\n%s\n' "docs: ajoute CLAUDE.md v1 et docs/architecture.md" \
      "État de départ de la séance 2 : livrables de la séance 1, sans dossier .claude/." ;;
    depart-seance-3) printf '%s\n\n%s\n' "chore: ajoute le kit Claude Code de l'équipe" \
      "État de départ de la séance 3 : .claude/ (rules, skills, agent reviewer, hooks), .mcp.json (comptoir-db), CLAUDE.md et .gitignore de la séance 2." ;;
    depart-seance-4) printf '%s\n\n%s\n' "feat: remises par palier, GET /api/orders optimisé, calcul de facture refactoré" \
      "État de départ de la séance 4 : livrables de la séance 3." ;;
    depart-seance-5) printf '%s\n\n%s\n' "chore: ajoute le kit de revue et de sécurité" \
      "État de départ de la séance 5 : agent security-auditor, skill checklist-revue, hook garde-securite, workflows de revue Claude, consignes de revue dans CLAUDE.md." ;;
  esac
}

# Publie le dépôt construit $2 dans DOSSIER/.origines/$1.git, puis le clone dans DOSSIER/$1.
installer() {
  local nom="$1" construit="$2" origine="$DOSSIER/.origines/$1.git"
  rm -rf "$origine"
  git clone --quiet --bare "$construit" "$origine"
  git -C "$origine" remote remove origin
  git -C "$origine" symbolic-ref HEAD refs/heads/main
  git clone --quiet "$origine" "$DOSSIER/$nom"
  printf '    %-22s %s\n' "$nom" "$(git -C "$origine" for-each-ref --format='%(refname:short)' refs/heads | tr '\n' ' ')"
}

a_installer() {
  if [[ -e "$DOSSIER/$1" ]]; then
    echo "    $1 : déjà présent dans $DOSSIER, ignoré"
    return 1
  fi
}

# comptoir (et comptoir-vulnerable, construit à partir de sa branche depart-seance-4)
COMPTOIR="$TRAVAIL/comptoir"
info "comptoir : main, ${BRANCHES_COMPTOIR[*]}"
init_depot "$COMPTOIR"
extraire comptoir "$COMPTOIR"
committer "$COMPTOIR" "$(message_branche main)"
precedente=main
for branche in "${BRANCHES_COMPTOIR[@]}"; do
  git -C "$COMPTOIR" switch --quiet -c "$branche" "$precedente"
  appliquer_surcouche "comptoir-branches/$branche" "$COMPTOIR"
  committer "$COMPTOIR" "$(message_branche "$branche")"
  precedente="$branche"
done
git -C "$COMPTOIR" switch --quiet main

info "comptoir-vulnerable : main (= depart-seance-4), sprint-12"
VULNERABLE="$TRAVAIL/comptoir-vulnerable"
git clone --quiet --no-local --single-branch --branch depart-seance-4 "$COMPTOIR" "$VULNERABLE"
git -C "$VULNERABLE" branch --quiet -m depart-seance-4 main
git -C "$VULNERABLE" switch --quiet -c sprint-12 main
shopt -s nullglob
patches=("$SOURCE"/comptoir-vulnerable/patches/sprint-12/*.patch)
shopt -u nullglob
[[ ${#patches[@]} -gt 0 ]] || die "aucun patch dans comptoir-vulnerable/patches/sprint-12"
GIT_COMMITTER_NAME="$AUTEUR_NOM" GIT_COMMITTER_EMAIL="$AUTEUR_EMAIL" \
  git -C "$VULNERABLE" am --quiet --committer-date-is-author-date "${patches[@]}" \
  || { git -C "$VULNERABLE" am --abort 2>/dev/null || true; die "échec de git am sur comptoir-vulnerable"; }
git -C "$VULNERABLE" switch --quiet main

info "comptoir-vitrine : main, depart-seance-7"
VITRINE="$TRAVAIL/comptoir-vitrine"
init_depot "$VITRINE"
extraire comptoir-vitrine "$VITRINE"
committer "$VITRINE" "chore: import initial de comptoir-vitrine"
git -C "$VITRINE" switch --quiet -c depart-seance-7 main
appliquer_surcouche comptoir-vitrine-branches/depart-seance-7 "$VITRINE"
committer "$VITRINE" "$(printf '%s\n\n%s\n' "chore: ajoute les permissions Claude Code de l'atelier" "État de départ de la séance 7 : .claude/settings.json (permissions de l'atelier SEO/GEO).")"
git -C "$VITRINE" switch --quiet main

info "corpus-rag : main"
CORPUS="$TRAVAIL/corpus-rag"
init_depot "$CORPUS"
extraire corpus-rag "$CORPUS"
committer "$CORPUS" "chore: import initial de corpus-rag"

echo
info "Installation dans $DOSSIER :"
for nom in comptoir comptoir-vulnerable comptoir-vitrine corpus-rag; do
  if a_installer "$nom"; then installer "$nom" "$TRAVAIL/$nom"; fi
done

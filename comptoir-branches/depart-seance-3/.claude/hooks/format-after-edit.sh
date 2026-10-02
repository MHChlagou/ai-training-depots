#!/usr/bin/env bash
# .claude/hooks/format-after-edit.sh
#
# Hook PostToolUse (matcher "Edit|Write") du dépôt comptoir.
# Formate le fichier que Claude vient de créer ou de modifier :
#   - api/**/*.php (hors *.blade.php)            -> Laravel Pint
#   - web/**/*.{ts,tsx,js,jsx,mjs,cjs,json,css,scss,md,mdx} -> Prettier
#
# Entrée  : JSON de l'événement sur stdin (tool_name, tool_input.file_path, ...).
# Sorties :
#   exit 0 -> rien à signaler (fichier formaté, ou fichier non concerné)
#   exit 2 -> le formateur a échoué : stderr est montré à Claude (l'édition a déjà eu lieu,
#             PostToolUse ne peut pas l'annuler, mais Claude voit l'erreur et peut corriger)
#   exit 1 -> problème d'outillage (jq absent) : simple avertissement non bloquant
#
# Prérequis : jq, api/vendor/bin/pint (composer install), web/node_modules/.bin/prettier (npm ci).

set -uo pipefail

INPUT="$(cat)"

if ! command -v jq >/dev/null 2>&1; then
  echo "format-after-edit : jq est introuvable, formatage ignoré (installez jq)." >&2
  exit 1
fi

FILE_PATH="$(printf '%s' "$INPUT" | jq -r '.tool_input.file_path // empty')"

# Rien à faire si l'outil ne porte pas sur un fichier ou si le fichier n'existe plus.
[ -n "$FILE_PATH" ] || exit 0
FILE_PATH="${FILE_PATH//\\//}"   # chemins Windows : \ -> /
[ -f "$FILE_PATH" ] || exit 0

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"
PROJECT_DIR="${PROJECT_DIR//\\//}"

# Ne jamais toucher aux dépendances ni aux fichiers générés.
case "$FILE_PATH" in
  */vendor/* | */node_modules/* | */.next/* | */storage/* | */bootstrap/cache/*) exit 0 ;;
esac

case "$FILE_PATH" in
  "$PROJECT_DIR"/api/*.blade.php)
    exit 0
    ;;

  "$PROJECT_DIR"/api/*.php)
    PINT="$PROJECT_DIR/api/vendor/bin/pint"
    if [ ! -x "$PINT" ]; then
      echo "format-after-edit : Pint absent ($PINT). Lancez 'cd api && composer install'." >&2
      exit 1
    fi
    if ! OUTPUT="$(cd "$PROJECT_DIR/api" && "$PINT" "$FILE_PATH" 2>&1)"; then
      echo "Pint n'a pas pu formater $FILE_PATH (erreur de syntaxe probable) :" >&2
      echo "$OUTPUT" >&2
      exit 2
    fi
    ;;

  "$PROJECT_DIR"/web/*.ts | "$PROJECT_DIR"/web/*.tsx | \
  "$PROJECT_DIR"/web/*.js | "$PROJECT_DIR"/web/*.jsx | \
  "$PROJECT_DIR"/web/*.mjs | "$PROJECT_DIR"/web/*.cjs | \
  "$PROJECT_DIR"/web/*.json | "$PROJECT_DIR"/web/*.css | \
  "$PROJECT_DIR"/web/*.scss | "$PROJECT_DIR"/web/*.md | \
  "$PROJECT_DIR"/web/*.mdx)
    PRETTIER="$PROJECT_DIR/web/node_modules/.bin/prettier"
    if [ ! -x "$PRETTIER" ]; then
      echo "format-after-edit : Prettier absent ($PRETTIER). Lancez 'cd web && npm ci'." >&2
      exit 1
    fi
    if ! OUTPUT="$(cd "$PROJECT_DIR/web" && "$PRETTIER" --write "$FILE_PATH" 2>&1)"; then
      echo "Prettier n'a pas pu formater $FILE_PATH (erreur de syntaxe probable) :" >&2
      echo "$OUTPUT" >&2
      exit 2
    fi
    ;;

  *)
    # Fichier hors api/ et web/ (CLAUDE.md, .claude/, docs...) : pas de formatage.
    exit 0
    ;;
esac

exit 0

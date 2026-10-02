#!/usr/bin/env bash
# .claude/hooks/protect-migrations.sh   (BONUS de l'atelier)
#
# Hook PreToolUse (matcher "Edit|Write").
# Bloque toute modification d'une migration déjà suivie par git dans api/database/migrations/.
# La création d'une NOUVELLE migration reste possible.
# C'est la version déterministe de la règle écrite dans .claude/rules/migrations.md.
#
# exit 0 -> pas d'objection (le flux de permissions normal s'applique)
# exit 2 -> modification bloquée, stderr renvoyé à Claude

set -uo pipefail

INPUT="$(cat)"

if ! command -v jq >/dev/null 2>&1; then
  echo "protect-migrations : jq est introuvable, contrôle ignoré." >&2
  exit 1
fi

FILE_PATH="$(printf '%s' "$INPUT" | jq -r '.tool_input.file_path // empty')"
[ -n "$FILE_PATH" ] || exit 0
FILE_PATH="${FILE_PATH//\\//}"

case "$FILE_PATH" in
  */api/database/migrations/*.php) ;;
  *) exit 0 ;;
esac

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"

# Le fichier est-il déjà versionné ? Si oui, c'est une migration existante : on bloque.
if git -C "$PROJECT_DIR" ls-files --error-unmatch "$FILE_PATH" >/dev/null 2>&1; then
  echo "Bloqué : $FILE_PATH est une migration déjà versionnée. Ne la modifiez pas : créez une nouvelle migration avec 'php artisan make:migration'." >&2
  exit 2
fi

exit 0

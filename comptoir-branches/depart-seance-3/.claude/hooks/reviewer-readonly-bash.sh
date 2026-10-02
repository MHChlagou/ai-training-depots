#!/usr/bin/env bash
# .claude/hooks/reviewer-readonly-bash.sh
#
# Hook PreToolUse (matcher "Bash") déclaré dans le frontmatter du subagent "reviewer".
# Il ne s'exécute que pendant que le reviewer travaille.
# Objectif : garantir que le reviewer n'utilise Bash qu'en lecture.
#
# Autorisé : git diff | log | show | status | blame, ls, cat, head, tail, wc, grep,
#            éventuellement enchaînés par des pipes ("git diff | head -200").
# Refusé   : tout le reste, les redirections, les enchaînements ; && ||, les substitutions
#            et l'option --output de git (qui écrit dans un fichier).
#
# exit 0 -> pas d'objection (le flux de permissions normal s'applique)
# exit 2 -> appel bloqué, stderr renvoyé au reviewer

set -uo pipefail

INPUT="$(cat)"

if ! command -v jq >/dev/null 2>&1; then
  echo "reviewer : jq introuvable, commande Bash refusée par prudence." >&2
  exit 2
fi

COMMAND="$(printf '%s' "$INPUT" | jq -r '.tool_input.command // empty')"
[ -n "$COMMAND" ] || exit 0

# 1. Refuser les redirections, enchaînements et substitutions.
case "$COMMAND" in
  *'>'* | *'<'* | *';'* | *'&'* | *'`'* | *'$('* | *'--output'*)
    echo "Refusé : le reviewer est en lecture seule (pas de redirection, d'enchaînement ni de substitution). Commande : $COMMAND" >&2
    exit 2
    ;;
esac

# 2. Chaque segment séparé par un pipe doit commencer par une commande de lecture.
IFS='|' read -r -a SEGMENTS <<< "$COMMAND"
for SEGMENT in "${SEGMENTS[@]}"; do
  SEGMENT="$(printf '%s' "$SEGMENT" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
  if ! printf '%s' "$SEGMENT" | grep -Eq '^(git (diff|log|show|status|blame)|ls|cat|head|tail|wc|grep)([[:space:]]|$)'; then
    echo "Refusé : le reviewer ne peut lancer que des commandes de lecture (git diff/log/show/status/blame, ls, cat, head, tail, wc, grep). Segment refusé : $SEGMENT" >&2
    exit 2
  fi
done

exit 0

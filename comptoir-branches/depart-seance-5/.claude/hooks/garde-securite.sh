#!/usr/bin/env bash
# .claude/hooks/garde-securite.sh
#
# Hook PreToolUse (séance 4) : bloque AVANT exécution
#   1. tout accès aux fichiers de secrets (.env, .env.*, clés privées, ~/.ssh, ~/.aws)
#      par les outils Read, Edit, Write, Grep et par des commandes Bash ;
#   2. les commandes Bash dangereuses (suppression récursive de la racine ou du projet,
#      push forcé, exfiltration de fichiers par curl/wget, pipe vers un shell,
#      destruction de base de données, sudo, chmod 777, affichage de l'environnement).
#
# Contrat (doc Claude Code, hooks reference) :
#   - entrée : un objet JSON sur stdin avec notamment "tool_name" et "tool_input" ;
#       Bash        -> tool_input.command
#       Read/Edit/Write -> tool_input.file_path (toujours absolu)
#       Grep        -> tool_input.path et tool_input.glob (facultatifs)
#   - sortie : exit 2 = blocage ; le texte écrit sur stderr est renvoyé à Claude
#     comme raison du refus. exit 0 = pas de décision, le flux de permissions normal s'applique.
#   - ATTENTION : exit 1 NE BLOQUE PAS (erreur non bloquante). Toujours utiliser exit 2.
#
# Dépendance : jq. Si jq est absent, le hook bloque par précaution (fail closed).
# Limite assumée : c'est un filtre par motifs. Il complète les règles "deny" de
# .claude/settings.json et le sandbox, il ne les remplace pas.

set -uo pipefail

bloquer() {
  echo "BLOQUÉ par garde-securite : $1. Action interdite par la politique de sécurité du projet ; ne cherchez pas à la contourner, signalez-la à l'utilisateur." >&2
  exit 2
}

if ! command -v jq >/dev/null 2>&1; then
  echo "BLOQUÉ par garde-securite : jq est introuvable, impossible d'analyser l'appel d'outil (installez jq)." >&2
  exit 2
fi

INPUT="$(cat)"
TOOL="$(jq -r '.tool_name // empty' <<<"$INPUT" 2>/dev/null)" || bloquer "entrée JSON illisible"

# Renvoie 0 (vrai) si le chemin désigne un fichier de secrets.
est_chemin_secret() {
  local chemin="${1//\\//}"          # normalise les séparateurs Windows
  local base="${chemin##*/}"
  case "$base" in
    .env.example|.env.sample|.env.dist|.env.testing.example) return 1 ;;
    .env|.env.*|*.pem|*.p12|*.pfx|id_rsa|id_rsa.*|id_ed25519|id_ed25519.*) return 0 ;;
  esac
  case "$chemin" in
    */.ssh/*|*/.aws/*|*/.gnupg/*|*/.docker/config.json) return 0 ;;
  esac
  return 1
}

case "$TOOL" in
  Read|Edit|Write)
    FICHIER="$(jq -r '.tool_input.file_path // empty' <<<"$INPUT")"
    if [[ -n "$FICHIER" ]] && est_chemin_secret "$FICHIER"; then
      bloquer "accès au fichier de secrets '$FICHIER' via $TOOL"
    fi
    ;;

  Grep)
    CHEMIN="$(jq -r '.tool_input.path // empty' <<<"$INPUT")"
    FILTRE="$(jq -r '.tool_input.glob // empty' <<<"$INPUT")"
    if [[ -n "$CHEMIN" ]] && est_chemin_secret "$CHEMIN"; then
      bloquer "recherche dans le fichier de secrets '$CHEMIN'"
    fi
    if [[ "$FILTRE" == *".env"* && "$FILTRE" != *".env.example"* ]]; then
      bloquer "recherche ciblant des fichiers .env (glob '$FILTRE')"
    fi
    ;;

  Bash)
    CMD="$(jq -r '.tool_input.command // empty' <<<"$INPUT")"
    [[ -z "$CMD" ]] && exit 0

    # 1. Fichiers de secrets cités dans la commande (.env.example et assimilés restent autorisés).
    CMD_SANS_EXEMPLES="$(sed -E 's/\.env\.(example|sample|dist)//g' <<<"$CMD")"
    if grep -Eq '(^|[^[:alnum:]_])\.env([^[:alnum:]_]|$)' <<<"$CMD_SANS_EXEMPLES"; then
      bloquer "la commande manipule un fichier .env"
    fi
    if grep -Eq '(\.ssh/|\.aws/credentials|id_rsa|id_ed25519|\.pem([^[:alnum:]]|$))' <<<"$CMD"; then
      bloquer "la commande manipule des clés ou identifiants"
    fi

    # 2. Affichage de l'environnement complet (contient souvent des jetons).
    if grep -Eq '(^|[;&|[:space:]])(printenv|export[[:space:]]+-p)([[:space:]]|$|[;&|])' <<<"$CMD" \
       || grep -Eq '(^|[;&|[:space:]])env[[:space:]]*($|[;&|>])' <<<"$CMD"; then
      bloquer "affichage des variables d'environnement"
    fi

    # 3. Suppression récursive de la racine, du home ou du projet entier.
    if grep -Eq '(^|[;&|[:space:]])rm[[:space:]][^;&|]*-[[:alpha:]]*[rR][^;&|]*[[:space:]](/|/\*|~|~/|\$HOME|\.|\./|\./\*|\.\.|\*)([[:space:]]|$|[;&|])' <<<"$CMD" \
       || grep -Eq -- '--no-preserve-root' <<<"$CMD"; then
      bloquer "suppression récursive d'un répertoire racine"
    fi

    # 4. Push forcé (--force-with-lease reste autorisé).
    if grep -Eq 'git[[:space:]]+push[^;&|]*[[:space:]](--force|-f)([[:space:]]|$|[;&|])' <<<"$CMD"; then
      bloquer "git push forcé"
    fi

    # 5. Téléchargement exécuté directement ou envoi de fichiers locaux vers l'extérieur.
    if grep -Eq '(curl|wget)[^|]*\|[[:space:]]*(sudo[[:space:]]+)?(ba|z|da)?sh([[:space:]]|$)' <<<"$CMD"; then
      bloquer "script distant exécuté sans relecture (curl | sh)"
    fi
    if grep -Eq 'curl[^;&|]*[[:space:]](-d|--data|--data-binary|--data-raw|--data-urlencode|-F|--form|-T|--upload-file)[[:space:]=]*[^[:space:]]*@' <<<"$CMD" \
       || grep -Eq 'wget[^;&|]*--post-file' <<<"$CMD"; then
      bloquer "envoi d'un fichier local vers un serveur distant (exfiltration possible)"
    fi

    # 6. Destruction de données.
    if grep -Eiq '(migrate:fresh|migrate:reset|db:wipe|drop[[:space:]]+(database|schema|table)|truncate[[:space:]]+table)' <<<"$CMD"; then
      bloquer "commande destructrice sur la base de données"
    fi

    # 7. Élévation de privilèges et permissions trop larges.
    if grep -Eq '(^|[;&|[:space:]])sudo[[:space:]]' <<<"$CMD"; then
      bloquer "élévation de privilèges (sudo)"
    fi
    if grep -Eq 'chmod[[:space:]]+(-R[[:space:]]+)?0?777' <<<"$CMD"; then
      bloquer "chmod 777"
    fi
    ;;
esac

exit 0

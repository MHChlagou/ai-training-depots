# Dépôts d'exercice de la formation

Dépôts utilisés pendant les ateliers de la formation « L'IA au service du développement avec Claude et Claude Code ».

## Installation

```bash
mkdir -p ~/formation && cd ~/formation
git clone https://github.com/MHChlagou/ai-training-depots.git
bash ai-training-depots/installer.sh ~/formation
```

`installer.sh` construit dans `~/formation` les dépôts git `comptoir` (branches `main`, `depart-seance-2` à `5`), `comptoir-vulnerable` (`main`, `sprint-12`), `comptoir-vitrine` (`main`, `depart-seance-7`) et `corpus-rag` (`main`), chacun cloné depuis un `origin` local (`~/formation/.origines/`). Un dépôt déjà présent n'est jamais écrasé. Travaillez dans ces dépôts, pas dans ce clone.

## Contenu

| Dossier | Séances | Contenu |
|---|---|---|
| `comptoir/` | 1 à 5 | Application fil rouge : API Laravel 12 (`api/`) et front Next.js (`web/`). Installation : voir `comptoir/README.md` |
| `comptoir-branches/depart-seance-N/` | 2 à 5 | Fichiers à ajouter ou remplacer dans `comptoir/` pour partir de l'état de début de la séance N (et `SUPPRESSIONS.txt` : chemins à retirer) |
| `comptoir-vulnerable/patches/sprint-12/` | 4 | Patches du sprint 12, à appliquer avec `git am` sur l'état `depart-seance-4` |
| `corpus-rag/` | 6 | Corpus documentaire fictif à indexer |
| `comptoir-vitrine/`, `comptoir-vitrine-branches/depart-seance-7/` | 7 | Site vitrine à auditer (SEO / GEO). Installation : voir `comptoir-vitrine/README.md` |

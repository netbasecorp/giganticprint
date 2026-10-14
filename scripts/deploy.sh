#!/bin/bash
# Deploy repo -> staging docroot (wp-content). Usage: deploy.sh [--dry-run]
# 1) rsync dry-run to find changed files  2) php -l on changed PHP, abort on error
# 3) tar of the target dirs -> /root/backups/giganticprints/deploys/<ts>/  4) rsync --delete-after, owner giganticprints
set -euo pipefail
. "$(dirname "$0")/_common.sh"
DRY=0; [ "${1:-}" = "--dry-run" ] && DRY=1
[ "$(id -u)" -eq 0 ] || { echo "run as root" >&2; exit 1; }
[ -e "$REPO/.git" ] && [ -d "$WPC" ] || { echo "bad REPO/WPC" >&2; exit 1; }
if [ -n "$(git -C "$REPO" status --porcelain)" ]; then echo "WARN: repo has uncommitted changes (deploying the working tree)" >&2; fi
TS=$(date -u +%Y%m%dT%H%M%SZ)
CHANGED=()
for t in $TARGETS; do
  [ -d "$REPO/$t" ] || { echo "missing $REPO/$t" >&2; exit 1; }
  mkdir -p "$WPC/$t"
  while IFS= read -r line; do
    # itemize: first field flags, rest path; keep files that will be created/updated (not deletions)
    flags="${line%% *}"; path="${line#* }"
    case "$flags" in \*deleting) continue;; esac
    case "$path" in *.php) CHANGED+=("$REPO/$t/$path");; esac
  done < <(rsync -rlcni "${RSYNC_EXCL[@]}" --delete-after "$REPO/$t/" "$WPC/$t/" | grep -v '/$' || true)
done
echo "changed PHP files: ${#CHANGED[@]}"
if [ "${#CHANGED[@]}" -gt 0 ]; then "$(dirname "$0")/lint.sh" "${CHANGED[@]}" || { echo "ABORT: lint errors, nothing deployed" >&2; exit 1; }; fi
if [ "$DRY" -eq 1 ]; then
  for t in $TARGETS; do rsync -rlcni "${RSYNC_EXCL[@]}" --delete-after "$REPO/$t/" "$WPC/$t/" | sed "s#^#[$t] #"; done
  echo "dry-run only"; exit 0
fi
BK="$BACKUP_ROOT/$TS"; mkdir -p "$BK"; chmod 700 "$BACKUP_ROOT" "$BK"
( cd "$WPC" && tar -czf "$BK/pre-deploy.tar.gz" $TARGETS )
echo "$(tar -tzf "$BK/pre-deploy.tar.gz" | wc -l) entries in $BK/pre-deploy.tar.gz"
git -C "$REPO" rev-parse HEAD > "$BK/deployed-commit.txt"
for t in $TARGETS; do
  rsync -rltog --delete-after "${RSYNC_EXCL[@]}" --chown="$OWNER" --chmod=D755,F644 "$REPO/$t/" "$WPC/$t/"
done
echo "deployed commit $(cut -c1-9 "$BK/deployed-commit.txt") ts=$TS ; rollback: $REPO/scripts/rollback.sh $TS"

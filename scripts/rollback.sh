#!/bin/bash
# Restore the target dirs from a pre-deploy backup. Usage: rollback.sh <ts>
set -euo pipefail
. "$(dirname "$0")/_common.sh"
TS="${1:-}"
[[ "$TS" =~ ^[0-9]{8}T[0-9]{6}Z$ ]] || { echo "usage: rollback.sh <ts like 20261014T130000Z>; available:" >&2; ls "$BACKUP_ROOT" 2>/dev/null >&2 || true; exit 1; }
BK="$BACKUP_ROOT/$TS"; TAR="$BK/pre-deploy.tar.gz"
[ -f "$TAR" ] && [ -d "$WPC" ] || { echo "no backup $TAR" >&2; exit 1; }
TMP=$(mktemp -d /tmp/gp-rollback.XXXXXX); [ -n "$TMP" ] && [ -d "$TMP" ] || exit 1
trap 'rm -rf "$TMP"' EXIT
tar -xzf "$TAR" -C "$TMP"
for t in $TARGETS; do
  [ -d "$TMP/$t" ] || { echo "backup lacks $t" >&2; exit 1; }
  rsync -a --delete-after "$TMP/$t/" "$WPC/$t/"
done
echo "rolled back to $TS (repo working tree unchanged; use git to align it)"

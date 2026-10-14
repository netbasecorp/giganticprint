#!/bin/bash
# Run a data script as the cPanel account with a backup staging dir; moves the JSON backup(s) to /root/backups/giganticprints/data/.
# usage (as root): scripts/data/run.sh <script.php> [--dry-run | --apply | --restore=/root/backups/giganticprints/data/<file>.json] [...]
set -euo pipefail
[ "$(id -u)" -eq 0 ] || { echo "run as root" >&2; exit 1; }
HERE="$(cd "$(dirname "$0")" && pwd)"
S="${1:-}"; shift || true
[ -n "$S" ] || { echo "usage: $0 <script.php> [args]" >&2; exit 2; }
[ -f "$HERE/$S" ] || [ -f "$S" ] || { echo "no such script: $S" >&2; exit 2; }
[ -f "$HERE/$S" ] && S="$HERE/$S"
PHP=/opt/cpanel/ea-php82/root/usr/bin/php
STAGE=$(mktemp -d /tmp/gp-data.XXXXXX); chmod 777 "$STAGE"
ARGS=()
for a in "$@"; do
  case "$a" in
    --restore=*) f="${a#--restore=}"; [ -f "$f" ] || { echo "no such backup $f" >&2; exit 2; }
                 cp "$f" "$STAGE/restore.json"; chmod 644 "$STAGE/restore.json"; ARGS+=("--restore=$STAGE/restore.json");;
    *) ARGS+=("$a");;
  esac
done
cp "$S" "$STAGE/script.php"; cp "$HERE/_lib.php" "$STAGE/_lib.php"; chmod 644 "$STAGE"/*.php
set +e
( cd /tmp && GP_STAGE="$STAGE" runuser -u giganticprints -- "$PHP" -d display_errors=1 -d log_errors=0 -d error_reporting=22527 "$STAGE/script.php" "${ARGS[@]}" 2>&1 | grep -v -e 'Imagick was compiled' -e 'REMOTE_ADDR' )
RC=${PIPESTATUS[0]}
set -e
mkdir -p /root/backups/giganticprints/data; chmod 700 /root/backups/giganticprints /root/backups/giganticprints/data
for j in "$STAGE"/backup-*.json; do [ -f "$j" ] || continue; mv "$j" /root/backups/giganticprints/data/; echo "backup: /root/backups/giganticprints/data/$(basename "$j")"; done
rm -rf "$STAGE"
exit $RC

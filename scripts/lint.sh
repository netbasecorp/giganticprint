#!/bin/bash
# php -l (ea-php82) on every PHP file in the repo targets, or on the files given as args.
set -uo pipefail
. "$(dirname "$0")/_common.sh"
[ -x "$PHP" ] || { echo "php not found: $PHP" >&2; exit 2; }
if [ "$#" -gt 0 ]; then FILES=("$@"); else
  mapfile -t FILES < <(cd "$REPO" && for t in $TARGETS; do find "$t" -type f -name '*.php'; done | sort)
fi
bad=0; n=0
for f in "${FILES[@]}"; do
  [ -f "$f" ] || f="$REPO/$f"
  n=$((n+1))
  out=$("$PHP" -d log_errors=0 -d display_errors=1 -l "$f" 2>&1) || { echo "LINT FAIL: $f"; echo "$out"; bad=$((bad+1)); }
done
echo "lint: $n files, $bad failed"
[ "$bad" -eq 0 ]

#!/bin/bash
# B2: PHP limits for the Gigantic Prints cPanel account (ea-php82, PHP-FPM) so artwork up to 100 MB can be uploaded.
#   scripts/data/b2-php-limits.sh [--dry-run|--apply|--restore=<saved content file>]
# Uses cPanel's own MultiPHP INI API (writes the managed .user.ini correctly). Saves the previous content first.
# Cloudflare proxies this host and caps request bodies at 100 MB, so post_max_size stays just above upload_max_filesize (bigger files go through the chunked path, 8 MB per request). memory_limit 1024M: the core uploader holds a file about 2.8 times in memory (200 MB file ~ 560 MB).
set -euo pipefail
[ "$(id -u)" -eq 0 ] || { echo "run as root" >&2; exit 1; }
USER_=giganticprints; VHOST=giganticprints.printcart.com
BK=/root/backups/giganticprints/data; mkdir -p "$BK"; chmod 700 /root/backups/giganticprints "$BK"
MODE="${1:---dry-run}"
declare -A WANT=( [upload_max_filesize]=100M [post_max_size]=105M [max_execution_time]=300 [max_input_time]=300 [memory_limit]=1024M )
cur() { uapi --user=$USER_ LangPHP php_ini_get_user_basic_directives type=vhost vhost=$VHOST --output=json | python3 -c '
import json,sys
d=json.load(sys.stdin)["result"]["data"]["directives"]
for x in d: print(x["key"]+"="+str(x["value"]))'; }
echo "== b2-php-limits mode=$MODE"
echo "-- current:"; cur | grep -E '^(upload_max_filesize|post_max_size|max_execution_time|max_input_time|memory_limit)=' || true
case "$MODE" in
  --dry-run) for k in "${!WANT[@]}"; do echo "would set $k=${WANT[$k]}"; done; exit 0;;
  --apply)
    TS=$(date -u +%Y%m%dT%H%M%SZ); SAVE="$BK/php-limits-before-$TS.txt"
    cur > "$SAVE"; chmod 600 "$SAVE"; echo "saved previous values: $SAVE"
    args=(); i=1
    for k in "${!WANT[@]}"; do args+=("directive-$i=$k:${WANT[$k]}"); i=$((i+1)); done
    uapi --user=$USER_ LangPHP php_ini_set_user_basic_directives type=vhost vhost=$VHOST "${args[@]}" | grep -E 'status|errors' | head -3
    ;;
  --restore=*)
    F="${MODE#--restore=}"; [ -f "$F" ] || { echo "no such file" >&2; exit 2; }
    args=(); i=1
    while IFS='=' read -r k v; do case "$k" in upload_max_filesize|post_max_size|max_execution_time|max_input_time|memory_limit) args+=("directive-$i=$k:$v"); i=$((i+1));; esac; done < "$F"
    uapi --user=$USER_ LangPHP php_ini_set_user_basic_directives type=vhost vhost=$VHOST "${args[@]}" | grep -E 'status|errors' | head -3
    ;;
  *) echo "usage: $0 [--dry-run|--apply|--restore=file]" >&2; exit 2;;
esac
echo "-- now:"; cur | grep -E '^(upload_max_filesize|post_max_size|max_execution_time|max_input_time|memory_limit)='

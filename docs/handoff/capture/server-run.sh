#!/bin/bash
# Runs the screenshot capture on the staging host (as root), inside the existing long-lived e2e container `gp-e2e`.
# Internal tool. Usage (on the host):  server-run.sh [capture.mjs args]      e.g.  server-run.sh --batch now
#   input : /srv/gp-dev/handoff/capture/   (shots.json, capture.mjs, make-fixtures.mjs, admin-login.php)  <- put there by sync-and-run.sh
#   output: /srv/gp-dev/handoff/images/    (PNG + _capture-report.json)
# Never creates a container: if gp-e2e is not running, start it once with /srv/gp-dev/e2e/go.sh (see README).
set -uo pipefail
H=/srv/gp-dev/handoff
PHP=/opt/cpanel/ea-php82/root/usr/bin/php
docker ps --format '{{.Names}}' | grep -qx gp-e2e || { echo "gp-e2e is not running; start it with /srv/gp-dev/e2e/go.sh --list (creates the long-lived container)" >&2; exit 2; }
mkdir -p "$H/run"; rm -rf "$H/images"; mkdir -p "$H/images"   # host copy is a scratch area; the laptop keeps the merged set

# 1) fresh login cookies (session tokens only, no passwords): test users -> e2e/auth, admin -> container /tmp/handoff
for u in gp_test_b2c gp_test_b2b gp_test_reseller; do
  /srv/gp-dev/scripts/test-users.sh login "$u" > "/srv/gp-dev/e2e/auth/$u.json.tmp" 2>/dev/null && mv "/srv/gp-dev/e2e/auth/$u.json.tmp" "/srv/gp-dev/e2e/auth/$u.json" || rm -f "/srv/gp-dev/e2e/auth/$u.json.tmp"
done
chmod 600 /srv/gp-dev/e2e/auth/*.json 2>/dev/null
( cd /tmp && runuser -u giganticprints -- "$PHP" -d display_errors=0 -d log_errors=0 "$H/capture/admin-login.php" login "${GP_ADMIN_LOGIN:-admin}" > "$H/run/admin.json" 2>/dev/null )
[ -s "$H/run/admin.json" ] && chmod 600 "$H/run/admin.json" || echo "warning: no admin cookies (admin shots will fail)" >&2

# 2) copy the capture tool into the container (fresh copy every run)
docker exec gp-e2e rm -rf /tmp/handoff/capture /tmp/handoff/images
docker exec gp-e2e mkdir -p /tmp/handoff
docker cp "$H/capture" gp-e2e:/tmp/handoff/capture
docker cp "$H/run/admin.json" gp-e2e:/tmp/handoff/admin.json
docker exec gp-e2e mkdir -p /tmp/handoff/images

# 3) fixtures (sample artwork) once, then capture
docker exec -w /tmp/handoff/capture gp-e2e bash -c '[ -f fixtures/sample-banner-art.png ] || node make-fixtures.mjs'
docker exec -w /tmp/handoff/capture gp-e2e node capture.mjs --out /tmp/handoff/images "$@"
rc=$?

# 4) bring the images back
rm -rf "$H/images.new"; docker cp gp-e2e:/tmp/handoff/images "$H/images.new" && { mkdir -p "$H/images"; cp -a "$H/images.new/." "$H/images/"; rm -rf "$H/images.new"; }
docker exec gp-e2e rm -f /tmp/handoff/admin.json
chmod -R a+rX "$H/images"
exit $rc

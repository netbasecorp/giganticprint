#!/bin/bash
# Runs the screenshot capture on the staging host (as root), inside the long-lived e2e container (default name gp-e2e4, override with GP_CONTAINER).
# Internal tool. Usage (on the host):  server-run.sh [capture.mjs args]      e.g.  server-run.sh --batch now
#   input : /srv/gp-dev/handoff/capture/   (shots.json, capture.mjs, make-fixtures.mjs, admin-login.php)  <- put there by sync-and-run.sh
#   output: /srv/gp-dev/handoff/images/    (PNG + _capture-report.json)
# Never creates a container and never uses --rm. Create it once by hand (see README), stop it when done (docker stop), never remove it.
set -uo pipefail
GP_CONTAINER="${GP_CONTAINER:-gp-e2e4}"
H=/srv/gp-dev/handoff
PHP=/opt/cpanel/ea-php82/root/usr/bin/php
docker ps --format '{{.Names}}' | grep -qx ${GP_CONTAINER} || { echo "${GP_CONTAINER} is not running; create it once: docker run -d --name ${GP_CONTAINER} --ipc=host --memory 2g --cpus 1.5 -v /srv/gp-dev/e2e:/work -w /work mcr.microsoft.com/playwright:v1.48.0-jammy sleep infinity" >&2; exit 2; }
mkdir -p "$H/run"; rm -rf "$H/images"; mkdir -p "$H/images"   # host copy is a scratch area; the laptop keeps the merged set

# 1) fresh login cookies (session tokens only, no passwords): test users -> e2e/auth, admin -> container /tmp/handoff
for u in gp_test_b2c gp_test_b2b gp_test_reseller gp_test_staff; do
  /srv/gp-dev/scripts/test-users.sh login "$u" > "/srv/gp-dev/e2e/auth/$u.json.tmp" 2>/dev/null && mv "/srv/gp-dev/e2e/auth/$u.json.tmp" "/srv/gp-dev/e2e/auth/$u.json" || rm -f "/srv/gp-dev/e2e/auth/$u.json.tmp"
done
chmod 600 /srv/gp-dev/e2e/auth/*.json 2>/dev/null
( cd /tmp && runuser -u giganticprints -- "$PHP" -d display_errors=0 -d log_errors=0 "$H/capture/admin-login.php" login "${GP_ADMIN_LOGIN:-admin}" > "$H/run/admin.json" 2>/dev/null )
( cd /tmp && runuser -u giganticprints -- "$PHP" -d display_errors=0 -d log_errors=0 "$H/capture/demo-urls.php" > "$H/run/urls.json" 2>/dev/null )
[ -s "$H/run/admin.json" ] && chmod 600 "$H/run/admin.json" || echo "warning: no admin cookies (admin shots will fail)" >&2

# 2) copy the capture tool into the container (fresh copy every run)
docker exec ${GP_CONTAINER} rm -rf /tmp/handoff/capture /tmp/handoff/images
docker exec ${GP_CONTAINER} mkdir -p /tmp/handoff
docker cp "$H/capture" ${GP_CONTAINER}:/tmp/handoff/capture
docker cp "$H/run/admin.json" ${GP_CONTAINER}:/tmp/handoff/admin.json
[ -s "$H/run/urls.json" ] && docker cp "$H/run/urls.json" ${GP_CONTAINER}:/tmp/handoff/urls.json
docker exec ${GP_CONTAINER} mkdir -p /tmp/handoff/images

# 3) fixtures (sample artwork) once, then capture
docker exec -w /tmp/handoff/capture ${GP_CONTAINER} bash -c '[ -f fixtures/sample-banner-art.png ] || node make-fixtures.mjs'
docker exec -w /tmp/handoff/capture ${GP_CONTAINER} node capture.mjs --out /tmp/handoff/images "$@"
rc=$?

# 4) bring the images back
rm -rf "$H/images.new"; docker cp ${GP_CONTAINER}:/tmp/handoff/images "$H/images.new" && { mkdir -p "$H/images"; cp -a "$H/images.new/." "$H/images/"; rm -rf "$H/images.new"; }
docker exec ${GP_CONTAINER} rm -f /tmp/handoff/admin.json /tmp/handoff/urls.json; rm -f "$H/run/admin.json" "$H/run/urls.json"
chmod -R a+rX "$H/images"
exit $rc

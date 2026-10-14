#!/bin/bash
# /srv/gp-dev/e2e/go.sh [playwright args]  - refresh auth cookies for test users, run Playwright in the long-lived gp-e2e container.
set -uo pipefail
cd /srv/gp-dev/e2e || exit 1
mkdir -p auth shots
if ! docker ps --format '{{.Names}}' | grep -qx gp-e2e; then
  docker rm -f gp-e2e >/dev/null 2>&1 || true
  docker run -d --name gp-e2e --ipc=host --memory 2g --cpus 1.5 -v /srv/gp-dev/e2e:/work -w /work mcr.microsoft.com/playwright:v1.48.0-jammy sleep infinity >/dev/null
fi
if [ -f /root/.secrets/gp-test-users.txt ]; then
  for u in gp_test_b2c gp_test_b2b gp_test_reseller gp_test_staff; do
    /srv/gp-dev/scripts/test-users.sh login "$u" > "auth/$u.json.tmp" 2>/dev/null && mv "auth/$u.json.tmp" "auth/$u.json" || rm -f "auth/$u.json.tmp"
  done
  chmod 600 auth/*.json 2>/dev/null || true
fi
docker exec -e BASE_URL="${BASE_URL:-}" -e SHOT_DIR="${SHOT_DIR:-shots}" gp-e2e bash -c '[ -d node_modules/@playwright/test ] || npm install --no-audit --no-fund --silent; [ -n "$BASE_URL" ] || unset BASE_URL; npx playwright test "$@"' -- "$@"
rc=$?
chmod -R a+rX shots 2>/dev/null
exit $rc

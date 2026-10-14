#!/bin/bash
# Laptop side: copies this capture folder to the staging host, runs server-run.sh there and pulls the PNGs back into ../images.
# Needs an SSH command for the host in $GP_SSH (key + proxy options included), e.g. GP_SSH='ssh -i KEY -o ProxyCommand=... root@HOST'.
# Usage: GP_SSH='...' ./sync-and-run.sh [capture.mjs args]      e.g.  --batch now   |   --only 07,12   |   --batch b4
set -uo pipefail
: "${GP_SSH:?set GP_SSH to the ssh command for the staging host}"
here="$(cd "$(dirname "$0")" && pwd)"
$GP_SSH 'rm -rf /srv/gp-dev/handoff/capture && mkdir -p /srv/gp-dev/handoff/capture' || exit 1
tar -C "$here" --exclude=fixtures --exclude=node_modules -cf - . | $GP_SSH 'tar -C /srv/gp-dev/handoff/capture -xf -' || exit 1
$GP_SSH "chmod +x /srv/gp-dev/handoff/capture/server-run.sh; /srv/gp-dev/handoff/capture/server-run.sh $*"
rc=$?
mkdir -p "$here/../images"
$GP_SSH 'tar -C /srv/gp-dev/handoff/images -cf - .' | tar -C "$here/../images" -xf -
echo "images in $(cd "$here/../images" && pwd) (exit $rc)"
exit $rc

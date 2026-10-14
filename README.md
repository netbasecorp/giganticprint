# giganticprint staging code (srv002)

Custom code of https://giganticprints.printcart.com (cPanel user `giganticprints`, docroot
`/home/giganticprints/public_html`, PHP 8.2). Layout mirrors `wp-content/`:

- `plugins/printcart-b2bstore`, `plugins/signedge-designer-ui`, `plugins/cmsmart-ui-override`
- `themes/storecmsmart`

Baseline = live staging state on 2026-10-14 (branch `dev/staging-baseline`).

## Scripts (run as root on srv002)
- `scripts/lint.sh [files]` - `php -l` (ea-php82) over all PHP, or given files.
- `scripts/deploy.sh [--dry-run]` - lint changed PHP, tar the target dirs to
  `/root/backups/giganticprints/deploys/<ts>/`, then rsync `--delete-after` into wp-content (owner `giganticprints`).
- `scripts/rollback.sh <ts>` - restore the target dirs from that backup.

Staging is `noindex` (`blog_public=0`); flip back to 1 at go-live. Production change rules apply: ledger entry first.

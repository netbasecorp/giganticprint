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

## Data scripts (`scripts/data/`, run as root on srv002 through `scripts/data/run.sh <script> [--dry-run|--apply|--restore=<backup.json>]`)
Idempotent, dry-run by default, every touched option/meta/row/term is saved as JSON in `/root/backups/giganticprints/data/` first.
- `b1-pricing-catalog.php` (13oz tier, HDPE range gap, acrylic default, US price format, vinyl unit, categories, tiers),
  `b2-upload-settings.php` (+ `b2-php-limits.sh`: PHP limits through the cPanel API), `b3-woo-settings.php` (guest checkout off,
  registration on, store status vocabulary), `b3-audit-cleanup.php` (lists then deletes test leftovers of the day).
- `price-check.php`: runs the real NBDesigner server price calculation for the customer's price sheet (0 strict failures expected).
- `b3-security-check.php`, `b3-pricing-check.php`, `b3-fixture.php create|status|selftest-invite|clear-ratelimit|cleanup`: server-side checks and the
  fixture used by the e2e suite.

## Tests (`tests/e2e`, Playwright, run on srv002 in the long-lived container `gp-e2e` with `/srv/gp-dev/e2e/go.sh tests/<spec>`)
`b1.spec.js` pricing and catalog, `b2.spec.js` upload to preview, `b3.spec.js` B2B and security. Logged-in tests use
`scripts/test-users.sh` (users `gp_test_b2c`, `gp_test_b2b`, `gp_test_reseller`) with auth cookies, no typed passwords. Screenshots go to `shots/bN/`.

## What the plugins add (upgrade of 2026-10-14)
- `cmsmart-ui-override`: `GP_Linear_Pricing` (pole pocket and rope per linear foot from the chosen size, server + browser), `GP_Hardening` (login rate limit, no user enumeration).
- `signedge-designer-ui`: auto-placement of uploaded art, art bar (stretch / fit / center, size edit), progress, low-resolution warning, empty-print-area guard,
  loading state, `SG_Upload` (TIFF/EPS conversion, chunked upload up to 200 MB), single B2B discount on raw numeric prices. Assets are cache-busted by file mtime.
- `printcart-b2bstore`: one store status vocabulary (`active`), cookie-bound invitations, POST + nonce quotes, discount applied once in the cart.
- theme `storecmsmart`: real "From" price, category bar in Signs365 order, old category redirects, "Coming soon" for empty categories.

## B4 additions (2026-10-15)
- Phone flow: `signedge-designer-ui/assets/js/mobile.js` + `mobile.css` build `#sg-m` (upload, preview, size, options, price, Add to Cart) on screens up to 768 px; the core editor stays reachable through "Advanced editor". Theme CSS removes horizontal scroll on every page.
- `printcart-b2bstore`: `PCS_Reorder` (one-click reorder with options and a copy of the design folder), `PCS_Company_Images` (private Image Zone, files under `uploads/gp-imagezone/<store>/`, served only through `admin-ajax gp_iz_file` after a membership check), `PCS_Company_Templates` (company templates: save in the designer, portal list/rename/clone/delete, `?gp_tpl=<id>` starts an order), `PCS_Private_Products` (assign products to a company on the company admin screen; hidden from everybody else, 404 on the URL and the designer URL). New tables are created lazily: `pcs_company_images`, `pcs_company_templates`, `pcs_store_products`; the per-company setting "Hide public templates" lives in the option `pcs_store_settings`.
- Tests: `tests/e2e/tests/b4.spec.js`; fixtures `scripts/data/b3-fixture.php create|assign-private|cleanup`, `b4-reorder-fixture.php`; checks `b4-*-check.php`.

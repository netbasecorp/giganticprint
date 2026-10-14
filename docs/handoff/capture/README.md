# Handoff screenshot capture (internal, not for the customer)

Takes the screenshots for `docs/handoff/Gigantic-Prints-Handoff-Guide.md` with Playwright. Every shot is described in
`shots.json`; `capture.mjs` runs them, draws a red outline (and a numbered marker) on the element to look at, and writes
`../images/<id>.png` (`id` = `NN-slug`, the same name the guide uses).

Only the guide, the `images/` folder and the PDF go to the customer. This folder (scripts, host names, test users) stays internal.

## What is in here

| File | Purpose |
|---|---|
| `shots.json` | the capture list: one entry per screenshot |
| `capture.mjs` | the runner (Node ESM + Playwright) |
| `make-fixtures.mjs` | draws the sample artwork used in the screenshots (`GRAND OPENING` banner, 150 dpi; and a 72 dpi one for the low-resolution warning) |
| `admin-login.php` | prints a Playwright login cookie for an administrator (no password involved) |
| `server-run.sh` | runs everything on the staging host inside the existing container `gp-e2e` |
| `check-guide.py` | checks that every screenshot in the guide has a shot (and the reverse) and writes `SHOT-LIST.md` (the capture list with captured / pending status) |
| `sync-and-run.sh` | from the laptop: copy this folder to the host, run `server-run.sh`, pull the PNGs back into `../images` |

## Run it

Prerequisites on the staging host (already there): the long-lived container `gp-e2e` (Playwright 1.48 image, `/srv/gp-dev/e2e` mounted as
`/work`, `node_modules` installed) and `/srv/gp-dev/scripts/test-users.sh` with the three test users
(`gp_test_b2c`, `gp_test_b2b`, `gp_test_reseller`). Do **not** create `--rm` containers on that host (they get stuck); if `gp-e2e` is
missing, start it once with `/srv/gp-dev/e2e/go.sh --list`.

```bash
# laptop: GP_SSH is an executable that runs its arguments on the host (key + proxy options inside), e.g. a 2-line wrapper script
export GP_SSH=/path/to/ssh-to-host.sh
./sync-and-run.sh --batch now            # shots of areas that are final today
./sync-and-run.sh --batch b4             # final run, after batch B4 is deployed (see "Final run" below)
./sync-and-run.sh --only 07,11           # a few shots by id prefix
```

Or directly on the host (as root), once the folder is in `/srv/gp-dev/handoff/capture/`:
`/srv/gp-dev/handoff/capture/server-run.sh --batch now`. Output lands in `/srv/gp-dev/handoff/images/`.

`capture.mjs` options: `--shots FILE`, `--out DIR`, `--batch now|b4|all`, `--only id[,id]` (prefix match), `--base URL`, `--fixtures DIR`,
`--auth-dir DIR`, `--admin-cookies FILE`. `_capture-report.json` in the output folder lists ok/failed per shot; a failed shot leaves
`_failed-<id>.png` so you can see where it stopped. The exit code is 1 if any shot failed. The browser is closed in `finally`.

What the run does on the live site: it logs in with session cookies (test users, and the administrator), opens pages, uploads the sample art in
the designer and, for shot `30-cart`, adds one item to the cart of the test user and removes it again afterwards. It changes no settings and places no
order. Design folders for the uploaded art may remain in the uploads directory (same as any test run); the audit cleanup script removes them.

## shots.json

Top level: `baseUrl`, `shots[]`. Per shot:

| Field | Meaning |
|---|---|
| `id` | `NN-slug`, becomes `images/NN-slug.png` |
| `url` | page to open (`${BASE}` is replaced) |
| `viewport` | `desktop` (1440 x 900) or `mobile` (390 x 844, 2x, touch) |
| `auth` | `none`, `b2c`, `b2b`, `reseller` (test users) or `admin` |
| `batch` | `now` (final today) or `b4` (areas batch B4 changes: capture in the final run) |
| `needs` | informational: `fixture` (run `scripts/data/run.sh b3-fixture.php create` first and `cleanup` after), `order` (one test order must exist), `no-company` (capture before the fixture exists: the b2c test user must not be a company member, otherwise My Account redirects to the company portal) or `clean-data` (lists must hold only the intended sample rows, no leftovers of other test runs) |
| `designer` | `true` = wait until the designer and its options are loaded and close the guided tour |
| `waitMs` / `waitFor` / `waitAfter` | extra waiting (ms) / selector to wait for / wait after actions |
| `actions` | list of steps, see below |
| `highlight` | selector, list of selectors, or list of `{ "sel": "...", "label": "1", "pad": 4 }` (red outline + numbered marker) |
| `clip` | selector: screenshot only that element |
| `fullPage` | full-page screenshot |
| `hide` / `css` | selectors to hide / extra CSS (e.g. to hide an e-mail address) |
| `cleanup` | actions run after the screenshot to undo a change (never fails the shot) |
| `caption` | text of the screenshot as used in the guide (documentation only) |

Actions (one verb per object): `{"goto": url}`, `{"click": sel, "force": true}`, `{"clickText": "text"}`, `{"hover": sel}`,
`{"fill": sel, "value": "..", "enter": true}`, `{"press": "Enter"}`, `{"select": sel, "value": ".."}`, `{"check": sel}`,
`{"upload": sel, "file": "sample-banner-art.png"}`, `{"waitFor": sel, "state": "visible", "timeout": 30000}`, `{"wait": ms}`,
`{"scrollTo": sel}`, `{"scrollBy": px}`, `{"evaluate": "js"}`, and the designer helpers `{"designerTab": "PHOTOS|PRINTING|TEXT|LAYERS"}`,
`{"designerUpload": "file"}` (choose the file and wait until the art sits on the canvas), `{"setSize": [w, h]}`, `{"pickOption": "text"}`.

## Final run (after batch B4)

1. Deploy of batch B4 finished and verified (do not capture while another deploy is running).
2. First run the retail-customer shots (`needs: no-company`: `--only 40,42,43,44,45`), then create the temporary company data: `scripts/data/run.sh b3-fixture.php create` (company "GP Test Company", one member, one sent quote).
   Place one test order with a print file as the b2c test user (for shots `41`, `90`, `91`) and cancel/delete it afterwards.
3. Check the `b4` shots against the new screens: Image Zone, templates and private products have no final selectors yet; adjust their `url`,
   `actions` and `highlight` in `shots.json` first (they are drafts).
4. `./sync-and-run.sh --batch b4`, look at every PNG, re-run single shots with `--only`.
5. Remove the fixture (`b3-fixture.php cleanup`), the test order and the test users when the guide is final.
6. Update the guide text where the real wording differs from the draft, then build the PDF (`../build-pdf.md`).

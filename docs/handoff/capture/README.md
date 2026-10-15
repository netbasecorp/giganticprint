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
| `server-run.sh` | runs everything on the staging host inside the long-lived container `gp-e2e2` |
| `check-guide.py` | checks that every screenshot in the guide has a shot (and the reverse) and writes `SHOT-LIST.md` (the capture list with captured / pending status) |
| `sync-and-run.sh` | from the laptop: copy this folder to the host, run `server-run.sh`, pull the PNGs back into `../images` |

## Run it

Prerequisites on the staging host: one long-lived container `gp-e2e2` (Playwright 1.48 image, `/srv/gp-dev/e2e` mounted as `/work`, `node_modules`
installed there) and `/srv/gp-dev/scripts/test-users.sh` with the demo users (`gp_test_b2c`, `gp_test_b2b`, `gp_test_staff`, `gp_test_reseller`).
Do **not** use `--rm` containers on that host (they get stuck in "Removal In Progress"). Create the container once with
`docker run -d --name gp-e2e2 --ipc=host --memory 2g --cpus 1.5 -v /srv/gp-dev/e2e:/work -w /work mcr.microsoft.com/playwright:v1.48.0-jammy sleep infinity`
(override the name with `GP_CONTAINER`), and `docker stop` it when you are done; do not remove it.

```bash
# laptop: GP_SSH is an executable that runs its arguments on the host (key + proxy options inside), e.g. a 2-line wrapper script
export GP_SSH=/path/to/ssh-to-host.sh
./sync-and-run.sh                        # every shot
./sync-and-run.sh --only 07,11           # a few shots by id prefix
```

Or directly on the host (as root), once the folder is in `/srv/gp-dev/handoff/capture/`:
`/srv/gp-dev/handoff/capture/server-run.sh`. Output lands in `/srv/gp-dev/handoff/images/`.

`capture.mjs` options: `--shots FILE`, `--out DIR`, `--batch final|all`, `--only id[,id]` (prefix match), `--base URL`, `--fixtures DIR`,
`--auth-dir DIR`, `--admin-cookies FILE`. `_capture-report.json` in the output folder lists ok/failed per shot; a failed shot leaves
`_failed-<id>.png` so you can see where it stopped. The exit code is 1 if any shot failed. The browser is closed in `finally`.

What the run does on the live site: it logs in with session cookies (test users, and the administrator), opens pages, uploads the sample art in
the designer and, for the cart and checkout shots, adds one item to the cart of a demo user and empties the cart again afterwards. It changes no settings and places no
order. Design folders for the uploaded art may remain in the uploads directory (same as any test run); the audit cleanup script removes them.

## shots.json

Top level: `baseUrl`, `shots[]`. Per shot:

| Field | Meaning |
|---|---|
| `id` | `NN-slug`, becomes `images/NN-slug.png` |
| `url` | page to open (`${BASE}` is replaced) |
| `viewport` | `desktop` (1440 x 900) or `mobile` (390 x 844, 2x, touch) |
| `auth` | `none`, `b2c`, `b2b`, `staff`, `reseller` (demo users) or `admin` |
| `batch` | `final` for every shot (the field is kept so a run can be split with `--batch`) |
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

## Demo data the shots rely on

The shots use the clean demo state of the store (script `scripts/data/b5-demo-state.php`): `gp_test_b2c` (retail customer, no company), `gp_test_b2b`
(owner of **Demo Signs Co**: one Image Zone image, template id 22 for product 1168, product 1263 private to the company), `gp_test_staff`
(staff member of Demo Signs Co), `gp_test_reseller` (owner of **Demo Reseller Co**), orders #2027 to #2030 (one Completed per user), empty carts.
`demo-urls.php` reads the existing orders (read-only) so that the Order received page and the order screens can be opened without placing an order.
Shots that add an item to the cart (`30`, `31`, `33`, `65`) empty the cart again (`cleanup`). Nothing else is written. Design folders for the uploaded art
may remain in the uploads directory; `b5-demo-state.php sweep` lists and removes them.

## Re-running everything

1. Check that the demo state is still clean (`b5-demo-state.php status`), and that no other deploy is running.
2. Create the container once if it does not exist (name `gp-e2e2`, `docker run -d ... sleep infinity`, never `--rm`).
3. `./sync-and-run.sh` (all shots, about 40 minutes) or with `--only id,id` for single shots; look at every PNG.
4. `python check-guide.py` (guide and capture list agree), then build the PDF (`../build-pdf.md`).
5. `docker stop` the container when done (do not remove it).

# Building the PDF of the handoff guide

The guide is written in Markdown (`Gigantic-Prints-Handoff-Guide.md`) and shipped as a PDF next to it. The PDF is produced
in two steps: Markdown to one self-contained HTML file (screenshots embedded), then Chromium prints that HTML to A4 with
`page.pdf()`. Everything is in `build_pdf.py` in this folder.

## One-time setup (laptop or any machine with Python 3.10+)

```bash
pip install markdown playwright
python -m playwright install chromium
```

## Build

```bash
cd docs/handoff
python build_pdf.py                 # -> dist/Gigantic-Prints-Handoff-Guide.html and .pdf
python build_pdf.py --no-pdf        # HTML only (quick preview in a browser)
python build_pdf.py --out /tmp/gp   # other output folder
```

What it does:

- Reads `Gigantic-Prints-Handoff-Guide.md`; HTML comments (`<!-- ... -->`, used for draft notes) are removed first, so they never reach the customer.
- Converts with the Python `markdown` package (`tables`, `toc`, `sane_lists`), adds a cover-style title, a table of contents (levels 2-3) and print CSS (A4, page numbers in the footer).
- Images `![caption](images/NN-slug.png)` are read from `docs/handoff/images/` and embedded as data URIs with the caption below. A screenshot that has not been captured yet is shown as a grey "Screenshot to be added" box and listed in the console, so a draft PDF can be built at any time.
- Chromium is started and closed inside `try/finally`, so no browser process is left behind.

## Release checklist for the PDF

1. All screenshots captured (`capture/README.md`, "Final run"); `python build_pdf.py` prints `0 screenshot(s) not captured yet`.
2. Open the PDF and check: headings not orphaned at the bottom of a page, tables not cut, each screenshot readable (zoom 100%), red outlines on the right element.
3. Search the PDF for words that must not appear: internal server names, IP addresses, `Docker`, `cPanel`, `Cloudflare`, `test user`, `gp_test`, e-mail addresses of Printcart staff.
4. Send the PDF, or the Markdown plus the `images/` folder; never the `capture/` folder. The PDF of release 1.0 is committed as `dist/Gigantic-Prints-Handoff-Guide.pdf` (about 16 MB); the self-contained HTML next to it (about 21 MB) is not committed, rebuild it with the command above.

## Alternatives

- Print the HTML from any browser (Ctrl+P, A4, background graphics on). Result is similar, headers and footers differ.
- On the staging host the same script can run inside the e2e container (it has Chromium); `markdown` has to be installed there with `pip`.
  Not needed for normal use.

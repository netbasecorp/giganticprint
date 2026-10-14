#!/usr/bin/env python3
"""Render docs/handoff/Gigantic-Prints-Handoff-Guide.md to HTML and PDF (Markdown -> HTML -> Chromium page.pdf).

    python build_pdf.py [--md Gigantic-Prints-Handoff-Guide.md] [--out dist] [--no-pdf]

Needs: pip install markdown playwright ; python -m playwright install chromium
Images are read from ./images (paths in the markdown are relative to this folder) and embedded in the HTML as data URIs,
so dist/*.html is a single self-contained file. A screenshot that is not captured yet is replaced by a grey placeholder box.
"""
import argparse
import base64
import html
import mimetypes
import re
from pathlib import Path

import markdown

HERE = Path(__file__).resolve().parent

CSS = """
@page { size: A4; margin: 18mm 15mm 18mm 15mm; }
* { box-sizing: border-box; }
body { font: 10.5pt/1.5 -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1b1f24; margin: 0; }
h1 { font-size: 24pt; margin: 0 0 4mm; color: #b3121f; }
h2 { font-size: 16pt; margin: 9mm 0 3mm; padding-bottom: 1.5mm; border-bottom: 2px solid #b3121f; page-break-after: avoid; }
h3 { font-size: 12.5pt; margin: 6mm 0 2mm; page-break-after: avoid; }
h4 { font-size: 11pt; margin: 4mm 0 1mm; page-break-after: avoid; }
p, li { orphans: 3; widows: 3; }
table { border-collapse: collapse; width: 100%; margin: 3mm 0; font-size: 9.5pt; page-break-inside: auto; }
tr { page-break-inside: avoid; }
th, td { border: 1px solid #cfd4da; padding: 1.6mm 2.4mm; vertical-align: top; text-align: left; }
th { background: #f1f3f5; }
code, kbd { font-family: Consolas, "Courier New", monospace; background: #f1f3f5; padding: 0 1.2mm; border-radius: 2px; font-size: 9.5pt; }
blockquote { margin: 3mm 0; padding: 2mm 4mm; background: #fff6e5; border-left: 4px solid #f0a000; }
blockquote p { margin: 1mm 0; }
figure { margin: 3mm 0 5mm; page-break-inside: avoid; text-align: center; }
figure img { max-width: 100%; border: 1px solid #cfd4da; border-radius: 3px; }
figcaption { font-size: 9pt; color: #5b636b; margin-top: 1.2mm; }
.missing { border: 2px dashed #b8bec5; background: #f7f8f9; color: #6b737b; padding: 14mm 4mm; font-size: 9.5pt; }
.cover { page-break-after: always; padding-top: 40mm; }
.cover .sub { font-size: 14pt; color: #444; margin-top: 3mm; }
nav.toc { page-break-after: always; }
nav.toc ul { list-style: none; padding-left: 0; } nav.toc ul ul { padding-left: 6mm; }
a { color: #0b57d0; text-decoration: none; }
"""


def embed(match, base: Path, missing: list):
    alt, src = match.group(1), match.group(2)
    p = (base / src).resolve()
    if p.is_file():
        mime = mimetypes.guess_type(p.name)[0] or "image/png"
        data = base64.b64encode(p.read_bytes()).decode()
        return f'<figure><img alt="{html.escape(alt)}" src="data:{mime};base64,{data}"><figcaption>{html.escape(alt)}</figcaption></figure>'
    missing.append(src)
    return f'<figure><div class="missing">Screenshot to be added: {html.escape(src)}</div><figcaption>{html.escape(alt)}</figcaption></figure>'


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--md", default=str(HERE / "Gigantic-Prints-Handoff-Guide.md"))
    ap.add_argument("--out", default=str(HERE / "dist"))
    ap.add_argument("--no-pdf", action="store_true")
    a = ap.parse_args()

    md_path = Path(a.md)
    text = md_path.read_text(encoding="utf-8")
    text = re.sub(r"<!--.*?-->", "", text, flags=re.S)  # internal draft notes never reach the customer
    missing: list = []
    # images first (own markup, so the markdown parser leaves them alone)
    stash = {}

    def stash_img(m):
        key = f"@@IMG{len(stash)}@@"
        stash[key] = embed(m, md_path.parent, missing)
        return key

    text = re.sub(r"!\[([^\]]*)\]\(([^)]+)\)", stash_img, text)
    md = markdown.Markdown(extensions=["tables", "toc", "sane_lists", "attr_list"], extension_configs={"toc": {"toc_depth": "2-3"}})
    body = md.convert(text)
    for k, v in stash.items():
        body = body.replace(f"<p>{k}</p>", v).replace(k, v)
    toc = f'<nav class="toc"><h2>Contents</h2>{md.toc}</nav>'
    doc = f'<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Gigantic Prints - Handoff Guide</title><style>{CSS}</style></head><body>{body.replace("</h1>", "</h1>" + toc, 1)}</body></html>'

    out = Path(a.out)
    out.mkdir(parents=True, exist_ok=True)
    html_path = out / (md_path.stem + ".html")
    html_path.write_text(doc, encoding="utf-8")
    print("html:", html_path, f"({len(missing)} screenshot(s) not captured yet)")
    for m in missing:
        print("  missing:", m)
    if a.no_pdf:
        return
    from playwright.sync_api import sync_playwright

    pdf_path = out / (md_path.stem + ".pdf")
    with sync_playwright() as pw:
        browser = pw.chromium.launch()
        try:
            page = browser.new_page()
            page.goto(html_path.resolve().as_uri(), wait_until="load")
            page.pdf(
                path=str(pdf_path), format="A4", print_background=True,
                display_header_footer=True,
                header_template="<span></span>",
                footer_template='<div style="font-size:8px;width:100%;text-align:center;color:#777">Gigantic Prints handoff guide - page <span class="pageNumber"></span> of <span class="totalPages"></span></div>',
                margin={"top": "18mm", "bottom": "18mm", "left": "15mm", "right": "15mm"},
            )
        finally:
            browser.close()
    print("pdf:", pdf_path)


if __name__ == "__main__":
    main()

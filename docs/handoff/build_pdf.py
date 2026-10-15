#!/usr/bin/env python3
"""Render docs/handoff/Gigantic-Prints-Handoff-Guide.md to HTML and PDF (Markdown -> HTML -> Chromium page.pdf).

    python build_pdf.py [--md Gigantic-Prints-Handoff-Guide.md] [--out dist] [--no-pdf] [--email]

--email also writes <name>-email.pdf: same content, screenshots shrunk (max 1000 px wide) and JPEG-compressed (quality 62) for e-mail attachments.

Needs: pip install markdown playwright ; python -m playwright install chromium
Images are read from ./images (paths in the markdown are relative to this folder) and embedded in the HTML as data URIs,
so dist/*.html is a single self-contained file. A screenshot that is not captured yet is replaced by a grey placeholder box.
"""
import argparse
import base64
import html
import io
import mimetypes
import re
from pathlib import Path

import markdown

HERE = Path(__file__).resolve().parent

CSS = """
@page { size: A4; margin: 18mm 15mm 18mm 15mm; }
* { box-sizing: border-box; }
body { font: 10.5pt/1.5 -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1b1f24; margin: 0; }
h1 { font-size: 24pt; margin: 0 0 4mm; color: #2b333d; }
h2 { color: #2b333d; font-size: 16pt; margin: 9mm 0 3mm; padding-bottom: 1.5mm; border-bottom: 2px solid #4f7cc4; page-break-after: avoid; }
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
nav.toc ul { list-style: none; padding-left: 0; margin: 0; } nav.toc ul ul { padding-left: 6mm; }
nav.toc { font-size: 10pt; line-height: 1.38; } nav.toc h2 { margin-top: 2mm; }
a { color: #0b57d0; text-decoration: none; }

.profile { page-break-inside: avoid; margin: 2mm 0 6mm; }
.profile.pf-pc { page-break-before: always; }
.pf-head { display: flex; align-items: center; gap: 4mm; margin: 2mm 0 3mm; }
.pf-logo-nb { height: 10mm; } .pf-logo-pc { height: 11mm; }
.pf-tag { font-size: 9.5pt; color: #5b636b; border-left: 1px solid #c9d1db; padding-left: 4mm; }
.pf-lead { font-size: 11pt; line-height: 1.55; }
.pf-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 3mm; margin: 4mm 0 5mm; }
.pf-stats div { background: #f3f6fb; border-top: 3px solid #4f7cc4; padding: 3mm 3mm 2.5mm; }
.pf-stats b { display: block; font-size: 18pt; line-height: 1.1; color: #2b333d; }
.pf-stats span { display: block; font-size: 8.5pt; color: #5b636b; margin-top: 1mm; }
.pf-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; }
.pf-cols h4, .profile h4 { color: #2b333d; margin: 2mm 0 1.5mm; }
.pf-cols ul { padding-left: 4.5mm; margin: 0; font-size: 9.5pt; } .pf-cols li { margin-bottom: 1.2mm; }
.pf-note { font-size: 8.5pt; color: #5b636b; }
.pf-divs { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 2.5mm; width: calc(100% - 1mm); }
.pf-div { border: 1px solid #d5dbe3; padding: 2.5mm; font-size: 8.5pt; color: #4a525b; }
.pf-div b { display: block; font-size: 10pt; color: #2b333d; margin-bottom: 1mm; }
.pf-div.pf-hl { border: 1.5px solid #4f7cc4; background: #f3f6fb; }
.pf-contact { margin-top: 4mm; padding: 3mm 4mm; border-left: 3px solid #2b333d; background: #f6f7f9; font-size: 8.8pt; line-height: 1.6; color: #4a525b; page-break-inside: avoid; }
.pf-contact b { color: #2b333d; }
"""

COVER_CSS = """
@page { size: A4; margin: 0; }
* { box-sizing: border-box; }
body { margin: 0; font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1b1f24; -webkit-print-color-adjust: exact; }
.page { width: 210mm; height: 297mm; position: relative; overflow: hidden; }
.band { position: absolute; top: 0; left: 0; right: 0; height: 9mm; background: #4f7cc4; }
.logos { position: absolute; top: 28mm; left: 22mm; right: 22mm; display: flex; align-items: center; gap: 9mm; }
.logos img.nb { height: 12mm; } .logos img.pc { height: 14mm; } .logos .bar { width: 0.4mm; height: 16mm; background: #c9d1db; }
.division { position: absolute; top: 50mm; left: 22mm; font-size: 9.5pt; color: #5b636b; letter-spacing: .02em; }
.title { position: absolute; top: 92mm; left: 22mm; right: 22mm; }
.title .kicker { font-size: 11pt; letter-spacing: .12em; text-transform: uppercase; color: #4f7cc4; font-weight: 600; }
.title h1 { font-size: 30pt; line-height: 1.15; margin: 4mm 0 5mm; color: #2b333d; }
.title .sub { font-size: 13pt; color: #4a525b; }
.meta { position: absolute; top: 160mm; left: 22mm; right: 22mm; border-top: 1px solid #d5dbe3; padding-top: 6mm; font-size: 10.5pt; line-height: 1.7; }
.meta b { display: inline-block; width: 38mm; color: #5b636b; font-weight: 600; }
.company { position: absolute; bottom: 20mm; left: 22mm; right: 22mm; font-size: 9pt; line-height: 1.6; color: #4a525b; border-top: 3px solid #2b333d; padding-top: 5mm; }
.company .name { font-size: 10.5pt; font-weight: 700; color: #2b333d; }
"""

COMPANY = {
    "brand": "Printcart — a division of Netbase.,JSC",
    "legal": "Netbase Business Application And Solution Joint Stock Company (Netbase JSC)",
    "address": "No. 18, Lane 445/46 Lac Long Quan Street, Tay Ho Ward, Hanoi City, Vietnam",
    "web": "printcart.com · netbasejsc.com",
    "contact": "David (Nguyen Quang Huy), CEO · ceo@printcart.com · WhatsApp +84 937 869 689",
}
DOC_TITLE = "Gigantic Prints Online Store"
DOC_SUB = "Handoff Guide — shopper and administrator instructions"
LOGO = HERE / "assets" / "printcart-logo.png"
NB_LOGO = HERE / "assets" / "netbase-logo.png"


def logo_uri(path=LOGO):
    mime = mimetypes.guess_type(path.name)[0] or "image/png"
    return f"data:{mime};base64," + base64.b64encode(path.read_bytes()).decode()


def inline_assets(markup):
    """Raw-HTML blocks in the markdown reference assets/<file>; embed them so the HTML/PDF is self-contained."""
    return re.sub(r'src="(assets/[^"]+)"', lambda m: f'src="{logo_uri(HERE / m.group(1))}"', markup)


def cover_html(version, date):
    c = COMPANY
    return f"""<!doctype html><html lang="en"><head><meta charset="utf-8"><style>{COVER_CSS}</style></head><body><div class="page">
<div class="band"></div>
<div class="logos"><img class="nb" src="{logo_uri(NB_LOGO)}" alt="Netbase JSC"><div class="bar"></div><img class="pc" src="{logo_uri()}" alt="Printcart"></div>
<div class="division">Printcart is the web-to-print division of Netbase JSC</div>
<div class="title"><div class="kicker">Project handoff</div><h1>{DOC_TITLE}</h1><div class="sub">{DOC_SUB}</div></div>
<div class="meta">
<div><b>Prepared for</b>Hoyle Fabrio, SignEdge Graphics / Gigantic Prints</div>
<div><b>Prepared by</b>Printcart, a division of Netbase JSC</div>
<div><b>Project lead</b>David Nguyen Quang Huy, CEO</div>
<div><b>Store</b>giganticprints.com (staging: giganticprints.printcart.com)</div>
<div><b>Version</b>{html.escape(version)} · {html.escape(date)}</div>
</div>
<div class="company"><div class="name">{c["brand"]}</div><div>{c["legal"]}</div><div>{c["address"]}</div><div>{c["web"]}</div><div>{c["contact"]}</div></div>
</div></body></html>"""


def header_template(version):
    return (f'<div style="width:100%;margin:0 15mm;padding-bottom:2mm;border-bottom:0.6px solid #c9d1db;display:flex;'
            f'justify-content:space-between;align-items:flex-end;font-family:Segoe UI,Arial,sans-serif;font-size:8px;color:#5b636b;-webkit-print-color-adjust:exact">'
            f'<img src="{logo_uri()}" style="height:5.5mm"><span style="display:flex;align-items:flex-end;gap:3mm">{DOC_TITLE} · Handoff Guide v{html.escape(version)}'
            f'<img src="{logo_uri(NB_LOGO)}" style="height:3.6mm"></span></div>')


def footer_template():
    return ('<div style="width:100%;margin:0 15mm;padding-top:2mm;border-top:0.6px solid #c9d1db;display:flex;justify-content:space-between;'
            'font-family:Segoe UI,Arial,sans-serif;font-size:7.5px;color:#6b737b">'
            f'<span>{COMPANY["brand"]} · {COMPANY["web"]} · ceo@printcart.com</span>'
            '<span>Page <span class="pageNumber"></span> of <span class="totalPages"></span></span></div>')


COMPRESS = False


def embed(match, base: Path, missing: list):
    alt, src = match.group(1), match.group(2)
    p = (base / src).resolve()
    if p.is_file():
        mime = mimetypes.guess_type(p.name)[0] or "image/png"
        raw = p.read_bytes()
        if COMPRESS:
            from PIL import Image
            im = Image.open(io.BytesIO(raw)).convert("RGB")
            if im.width > 1000:
                im = im.resize((1000, round(im.height * 1000 / im.width)), Image.LANCZOS)
            buf = io.BytesIO()
            im.save(buf, "JPEG", quality=62, optimize=True)
            raw, mime = buf.getvalue(), "image/jpeg"
        data = base64.b64encode(raw).decode()
        return f'<figure><img alt="{html.escape(alt)}" src="data:{mime};base64,{data}"><figcaption>{html.escape(alt)}</figcaption></figure>'
    missing.append(src)
    return f'<figure><div class="missing">Screenshot to be added: {html.escape(src)}</div><figcaption>{html.escape(alt)}</figcaption></figure>'


def build(a, email):
    global COMPRESS
    COMPRESS = email
    md_path = Path(a.md)
    text = md_path.read_text(encoding="utf-8")
    text = re.sub(r"<!--.*?-->", "", text, flags=re.S)  # internal draft notes never reach the customer
    m = re.search(r"^Version (\S+)", text, flags=re.M)
    version = m.group(1) if m else "1.0"
    # the title line and the version/store lines move to the cover page
    text = re.sub(r"\A\s*# [^\n]*\n(.*?)(?=^## )", "", text, count=1, flags=re.S | re.M)
    missing: list = []
    # images first (own markup, so the markdown parser leaves them alone)
    stash = {}

    def stash_img(m):
        key = f"@@IMG{len(stash)}@@"
        stash[key] = embed(m, md_path.parent, missing)
        return key

    text = re.sub(r"!\[([^\]]*)\]\(([^)]+)\)", stash_img, text)
    md = markdown.Markdown(extensions=["tables", "toc", "sane_lists", "attr_list"], extension_configs={"toc": {"toc_depth": "2-3"}})
    body = inline_assets(md.convert(text))
    for k, v in stash.items():
        body = body.replace(f"<p>{k}</p>", v).replace(k, v)
    toc = f'<nav class="toc"><h2>Contents</h2>{md.toc}</nav>'
    head = f'<meta charset="utf-8"><title>{DOC_TITLE} - Handoff Guide v{version}</title><meta name="author" content="Printcart (Netbase JSC)">'
    doc = f'<!doctype html><html lang="en"><head>{head}<style>{CSS}</style></head><body>{toc}{body}</body></html>'
    cover = cover_html(version, a.date)

    out = Path(a.out)
    out.mkdir(parents=True, exist_ok=True)
    stem = md_path.stem + ("-email" if email else "")
    html_path = out / (stem + ".html")
    cover_body = re.search(r"<body>(.*)</body>", cover, flags=re.S).group(1)
    web = doc.replace("<body>", f'<body><div style="max-width:210mm;margin:0 auto 10mm;box-shadow:0 0 0 1px #d5dbe3"><style>{COVER_CSS.replace("@page { size: A4; margin: 0; }", "")}</style>{cover_body}</div>', 1)
    html_path.write_text(web, encoding="utf-8")
    print("html:", html_path, f"({len(missing)} screenshot(s) not captured yet)")
    for m in missing:
        print("  missing:", m)
    if a.no_pdf:
        return
    from playwright.sync_api import sync_playwright
    from pypdf import PdfReader, PdfWriter

    pdf_path = out / (stem + ".pdf")
    tmp_cover, tmp_body, tmp_doc = out / (stem + ".cover.tmp.pdf"), out / (stem + ".body.tmp.pdf"), out / (stem + ".body.tmp.html")
    tmp_doc.write_text(doc, encoding="utf-8")
    with sync_playwright() as pw:
        browser = pw.chromium.launch()
        try:
            page = browser.new_page()
            page.set_content(cover, wait_until="load")
            page.pdf(path=str(tmp_cover), format="A4", print_background=True, margin={"top": "0", "bottom": "0", "left": "0", "right": "0"})
            page.goto(tmp_doc.resolve().as_uri(), wait_until="load")
            page.pdf(
                path=str(tmp_body), format="A4", print_background=True,
                display_header_footer=True,
                header_template=header_template(version),
                footer_template=footer_template(),
                margin={"top": "22mm", "bottom": "18mm", "left": "15mm", "right": "15mm"},
            )
        finally:
            browser.close()
    writer = PdfWriter()
    for part in (tmp_cover, tmp_body):
        for pg in PdfReader(str(part)).pages:
            writer.add_page(pg)
    writer.add_metadata({"/Title": f"{DOC_TITLE} - Handoff Guide v{version}", "/Author": "Printcart (Netbase JSC)", "/Subject": "Gigantic Prints store handoff"})
    with open(pdf_path, "wb") as fh:
        writer.write(fh)
    for t in (tmp_cover, tmp_body, tmp_doc):
        t.unlink(missing_ok=True)
    print("pdf:", pdf_path)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--md", default=str(HERE / "Gigantic-Prints-Handoff-Guide.md"))
    ap.add_argument("--out", default=str(HERE / "dist"))
    ap.add_argument("--no-pdf", action="store_true")
    ap.add_argument("--email", action="store_true", help="also build the small e-mail PDF")
    ap.add_argument("--date", default="15 October 2026", help="date printed on the cover")
    a = ap.parse_args()

    build(a, False)
    if a.email:
        build(a, True)
        (Path(a.out) / (Path(a.md).stem + '-email.html')).unlink(missing_ok=True)




if __name__ == "__main__":
    main()

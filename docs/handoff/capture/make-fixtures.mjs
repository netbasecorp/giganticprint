#!/usr/bin/env node
// Generates the sample artwork used by the screenshots (so the guide shows a realistic banner, not a test grid).
//   node make-fixtures.mjs [--out ./fixtures]      (Playwright from PW_MODULE_DIR, default /work/node_modules)
// Output: sample-banner-art.png (3600x2400 px @150 dpi = 24x16 in), sample-lowres-72dpi.png (1728x1152 px @72 dpi = 24x16 in).
import { createRequire } from 'node:module';
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const i = process.argv.indexOf('--out');
const OUT = path.resolve(i > -1 ? process.argv[i + 1] : path.join(here, 'fixtures'));
const require = createRequire(path.join(process.env.PW_MODULE_DIR || '/work/node_modules', 'noop.js'));
const { chromium } = require('playwright');

const crcTable = (() => { const t = []; for (let n = 0; n < 256; n++) { let c = n; for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1; t[n] = c >>> 0; } return t; })();
const crc32 = (buf) => { let c = 0xffffffff; for (const b of buf) c = crcTable[(c ^ b) & 0xff] ^ (c >>> 8); return (c ^ 0xffffffff) >>> 0; };
// insert a pHYs chunk (pixels per metre) right after IHDR so the designer reads the print resolution
function withDpi(png, dpi) {
  const ppm = Math.round(dpi / 0.0254);
  const data = Buffer.alloc(9); data.writeUInt32BE(ppm, 0); data.writeUInt32BE(ppm, 4); data[8] = 1;
  const type = Buffer.from('pHYs');
  const len = Buffer.alloc(4); len.writeUInt32BE(9);
  const crc = Buffer.alloc(4); crc.writeUInt32BE(crc32(Buffer.concat([type, data])));
  const chunk = Buffer.concat([len, type, data, crc]);
  const ihdrEnd = 8 + 4 + 4 + 13 + 4; // signature + IHDR chunk
  return Buffer.concat([png.subarray(0, ihdrEnd), chunk, png.subarray(ihdrEnd)]);
}

const html = (w, h) => `<!doctype html><meta charset="utf-8"><style>
  html,body{margin:0;width:${w}px;height:${h}px;overflow:hidden;font-family:Arial,Helvetica,sans-serif}
  .bg{position:absolute;inset:0;background:linear-gradient(135deg,#0b3d91 0%,#1565c0 55%,#f9a825 100%)}
  .ring{position:absolute;right:${w * 0.04}px;top:${h * 0.1}px;width:${h * 0.55}px;height:${h * 0.55}px;border-radius:50%;background:#fff3;border:${h * 0.02}px solid #fff6}
  h1{position:absolute;left:${w * 0.06}px;top:${h * 0.16}px;margin:0;font-size:${h * 0.2}px;color:#fff;letter-spacing:${h * 0.004}px;line-height:1}
  h2{position:absolute;left:${w * 0.06}px;top:${h * 0.42}px;margin:0;font-size:${h * 0.11}px;color:#ffe082;line-height:1}
  .tag{position:absolute;left:${w * 0.06}px;bottom:${h * 0.12}px;padding:${h * 0.03}px ${h * 0.06}px;background:#fff;color:#0b3d91;font-weight:700;font-size:${h * 0.07}px;border-radius:${h * 0.02}px}
  .small{position:absolute;right:${w * 0.05}px;bottom:${h * 0.12}px;color:#fff;font-size:${h * 0.05}px}
</style><div class="bg"></div><div class="ring"></div>
<h1>GRAND OPENING</h1><h2>Saturday, 10 a.m. - 6 p.m.</h2><div class="tag">SAMPLE SIGN CO.</div><div class="small">123 Main Street</div>`;

const browser = await chromium.launch();
try {
  fs.mkdirSync(OUT, { recursive: true });
  for (const [name, w, h, dpi] of [['sample-banner-art.png', 3600, 2400, 150], ['sample-lowres-72dpi.png', 1728, 1152, 72]]) {
    const page = await browser.newPage({ viewport: { width: w, height: h } });
    await page.setContent(html(w, h));
    const png = await page.screenshot({ type: 'png' });
    await page.close();
    fs.writeFileSync(path.join(OUT, name), withDpi(png, dpi));
    console.log('wrote', name, w + 'x' + h, dpi + ' dpi');
  }
} finally {
  await browser.close();
}
void zlib;

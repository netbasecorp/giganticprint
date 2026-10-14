#!/usr/bin/env node
// Screenshot capture for the Gigantic Prints handoff guide.
//
//   node capture.mjs [--shots shots.json] [--out ../images] [--batch now|b4|all] [--only 01,07-foo] [--base URL]
//                    [--auth-dir DIR] [--admin-cookies FILE] [--fixtures DIR]
//
// Reads shots.json, runs every shot in a fresh page, adds red outlines / numbered markers on the elements named in
// "highlight", and saves <out>/<id>.png (id = "NN-slug"). One failing shot never stops the run; a report is written to
// <out>/_capture-report.json and the exit code is 1 when any shot failed. The browser is always closed in `finally`.
//
// Playwright is resolved from PW_MODULE_DIR (default /work/node_modules, i.e. inside the e2e container).
import { createRequire } from 'node:module';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const arg = (name, def) => { const i = process.argv.indexOf('--' + name); return i > -1 && process.argv[i + 1] ? process.argv[i + 1] : def; };

const SHOTS_FILE = path.resolve(arg('shots', path.join(here, 'shots.json')));
const OUT_DIR = path.resolve(arg('out', path.join(here, '..', 'images')));
const BATCH = arg('batch', 'all');
const ONLY = arg('only', '').split(',').map(s => s.trim()).filter(Boolean);
const FIXTURES = path.resolve(arg('fixtures', path.join(here, 'fixtures')));
const AUTH_DIR = path.resolve(arg('auth-dir', process.env.GP_AUTH_DIR || '/work/auth'));          // gp_test_b2c.json ...
const ADMIN_COOKIES = path.resolve(arg('admin-cookies', process.env.GP_ADMIN_COOKIES || '/tmp/handoff/admin.json'));

const require = createRequire(path.join(process.env.PW_MODULE_DIR || '/work/node_modules', 'noop.js'));
const { chromium } = require('playwright');

const cfg = JSON.parse(fs.readFileSync(SHOTS_FILE, 'utf8'));
const BASE = (arg('base', process.env.BASE_URL || cfg.baseUrl || 'https://giganticprints.printcart.com')).replace(/\/$/, '');
const VIEWPORTS = {
  desktop: { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 },
  mobile: { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true },
};
const AUTH_FILES = { b2c: 'gp_test_b2c.json', b2b: 'gp_test_b2b.json', reseller: 'gp_test_reseller.json' };
const RED = '#e11d2e';

const sub = (s) => (typeof s === 'string' ? s.replaceAll('${BASE}', BASE) : s);
const fixture = (f) => (path.isAbsolute(f) ? f : path.join(FIXTURES, f));
const sleep = (ms) => new Promise(r => setTimeout(r, ms));

function cookiesFor(auth) {
  if (!auth || auth === 'none') return [];
  const file = auth === 'admin' ? ADMIN_COOKIES : path.join(AUTH_DIR, AUTH_FILES[auth] || '');
  if (!fs.existsSync(file)) throw new Error(`missing auth cookies for "${auth}" (${file})`);
  return JSON.parse(fs.readFileSync(file, 'utf8'));
}

// ---- designer helpers (same readiness checks the e2e suite uses) -------------------------------------------------
async function killTour(page) {
  for (let i = 0; i < 3; i++) {
    try { await page.click('.js-tour-close', { timeout: 1200 }); await page.waitForTimeout(400); } catch (e) { break; }
  }
  await page.addStyleTag({ content: '.tour-guide{display:none!important}' }).catch(() => {});
}
async function waitDesignerReady(page, ms = 60000) {
  try {
    await page.waitForFunction(() => {
      try {
        const s = window.angular && window.nbOption && nbOption.crtlId && angular.element(document.getElementById(nbOption.crtlId)).scope();
        return !!(s && s.nbd_fields && Object.keys(s.nbd_fields).length && nbOption.initialed);
      } catch (e) { return false; }
    }, null, { timeout: ms });
    await page.waitForTimeout(1500);
    return true;
  } catch (e) { return false; }
}
async function waitArtPlaced(page, ms = 120000) {
  await page.waitForFunction(() => {
    try {
      const s = angular.element(document.querySelector('[ng-controller=designCtrl]')).scope();
      return s.stages[s.currentStage].canvas.getObjects().some(o => o.type === 'image');
    } catch (e) { return false; }
  }, null, { timeout: ms });
  await page.waitForTimeout(2500);
}

// ---- action runner -----------------------------------------------------------------------------------------------
// Each action is an object with one verb key (plus optional modifiers). See README.md for the list.
async function runAction(page, a) {
  const [verb] = Object.keys(a);
  const v = a[verb];
  switch (verb) {
    case 'goto': await page.goto(sub(v), { waitUntil: 'load', timeout: 90000 }); break;
    case 'click': { const loc = page.locator(v); await (a.nth !== undefined ? loc.nth(a.nth) : loc.first()).click({ timeout: 15000, force: !!a.force }); break; }
    case 'clickText': await page.getByText(v, { exact: !!a.exact }).first().click({ timeout: 15000, force: true }); break;
    case 'hover': await page.locator(v).first().hover({ timeout: 15000 }); break;
    case 'fill': await page.locator(v).first().fill(sub(String(a.value)), { timeout: 15000 }); if (a.enter) await page.locator(v).first().press('Enter'); break;
    case 'press': await page.keyboard.press(v); break;
    case 'select': await page.locator(v).first().selectOption(a.value); break;
    case 'check': await page.locator(v).first().check({ timeout: 15000 }); break;
    case 'upload': await page.setInputFiles(v, fixture(a.file)); break;
    case 'waitFor': await page.locator(v).first().waitFor({ state: a.state || 'visible', timeout: a.timeout || 30000 }); break;
    case 'wait': await page.waitForTimeout(v); break;
    case 'mouseAway': await page.mouse.move(2, 2); await page.waitForTimeout(300); break;
    case 'scrollTo': await page.locator(v).first().scrollIntoViewIfNeeded(); break;
    case 'scrollBy': await page.evaluate((y) => window.scrollBy(0, y), v); break;
    case 'evaluate': await page.evaluate(v); break;
    case 'designerTab': { // PHOTOS | PRINTING | TEXT | LAYERS
      if (v === 'PRINTING') await page.locator('.main-tabs li.tab:has(.icon-nbd-package)').first().click({ timeout: 6000, force: true });
      else await page.locator(`text=${v}`).first().click({ timeout: 6000, force: true });
      await page.waitForTimeout(900); break;
    }
    case 'designerUpload': // upload artwork in the designer and wait until it sits on the canvas
      await page.locator('text=PHOTOS').first().click({ timeout: 6000, force: true }).catch(() => {});
      await page.waitForTimeout(500);
      await page.setInputFiles('#tab-photo .form-upload input[type="file"], input[type=file]', fixture(v));
      await waitArtPlaced(page, a.timeout || 120000); break;
    case 'setSize': { // designer W x H for products without art
      const ins = page.locator('input.nbo-dimension');
      for (const [i, val] of [[0, v[0]], [1, v[1]]]) { const el = ins.nth(i); await el.scrollIntoViewIfNeeded(); await el.fill(String(val)); await el.press('Tab'); await page.waitForTimeout(700); }
      await page.waitForTimeout(1800); break;
    }
    case 'pickOption': { const el = page.getByText(v, { exact: false }).first(); await el.scrollIntoViewIfNeeded(); await el.click({ force: true, timeout: 8000 }); await page.waitForTimeout(1800); break; }
    default: throw new Error('unknown action verb: ' + verb);
  }
}

// ---- highlights --------------------------------------------------------------------------------------------------
// highlight: "css" | ["css", ...] | [{ "sel": "css", "label": "1", "pad": 4 }, ...]  -> red outline (+ numbered marker when label is set)
async function highlight(page, spec) {
  const items = (Array.isArray(spec) ? spec : [spec]).map(h => (typeof h === 'string' ? { sel: h } : h));
  for (const [i, h] of items.entries()) {
    const loc = page.locator(h.sel).first();
    if (await loc.count() === 0) throw new Error('highlight selector not found: ' + h.sel);
    if (i === 0 && !h.noScroll) await loc.scrollIntoViewIfNeeded().catch(() => {});
  }
  await page.waitForTimeout(250);
  // rectangles in viewport coordinates (any Playwright selector works, not only CSS)
  const rects = [];
  for (const h of items) {
    const box = await page.locator(h.sel).first().boundingBox();
    if (!box) throw new Error('highlight element has no box (hidden?): ' + h.sel);
    rects.push({ ...h, r: box });
  }
  await page.evaluate(({ rects, RED }) => {
    document.querySelectorAll('.gp-hl-marker').forEach(n => n.remove());
    rects.forEach((h) => {
      const r = { left: h.r.x, top: h.r.y, width: h.r.width, height: h.r.height };
      const pad = h.pad == null ? 4 : h.pad;
      const box = document.createElement('div');
      box.className = 'gp-hl-marker';
      Object.assign(box.style, { position: 'fixed', left: (r.left - pad) + 'px', top: (r.top - pad) + 'px', width: (r.width + pad * 2) + 'px', height: (r.height + pad * 2) + 'px',
        border: '3px solid ' + RED, borderRadius: '6px', boxShadow: '0 0 0 2px rgba(255,255,255,.85)', pointerEvents: 'none', zIndex: 2147483646, boxSizing: 'border-box' });
      document.body.appendChild(box);
      if (h.label) {
        const b = document.createElement('div');
        b.className = 'gp-hl-marker'; b.textContent = h.label;
        Object.assign(b.style, { position: 'fixed', left: Math.max(2, r.left - pad - 12) + 'px', top: Math.max(2, r.top - pad - 12) + 'px', width: '26px', height: '26px', lineHeight: '26px', textAlign: 'center',
          background: RED, color: '#fff', font: '700 14px/26px Arial, sans-serif', borderRadius: '50%', boxShadow: '0 1px 4px rgba(0,0,0,.4)', pointerEvents: 'none', zIndex: 2147483647 });
        document.body.appendChild(b);
      }
    });
  }, { rects, RED });
}


// ---- privacy: no real or test-harness identities in the screenshots ------------------------------------------------
// Replaces e-mail addresses (except the store's own @giganticprints.com ones) and the test user names with neutral sample names.
async function redact(page) {
  await page.evaluate(() => {
    const people = [['gp_test_b2c', 'Alex Morgan', 'alex.morgan'], ['gp_test_b2b', 'Sam Rivera', 'sam.rivera'], ['gp_test_reseller', 'Pat Lee', 'pat.lee'], ['huynnnth', 'Jordan Blake', 'jordan.blake'], ['Nguyễn Huy', 'Jordan Blake', 'jordan.blake']];
    const fix = (t) => {
      if (!t) return t;
      for (const [login, name, slug] of people) {
        t = t.split('GP Test ' + login).join(name).split(login + '@example.test').join(slug + '@example.com').split(login).join(slug);
      }
      t = t.split('GP Test').join('Sample');
      if (t.trim() === 'HHH') t = t.replace('HHH', 'Sample Co.');
      return t.replace(/[A-Za-z0-9._%+-]+@(?!giganticprints\.com)[A-Za-z0-9.-]+\.[A-Za-z]{2,}/g, 'name@example.com');
    };
    const w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    const nodes = []; while (w.nextNode()) nodes.push(w.currentNode);
    nodes.forEach((n) => { const v = fix(n.nodeValue); if (v !== n.nodeValue) n.nodeValue = v; });
    document.querySelectorAll('input, textarea').forEach((e) => { if (e.type !== 'password' && e.value) { const v = fix(e.value); if (v !== e.value) e.value = v; } });
  });
}

const ADMIN_CLEAN_CSS = `
  .notice,.updated,.update-nag,#wpfooter,.wpforms-notice,#updraft-dashnotice,.updraft-ad-container,
  .woocommerce-message,.wc-admin-notice,#screen-meta-links,.wpmudev-notice,[class*="woocommerce-layout__notice"],.woocommerce-store-alerts,.wp-mail-smtp-review-notice,[class*="smtp"][class*="notice"],#wp-mail-smtp-notice-bar,.wp-mail-smtp-lite-upgrade{display:none!important}`;

// ---- main --------------------------------------------------------------------------------------------------------
const results = [];
fs.mkdirSync(OUT_DIR, { recursive: true });
const browser = await chromium.launch();
const contexts = new Map();
try {
  const all = cfg.shots.filter(s => (BATCH === 'all' || (s.batch || 'now') === BATCH) && (!ONLY.length || ONLY.some(o => s.id === o || s.id.startsWith(o))));
  console.log(`capture: ${all.length} shot(s), base ${BASE}, batch ${BATCH}`);
  for (const s of all) {
    const t0 = Date.now();
    const res = { id: s.id, ok: false };
    let page;
    try {
      const vp = VIEWPORTS[s.viewport || 'desktop'];
      const key = (s.auth || 'none') + '|' + (s.viewport || 'desktop');
      if (!contexts.has(key)) {
        const ctx = await browser.newContext({ ...vp, ignoreHTTPSErrors: false });
        await ctx.addCookies(cookiesFor(s.auth));
        contexts.set(key, ctx);
      }
      page = await contexts.get(key).newPage();
      page.on('dialog', d => d.accept().catch(() => {}));
      const url = sub(s.url);
      await page.goto(url, { waitUntil: 'load', timeout: 90000 }).catch(async () => { await sleep(2000); await page.goto(url, { waitUntil: 'load', timeout: 90000 }); });
      await page.waitForTimeout(s.waitMs || 1500);
      if (s.designer) { await killTour(page); if (!(await waitDesignerReady(page))) throw new Error('designer did not become ready'); await killTour(page); }
      if (s.auth === 'admin') await page.addStyleTag({ content: ADMIN_CLEAN_CSS });
      if (s.css) await page.addStyleTag({ content: s.css });
      for (const a of (s.actions || [])) await runAction(page, a);
      if (s.auth === 'admin') await page.addStyleTag({ content: ADMIN_CLEAN_CSS }); // notices that appear late
      if (s.redact !== false) await redact(page);
      if (s.hide) await page.addStyleTag({ content: s.hide.map(h => `${h}{visibility:hidden!important}`).join('\n') });
      if (s.waitFor) await page.locator(s.waitFor).first().waitFor({ state: 'visible', timeout: 30000 });
      if (s.waitAfter) await page.waitForTimeout(s.waitAfter);
      if (s.highlight) await highlight(page, s.highlight);
      const file = path.join(OUT_DIR, s.id + '.png');
      if (s.clip) await page.locator(s.clip).first().screenshot({ path: file });
      else await page.screenshot({ path: file, fullPage: !!s.fullPage });
      res.ok = true; res.file = file; res.bytes = fs.statSync(file).size;
      // undo what the shot changed (e.g. empty the cart); never fails the shot
      for (const a of (s.cleanup || [])) { try { await runAction(page, a); } catch (e) { res.cleanupError = String(e.message).slice(0, 120); break; } }
    } catch (e) {
      res.error = String(e.message || e).split('\n')[0].slice(0, 300);
      try { if (page) await page.screenshot({ path: path.join(OUT_DIR, '_failed-' + s.id + '.png') }); } catch (_) { /* ignore */ }
    } finally {
      if (page) await page.close().catch(() => {});
    }
    res.seconds = Math.round((Date.now() - t0) / 100) / 10;
    results.push(res);
    console.log(`${res.ok ? 'ok  ' : 'FAIL'} ${s.id} (${res.seconds}s)${res.ok ? '' : ' ' + res.error}`);
  }
} finally {
  for (const c of contexts.values()) await c.close().catch(() => {});
  await browser.close();
  fs.writeFileSync(path.join(OUT_DIR, '_capture-report.json'), JSON.stringify({ at: new Date().toISOString(), base: BASE, results }, null, 2));
}
const failed = results.filter(r => !r.ok).length;
console.log(`done: ${results.length - failed} ok, ${failed} failed`);
process.exit(failed ? 1 : 0);

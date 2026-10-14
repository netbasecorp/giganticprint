// Shared helpers for Gigantic Prints e2e (Playwright JS, runs in container gp-e2e)
const fs = require('fs');
const path = require('path');
const BASE = process.env.BASE_URL || 'https://giganticprints.printcart.com';
const SHOT_ROOT = process.env.SHOT_DIR || 'shots';

function shotPath(batch, name) {
  const dir = path.join(SHOT_ROOT, batch);
  fs.mkdirSync(dir, { recursive: true });
  return path.join(dir, name + '.png');
}
async function shot(page, batch, name, full = false) {
  await page.screenshot({ path: shotPath(batch, name), fullPage: full });
}
async function killTour(page) {
  for (let i = 0; i < 3; i++) {
    try { await page.click('.js-tour-close', { timeout: 1200 }); await page.waitForTimeout(400); } catch (e) { break; }
  }
  await page.addStyleTag({ content: '.tour-guide{display:none!important}' }).catch(() => {});
}
// options are fetched by AJAX (4-10 s on this server): wait until the NBO scope has its fields
async function waitReady(page, ms = 45000) {
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
async function openDesigner(page, pid) {
  for (let k = 0; k < 2; k++) {
    try { await page.goto(`${BASE}/create-your-own/?product_id=${pid}&view=m`, { waitUntil: 'load', timeout: 90000 }); break; }
    catch (e) { await page.waitForTimeout(2000); }
  }
  await page.waitForTimeout(1500);
  await killTour(page);
  const ok = await waitReady(page);
  await killTour(page);
  return ok;
}
async function tab(page, name) {
  if (name === 'PRINTING') { try { await page.locator('.main-tabs li.tab:has(.icon-nbd-package)').first().click({ timeout: 4000, force: true }); await page.waitForTimeout(900); } catch (e) {} return; }
  try { await page.locator(`text=${name}`).first().click({ timeout: 4000, force: true }); await page.waitForTimeout(900); } catch (e) {}
}
async function pickOption(page, text) {
  const el = page.getByText(text, { exact: false }).first();
  await el.scrollIntoViewIfNeeded();
  await el.click({ force: true, timeout: 5000 });
  await page.waitForTimeout(1800);
}
async function price(page) {
  return page.evaluate(() => {
    const e = document.getElementById('sg-total-price');
    const m = document.body.innerText.match(/Your price\s+([^\n]+)/);
    return (e && e.innerText.trim()) || (m && m[1].trim()) || null;
  });
}
async function dims(page) {
  return page.evaluate(() => [...document.querySelectorAll('input.nbo-dimension')].map(e => e.value).join('x'));
}
async function setSize(page, w, h) {
  const ins = page.locator('input.nbo-dimension');
  for (const [i, v] of [[0, w], [1, h]]) {
    const el = ins.nth(i);
    await el.scrollIntoViewIfNeeded();
    await el.fill(String(v));
    await el.press('Tab');
    await page.waitForTimeout(700);
  }
  await page.waitForTimeout(1800);
}
function num(s) { // "$16.63" | "16,63 $" | "$1,234.50" -> number
  if (s == null) return null;
  let t = String(s).replace(/[^0-9.,\-]/g, '');
  if (/,\d{1,2}$/.test(t)) t = t.replace(/\./g, '').replace(',', '.'); else t = t.replace(/,/g, '');
  const n = parseFloat(t); return isNaN(n) ? null : n;
}
function authFile(login) { return path.join('auth', login + '.json'); }
async function loginContext(browser, login, opts = {}) {
  const ctx = await browser.newContext(opts);
  const f = authFile(login);
  if (fs.existsSync(f)) await ctx.addCookies(JSON.parse(fs.readFileSync(f, 'utf8')));
  return ctx;
}
module.exports = { BASE, waitReady, pickOption, shot, killTour, openDesigner, tab, price, dims, setSize, num, loginContext, shotPath };

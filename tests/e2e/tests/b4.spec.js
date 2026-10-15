// B4 acceptance: mobile, reorder, Image Zone, templates, per-client products, big-file preview. Screens -> shots/b4/
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const G = require('../lib/gp');

test.setTimeout(300000);
const MOBILE = { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 };
const FX = (() => { try { return JSON.parse(fs.readFileSync('auth/fixture.json', 'utf8')); } catch (e) { return null; } })();

async function noHScroll(page, label) {
  const r = await page.evaluate(() => { const before = window.scrollX; window.scrollTo(200, 0); const after = window.scrollX; window.scrollTo(0, 0); return { sw: document.scrollingElement.scrollWidth, iw: window.innerWidth, moved: after - before }; });
  expect(r.sw, `${label}: scrollWidth ${r.sw} > ${r.iw}`).toBeLessThanOrEqual(r.iw);
  expect(r.moved, `${label}: page scrolls sideways`).toBe(0);
}

test.describe('mobile 390 px', () => {
  test('no horizontal scroll on any page', async ({ browser }) => {
    const ctx = await browser.newContext(MOBILE);
    const page = await ctx.newPage();
    try {
      const urls = ['/', '/shop/', '/product-category/banner/', '/product-category/handheld/', '/product/banner-13oz-single-sided/', '/product/acrylic/', '/about-us/', '/blog/', '/gallery/', '/contact-us/', '/my-account/', '/shopping-cart/', '/checkout/', '/b2b-portal/'];
      const bad = [];
      for (const u of urls) {
        await page.goto(G.BASE + u, { waitUntil: 'load', timeout: 90000 }).catch(() => {});
        await page.waitForTimeout(1500);
        try { await noHScroll(page, u); } catch (e) { bad.push(String(e.message).split('\n')[0]); }
        if (['/', '/product/banner-13oz-single-sided/', '/my-account/'].includes(u)) await G.shot(page, 'b4', '01-mobile' + (u === '/' ? '-home' : u.replace(/\W+/g, '-')) + '-390');
      }
      expect(bad).toEqual([]);
    } finally { await ctx.close(); }
  });

  test('designer phone flow: upload -> preview -> size -> options -> price -> add to cart', async ({ browser }) => {
    const ctx = await browser.newContext(MOBILE); // guest: retail prices
    const page = await ctx.newPage();
    const errs = [];
    page.on('pageerror', e => errs.push(String(e).slice(0, 200)));
    page.on('dialog', d => d.accept());
    try {
      await page.goto(`${G.BASE}/create-your-own/?product_id=1168&view=m`, { waitUntil: 'load', timeout: 90000 });
      await expect(page.locator('#sg-m')).toBeVisible({ timeout: 40000 });
      const up = page.locator('#sg-m-upload');
      await expect(up).toBeVisible();
      expect((await up.boundingBox()).height).toBeGreaterThanOrEqual(44);
      await G.shot(page, 'b4', '02-mobile-designer-start-390');
      await noHScroll(page, 'designer start');
      await page.waitForFunction(() => window.nbOption && nbOption.initialed, null, { timeout: 60000 });
      await page.waitForTimeout(2500);
      // empty print area: add to cart is refused with a message
      await page.locator('#sg-add-to-cart-btn').click({ force: true });
      await expect(page.locator('#sg-empty-msg')).toBeVisible({ timeout: 8000 });
      expect(page.url()).toContain('create-your-own');
      // upload
      await page.setInputFiles('#tab-photo .form-upload input[type="file"], input[type=file]', 'testimg/test-24x16in-150dpi.png');
      await expect(page.locator('#sg-m-preview img')).toBeVisible({ timeout: 90000 });
      await page.waitForFunction(() => { const i = document.querySelector('#sg-m-preview img'); return i && i.naturalWidth > 0; }, null, { timeout: 30000 });
      await expect(page.locator('#sg-m-measure')).toContainText(/24/, { timeout: 60000 });
      await expect(page.locator('#sg-m-measure')).toContainText(/16/, { timeout: 10000 });
      await G.shot(page, 'b4', '03-mobile-designer-preview-390');
      await noHScroll(page, 'designer preview');
      // size
      await expect(page.locator('#sg-art-w')).toBeVisible();
      const before = G.num(await G.price(page));
      expect(before).toBeCloseTo(17.68, 2);
      await page.locator('#sg-art-lock').uncheck().catch(() => {});
      await page.locator('#sg-art-w').fill('30');
      await page.locator('#sg-art-w').press('Enter');
      await page.waitForTimeout(4000);
      expect(G.num(await G.price(page))).toBeCloseTo(30 * 16 * 0.04604167, 2);
      await page.locator('#sg-art-w').fill('24');
      await page.locator('#sg-art-w').press('Enter');
      await page.waitForTimeout(4000);
      // options
      const pole = page.getByText('Top & Bottom').first();
      await pole.scrollIntoViewIfNeeded();
      await expect(pole).toBeVisible();
      await pole.click();
      await page.waitForTimeout(2500);
      expect(G.num(await G.price(page))).toBeCloseTo(31.68, 2);
      await G.shot(page, 'b4', '04-mobile-designer-options-390');
      await noHScroll(page, 'designer options');
      // add to cart
      const atc = page.locator('#sg-add-to-cart-btn');
      await expect(atc).toBeVisible();
      await atc.click();
      await page.waitForURL(/shopping-cart|checkout/, { timeout: 120000 });
      await page.waitForTimeout(1500);
      await noHScroll(page, 'cart after designer');
      await G.shot(page, 'b4', '05-mobile-cart-390');
      expect(errs.filter(e => /sg|SG|artbar|mobile/i.test(e))).toEqual([]);
    } finally { await ctx.close(); }
  });
});

test('one-click reorder keeps printing options and the original art', async ({ browser }) => {
  const OFX = (() => { try { return JSON.parse(fs.readFileSync('auth/reorder.json', 'utf8')); } catch (e) { return null; } })();
  test.skip(!OFX, 'reorder fixture missing');
  const ctx = await G.loginContext(browser, 'gp_test_b2c', { viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  page.on('dialog', d => d.accept());
  try {
    await page.goto(G.BASE + '/my-account/orders/', { waitUntil: 'domcontentloaded' });
    const btn = page.locator('.pcs-orders-reorder-btn').first();
    await expect(btn).toBeVisible({ timeout: 20000 });
    await G.shot(page, 'b4', '06-orders-reorder-button-1440');
    await btn.click();
    await expect(page.locator('#pcs-reorder-modal')).toBeVisible({ timeout: 60000 });
    await G.shot(page, 'b4', '06b-reorder-added-1440');
    await page.goto(G.BASE + '/shopping-cart/', { waitUntil: 'domcontentloaded' });
    const rows = await page.$$eval('.cart_item', els => els.map(e => ({ t: e.innerText.replace(/\s+/g, ' '), img: (e.querySelector('img') || {}).src || '' })));
    console.log('cart rows', JSON.stringify(rows).slice(0, 400));
    const hit = rows.find(r => /BANNER/i.test(r.t) && /Custom design/i.test(r.t) && /EDIT DESIGN/i.test(r.t));
    expect(hit, 'reordered banner line with its design attached').toBeTruthy();
    await G.shot(page, 'b4', '07-reorder-cart-1440');
  } finally { await ctx.close(); }
});

test('per-client products: visible to the company, hidden (404) for everybody else', async ({ browser }) => {
  test.skip(!FX, 'fixture missing');
  const URL = G.BASE + '/product/foamcore-single-sided/';
  const check = async (login, visible) => {
    const ctx = login ? await G.loginContext(browser, login, { viewport: { width: 1440, height: 900 } }) : await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    try {
      const r = await page.goto(URL, { waitUntil: 'domcontentloaded' });
      expect(r.status(), `${login || 'guest'} product page`).toBe(visible ? 200 : 404);
      const d = await page.goto(`${G.BASE}/create-your-own/?product_id=1263&view=m`, { waitUntil: 'domcontentloaded' });
      if (!visible) expect(d.status(), `${login || 'guest'} designer url`).toBe(404);
      await page.goto(G.BASE + '/product-category/rigid/', { waitUntil: 'domcontentloaded' });
      const names = await page.$$eval('li.product', lis => lis.map(l => l.innerText.replace(/\s+/g, ' ')));
      expect(names.some(n => /FOAMCORE/i.test(n)), `${login || 'guest'} category list`).toBe(visible);
      await page.goto(`${G.BASE}/?s=foamcore&post_type=product`, { waitUntil: 'domcontentloaded' });
      const sr = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
      expect(/FOAMCORE\s*[–-]\s*4/i.test(sr), `${login || 'guest'} search`).toBe(visible);
      if (login === 'gp_test_b2b') await G.shot(page, 'b4', '08-private-product-owner-search-1440');
    } finally { await ctx.close(); }
  };
  await check('gp_test_b2b', true);    // company owner
  await check('gp_test_staff', true);  // company member (retail account)
  await check('gp_test_b2c', false);   // retail account, not a member
  await check('gp_test_reseller', false);
  await check(null, false);
});

test('Image Zone: company members upload / list / use / delete, strangers see nothing', async ({ browser }) => {
  test.skip(!FX, 'fixture missing');
  const mk = async (login) => login ? G.loginContext(browser, login, { viewport: { width: 1440, height: 900 } }) : browser.newContext({ viewport: { width: 1440, height: 900 } });
  const owner = await mk('gp_test_b2b'), member = await mk('gp_test_staff'), stranger = await mk('gp_test_reseller'), guest = await mk(null);
  try {
    // owner uploads through the portal
    const po = await owner.newPage();
    po.on('dialog', d => d.accept());
    await po.goto(G.BASE + '/b2b-portal/?section=images', { waitUntil: 'load' });
    await po.waitForFunction(() => !/Loading your images/i.test(document.body.innerText), null, { timeout: 60000 }); // list arrives by AJAX
    const beforeIds = await po.$$eval('.pcs-iz-card', els => els.map(e => e.getAttribute('data-iz-id')));
    await po.setInputFiles('#pcs-iz-file', 'testimg/test-24x16in-150dpi.png');
    await po.locator('#pcs-iz-go').click();
    await po.waitForFunction(ids => [...document.querySelectorAll('.pcs-iz-card')].some(e => !ids.includes(e.getAttribute('data-iz-id'))), beforeIds, { timeout: 90000 });
    const id = (await po.$$eval('.pcs-iz-card', els => els.map(e => e.getAttribute('data-iz-id')))).find(x => !beforeIds.includes(x));
    const card = po.locator(`.pcs-iz-card[data-iz-id="${id}"]`);
    const txt = (await card.innerText()).replace(/\s+/g, ' ');
    console.log('image card:', id, txt);
    expect(txt).toMatch(/3600\s*[x×]\s*2400/);
    expect(txt).toMatch(/150/);
    await G.shot(po, 'b4', '09-imagezone-portal-1440');
    // member sees it, stranger and guest do not, and cannot fetch the file
    const pm = await member.newPage();
    await pm.goto(G.BASE + '/b2b-portal/?section=images', { waitUntil: 'load' });
    await expect(pm.locator(`.pcs-iz-card[data-iz-id="${id}"]`)).toBeVisible({ timeout: 30000 });
    const fileUrl = `${G.BASE}/wp-admin/admin-ajax.php?action=gp_iz_file&id=${id}`;
    expect((await member.request.get(fileUrl)).status()).toBe(200);
    expect((await owner.request.get(fileUrl + '&thumb=1')).status()).toBe(200);
    for (const [who, ctx] of [['stranger', stranger], ['guest', guest]]) {
      const r = await ctx.request.get(fileUrl);
      expect([200].includes(r.status()), `${who} must not get the file (status ${r.status()})`).toBeFalsy();
    }
    const ps = await stranger.newPage();
    await ps.goto(G.BASE + '/b2b-portal/?section=images', { waitUntil: 'load' });
    expect(await ps.locator(`.pcs-iz-card[data-iz-id="${id}"]`).count()).toBe(0);
    // the member uses the image from the designer upload tab
    const pd = await member.newPage();
    pd.on('dialog', d => d.accept());
    expect(await G.openDesigner(pd, 1168)).toBeTruthy();
    await G.tab(pd, 'Photos');
    await pd.locator('#sg-iz .sg-iz-head').click();
    const item = pd.locator(`#sg-iz .sg-iz-item[data-iz-id="${id}"]`);
    await expect(item).toBeVisible({ timeout: 30000 });
    await G.shot(pd, 'b4', '10-imagezone-designer-1440');
    await item.click();
    await pd.waitForFunction(`(() => { try { const s = angular.element(document.querySelector('[ng-controller=designCtrl]')).scope(); return s.stages[s.currentStage].canvas.getObjects().some(o => o.type === 'image'); } catch (e) { return false; } })()`, null, { timeout: 120000 });
    await pd.waitForTimeout(2500);
    expect(await G.dims(pd)).toBe('24x16');
    // delete from the portal
    await po.reload({ waitUntil: 'load' });
    const del = po.locator(`.pcs-iz-card[data-iz-id="${id}"] button, .pcs-iz-card[data-iz-id="${id}"] .pcs-iz-del`).filter({ hasText: /delete/i }).first();
    po.on('response', async r => { if (/admin-ajax/.test(r.url()) && r.request().method() === 'POST') console.log('[iz ajax]', r.status(), (await r.text().catch(() => '')).slice(0, 160)); });
    await del.click();
    await expect(po.locator(`.pcs-iz-card[data-iz-id="${id}"]`)).toHaveCount(0, { timeout: 20000 });
    expect((await owner.request.get(fileUrl)).status()).not.toBe(200);
  } finally { for (const c of [owner, member, stranger, guest]) await c.close(); }
});

test('company templates: save from the designer, list / rename / clone / delete in the portal, start an order from it', async ({ browser }) => {
  test.skip(!FX, 'fixture missing');
  test.setTimeout(420000);
  const mk = async (login) => G.loginContext(browser, login, { viewport: { width: 1440, height: 900 } });
  const owner = await mk('gp_test_b2b'), member = await mk('gp_test_staff'), stranger = await mk('gp_test_reseller');
  const NAME = 'GP e2e template ' + Date.now();
  try {
    const pd = await owner.newPage();
    pd.on('dialog', async d => { if (d.type() === 'prompt') await d.accept(NAME); else await d.accept(); });
    expect(await G.openDesigner(pd, 1168)).toBeTruthy();
    await G.tab(pd, 'Photos');
    await pd.setInputFiles('#tab-photo .form-upload input[type="file"], input[type=file]', 'testimg/test-24x16in-150dpi.png');
    await pd.waitForFunction(`(() => { try { const s = angular.element(document.querySelector('[ng-controller=designCtrl]')).scope(); return s.stages[s.currentStage].canvas.getObjects().some(o => o.type === 'image'); } catch (e) { return false; } })()`, null, { timeout: 120000 });
    await pd.waitForTimeout(2500);
    await G.tab(pd, 'PRINTING');
    await G.pickOption(pd, 'Top & Bottom');
    const priceBefore = G.num(await G.price(pd));
    expect(priceBefore).toBeCloseTo(31.68 * 0.9, 2); // owner: 10% company tier
    await expect(pd.locator('#sg-tpl-bar')).toBeVisible();
    await G.shot(pd, 'b4', '11-template-toolbar-1440');
    await pd.locator('#sg-tpl-save').click();
    await expect(pd.locator('#sg-tpl-bar')).toContainText('saved', { timeout: 40000 });
    // portal list
    const po = await owner.newPage();
    po.on('dialog', async d => { if (d.type() === 'prompt') await d.accept(d.defaultValue() + ' (renamed)'); else await d.accept(); });
    await po.waitForTimeout(3000);
    await po.goto(G.BASE + '/b2b-portal/?section=templates', { waitUntil: 'load' });
    const card = po.locator('.pcs-tp-card', { hasText: NAME }).first();
    await expect(card).toBeVisible({ timeout: 40000 });
    const id = await card.getAttribute('data-tpl-id');
    await G.shot(po, 'b4', '12-templates-portal-1440');
    // member sees it, stranger does not
    const pm = await member.newPage();
    await pm.goto(G.BASE + '/b2b-portal/?section=templates', { waitUntil: 'load' });
    await expect(pm.locator(`.pcs-tp-card[data-tpl-id="${id}"]`)).toBeVisible({ timeout: 40000 });
    const ps = await stranger.newPage();
    await ps.goto(G.BASE + '/b2b-portal/?section=templates', { waitUntil: 'load' });
    await ps.waitForTimeout(3000);
    expect(await ps.locator(`.pcs-tp-card[data-tpl-id="${id}"]`).count()).toBe(0);
    // start an order from the template: art, size and options come back
    const pe = await member.newPage();
    pe.on('dialog', d => d.accept());
    await pe.goto(`${G.BASE}/create-your-own/?product_id=1168&view=m&gp_tpl=${id}`, { waitUntil: 'load', timeout: 90000 });
    await pe.waitForFunction(() => window.nbOption && nbOption.initialed, null, { timeout: 60000 });
    await pe.waitForFunction(`(() => { try { const s = angular.element(document.querySelector('[ng-controller=designCtrl]')).scope(); return s.stages[s.currentStage].canvas.getObjects().some(o => o.type === 'image'); } catch (e) { return false; } })()`, null, { timeout: 120000 });
    await pe.waitForTimeout(4000);
    expect(await G.dims(pe)).toBe('24x16');
    expect(G.num(await G.price(pe)), 'template restores options (member 10%)').toBeCloseTo(31.68 * 0.9, 2);
    await G.shot(pe, 'b4', '13-order-from-template-1440');
    // clone, rename, delete
    await po.locator(`.pcs-tp-card[data-tpl-id="${id}"] button`, { hasText: /clone/i }).first().click();
    await expect(po.locator('.pcs-tp-card', { hasText: 'Copy of' })).toHaveCount(1, { timeout: 30000 });
    await po.locator(`.pcs-tp-card[data-tpl-id="${id}"] button`, { hasText: /rename/i }).first().click();
    await expect(po.locator('.pcs-tp-card', { hasText: '(renamed)' })).toHaveCount(1, { timeout: 30000 });
    for (const n of [0, 1]) {
      const c = po.locator('.pcs-tp-card', { hasText: NAME }).first();
      if (await c.count() === 0) break;
      await c.locator('button', { hasText: /delete/i }).first().click();
      await po.waitForTimeout(2500);
    }
    await expect(po.locator('.pcs-tp-card', { hasText: NAME })).toHaveCount(0, { timeout: 30000 });
  } finally { for (const c of [owner, member, stranger]) await c.close(); }
});

test('big file: instant client-side preview while the upload runs', async ({ page }) => {
  test.setTimeout(240000);
  page.on('dialog', d => d.accept());
  expect(await G.openDesigner(page, 1168)).toBeTruthy();
  for (const [file, label] of [['testimg/test-24x16in-150dpi.png', 'small'], ['testimg/big-130mb.png', '130 MB']]) {
    await G.tab(page, 'Photos');
    const t0 = Date.now();
    await page.setInputFiles('#tab-photo .form-upload input[type="file"], input[type=file]', file);
    await expect(page.locator('#sg-upload-preview')).toBeVisible({ timeout: 15000 });
    const secs = (Date.now() - t0) / 1000;
    const info = await page.evaluate(() => { const e = document.getElementById('sg-upload-preview'); const o = document.getElementById('sg-upload-progress'); return { w: e.width || e.naturalWidth || e.offsetWidth, h: e.height || e.naturalHeight || e.offsetHeight, txt: o ? o.innerText.replace(/\s+/g, ' ') : '' }; });
    console.log(label, 'preview after', secs.toFixed(1), 's', JSON.stringify(info));
    expect(info.w).toBeGreaterThan(40);
    expect(info.txt).toMatch(/px/i);
    expect(secs).toBeLessThan(15);
    await G.shot(page, 'b4', label === 'small' ? '14-preview-small-1440' : '15-preview-130mb-1440');
    if (label === 'small') { await page.waitForFunction(() => !document.getElementById('sg-upload-progress') || document.getElementById('sg-upload-progress').offsetWidth === 0, null, { timeout: 90000 }); await page.waitForTimeout(3000); }
  }
});

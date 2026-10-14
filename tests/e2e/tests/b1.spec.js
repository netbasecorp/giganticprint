// B1 acceptance: pricing + catalog. Screens -> shots/b1/ (desktop 1440 + mobile 390)
const { test, expect } = require('@playwright/test');
const G = require('../lib/gp');

test.describe.configure({ mode: 'serial' });
test.use({ viewport: { width: 1440, height: 900 } });
test.setTimeout(180000);
const US = /^\$\d{1,3}(,\d{3})*\.\d{2}$/;

test('top menu in Signs365 order, Adhesive instead of VINIL', async ({ page }) => {
  await page.goto(G.BASE + '/', { waitUntil: 'domcontentloaded' });
  const labels = await page.$$eval('.cat-nav-bar .cat-nav-label', els => els.map(e => e.textContent.trim().toLowerCase()));
  expect(labels).toEqual(['banner', 'rigid', 'adhesive', 'handheld', 'magnet', 'apparel', 'misc']);
  expect(await page.locator('.cat-nav-new-badge').count()).toBe(0);
  await G.shot(page, 'b1', '01-home-menu-1440');
});

test('old category slugs redirect', async ({ page }) => {
  for (const [from, to] of [['vinyl', 'adhesive'], ['magnetic-signsmagnetic-signs', 'magnet'], ['apparel-dtf', 'apparel']]) {
    await page.goto(`${G.BASE}/product-category/${from}/`, { waitUntil: 'domcontentloaded' });
    expect(page.url()).toContain(`/product-category/${to}/`);
    expect(await page.locator('body').getAttribute('class')).toMatch(/tax-product_cat/);
  }
});

test('empty categories show Coming soon', async ({ page }) => {
  for (const slug of ['handheld', 'misc']) {
    const r = await page.goto(`${G.BASE}/product-category/${slug}/`, { waitUntil: 'domcontentloaded' });
    expect(r.status()).toBe(200);
    await expect(page.locator('body')).toContainText(/coming soon/i);
  }
  await G.shot(page, 'b1', '02-category-coming-soon-1440');
});

test('catalog: From price is real and US formatted', async ({ page }) => {
  const expected = { banner: { 'banner-13oz-single-sided': 6.63, canvas: 14.94, hdpe: 4.5, poster: 6.0, 'nocurl-banner': 9.0, mesh: 10.98 }, rigid: { acrylic: 25.0 }, adhesive: { '3m-ij-35c-vinyl': 8.97 } };
  for (const cat of ['banner', 'rigid', 'adhesive', 'magnet', 'apparel']) {
    await page.goto(`${G.BASE}/product-category/${cat}/`, { waitUntil: 'domcontentloaded' });
    const rows = await page.$$eval('li.product', lis => lis.map(li => ({ href: (li.querySelector('a') || {}).href || '', price: (li.querySelector('.price .amount') || {}).textContent || '', text: (li.querySelector('.price') || {}).textContent || '' })));
    expect(rows.length).toBeGreaterThan(0);
    for (const r of rows) {
      expect(r.text, r.href).toMatch(/^\s*From/);
      expect(r.price.trim(), r.href).toMatch(US);
      expect(G.num(r.price), r.href).toBeGreaterThan(0);
      const slug = (r.href.match(/\/product\/([^/]+)/) || [])[1];
      if (expected[cat] && expected[cat][slug] != null) expect(G.num(r.price), slug).toBeCloseTo(expected[cat][slug], 2);
    }
    if (cat === 'banner') await G.shot(page, 'b1', '03-catalog-banner-from-price-1440', true);
  }
});

test('designer: 13oz tier fix, US format, pole pocket, rope, rush', async ({ browser }) => {
  const ctx = await G.loginContext(browser, 'gp_test_b2c', { viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  page.on('dialog', d => d.accept());
  try {
    expect(await G.openDesigner(page, 1168)).toBeTruthy();
    const p0 = await G.price(page);
    expect(p0).toMatch(US);
    expect(G.num(p0)).toBeCloseTo(6.63, 2);
    const cases = [[24, 16, 17.68], [36, 30, 49.73], [48, 24, 53.04], [60, 60, 165.75]];
    for (const [w, h, exp] of cases) {
      await G.setSize(page, w, h);
      expect(G.num(await G.price(page)), `${w}x${h}`).toBeCloseTo(exp, 2);
    }
    await G.setSize(page, 24, 16);
    await G.pickOption(page, 'Top & Bottom');
    expect(G.num(await G.price(page)), 'pole T&B 24x16').toBeCloseTo(17.68 + 14.0, 2);
    await G.pickOption(page, 'Add rope');
    expect(G.num(await G.price(page)), '+rope').toBeCloseTo(17.68 + 14.0 + 6.67, 2);
    await G.shot(page, 'b1', '04-designer-banner-pole-rope-1440');
    await G.pickOption(page, 'RUSH');
    expect(G.num(await G.price(page)), '+rush').toBeCloseTo((17.68 + 14.0 + 6.67) * 2, 2);
    await G.pickOption(page, 'Standard turnaround');
    await G.pickOption(page, 'No rope');
    await G.setSize(page, 48, 24);
    expect(G.num(await G.price(page)), '48x24 pole T&B').toBeCloseTo(53.04 + 18.0, 2);
    await G.pickOption(page, 'Top only');
    expect(G.num(await G.price(page)), '48x24 pole top only').toBeCloseTo(53.04 + 14.0, 2);
  } finally { await ctx.close(); }
});

test('designer: vinyl shows a real price after options load, no 0.00', async ({ page }) => {
  expect(await G.openDesigner(page, 1271)).toBeTruthy();
  const p = await G.price(page);
  expect(p).toMatch(US);
  expect(G.num(p)).toBeCloseTo(8.97, 2);
  expect(await page.locator('.await-for-options').isVisible().catch(() => false)).toBeFalsy();
  expect(await page.evaluate(() => window.NBDESIGNCONFIG && NBDESIGNCONFIG.nbdesigner_dimensions_unit)).toBe('in');
  await G.shot(page, 'b1', '05-designer-vinyl-1440');
});

test('designer: loading state is honest (no 0.00 while options load)', async ({ page }) => {
  await page.goto(`${G.BASE}/create-your-own/?product_id=1321&view=m`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1500);
  const early = await page.evaluate(() => (document.getElementById('sg-total-price') || {}).textContent || '');
  expect(early).not.toMatch(/0[.,]00/);
  await G.waitReady(page);
  await G.killTour(page);
  expect(G.num(await G.price(page))).toBeCloseTo(37.5, 2);
});

test('tiers: retail 0 / B2B 10 / reseller 20 in the designer', async ({ browser }) => {
  const want = { gp_test_b2c: 0, gp_test_b2b: 10, gp_test_reseller: 20 };
  for (const [login, disc] of Object.entries(want)) {
    const ctx = await G.loginContext(browser, login, { viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    try {
      await page.goto(`${G.BASE}/create-your-own/?product_id=1168&view=m`, { waitUntil: 'domcontentloaded' });
      const d = await page.evaluate(() => ({ d: window.sg_b2b_discount, t: window.sg_b2b_tier, l: window.sg_b2b_label }));
      expect(Number(d.d), `${login} ${JSON.stringify(d)}`).toBe(disc);
    } finally { await ctx.close(); }
  }
});

test('cart price equals designer price (server recalculation)', async ({ browser }) => {
  const ctx = await G.loginContext(browser, 'gp_test_b2c', { viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  page.on('dialog', d => d.accept());
  try {
    expect(await G.openDesigner(page, 1168)).toBeTruthy();
    await G.setSize(page, 24, 16);
    await G.pickOption(page, 'Top & Bottom');
    const designer = G.num(await G.price(page));
    expect(designer).toBeCloseTo(31.68, 2);
    await page.locator('#sg-add-to-cart-btn').click({ force: true });
    await page.waitForURL(/cart/, { timeout: 120000 }).catch(() => {});
    await page.goto(G.BASE + '/shopping-cart/', { waitUntil: 'domcontentloaded' });
    const txt = await page.locator('.cart_item').first().innerText();
    const amounts = (txt.match(/\$\s?[\d,]+\.\d{2}/g) || []).map(G.num);
    expect(amounts.some(a => Math.abs(a - 31.68) < 0.011), txt.replace(/\s+/g, ' ')).toBeTruthy();
    await G.shot(page, 'b1', '06-cart-banner-pole-1440');
  } finally { await ctx.close(); }
});

test('mobile 390: catalog and home screenshots, report horizontal overflow', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const page = await ctx.newPage();
  try {
    for (const [name, url] of [['home', '/'], ['catalog-banner', '/product-category/banner/']]) {
      await page.goto(G.BASE + url, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(1200);
      const sw = await page.evaluate(() => document.documentElement.scrollWidth);
      await G.shot(page, 'b1', `07-${name}-390`);
      console.log(`mobile ${name} scrollWidth=${sw}`);
    }
  } finally { await ctx.close(); }
});

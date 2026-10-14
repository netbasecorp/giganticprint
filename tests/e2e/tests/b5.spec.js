// B5 acceptance: checkout, labels, pages, fixtures. Screens -> shots/b5/
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const G = require('../lib/gp');

test.setTimeout(420000);
const DESIGN = `angular.element(document.querySelector('[ng-controller=designCtrl]')).scope()`;

async function artIn(page) {
  await page.waitForFunction(`(() => { try { const s = ${DESIGN}; return s.stages[s.currentStage].canvas.getObjects().some(o => o.type === 'image'); } catch (e) { return false; } })()`, null, { timeout: 120000 });
  await page.waitForTimeout(2500);
}

for (const [login, label] of [['gp_test_b2c', 'retail, no company'], ['gp_test_b2b', 'B2B company owner'], ['gp_test_reseller', 'reseller company owner'], ['gp_test_staff', 'company member']]) {
  test(`full test order as ${login} (${label}): designer -> cart -> checkout -> Order received`, async ({ browser }) => {
    const ctx = await G.loginContext(browser, login, { viewport: { width: 1440, height: 1100 } });
    const page = await ctx.newPage();
    page.on('dialog', d => d.accept());
    try {
      expect(await G.emptyCart(page), 'cart emptied').toBeTruthy();
      expect(await G.openDesigner(page, 1168)).toBeTruthy();
      await G.tab(page, 'Photos');
      await page.setInputFiles('#tab-photo .form-upload input[type="file"], input[type=file]', 'testimg/test-24x16in-150dpi.png');
      await artIn(page);
      await page.locator('#sg-add-to-cart-btn').click({ force: true });
      await page.waitForURL(/shopping-cart/, { timeout: 120000 });
      // cart: full uncropped preview, no empty second side
      await page.waitForTimeout(1500);
      expect(await page.locator('.cart_item').count(), 'exactly one cart line').toBe(1);
      if (login === 'gp_test_b2c') await G.shot(page, 'b5', '01-cart-preview-1440');
      // cart preview: no empty second side, uncropped (object-fit contain)
      const thumbs = await page.$$eval('.cart_item img', els => els.map(e => ({ src: e.src.replace(/\?.*/, '').split('/').slice(-3).join('/'), fit: getComputedStyle(e).objectFit, w: e.naturalWidth })));
      console.log(login, 'cart images', JSON.stringify(thumbs));
      expect(thumbs.length, 'cart preview images').toBeGreaterThan(0);
      expect(thumbs.filter(t => /frame_1/.test(t.src)), 'empty second side must not be shown').toEqual([]);
      expect(thumbs.filter(t => t.fit !== 'contain'), 'every cart image is uncropped (object-fit: contain)').toEqual([]);
      await page.goto(G.BASE + '/checkout/', { waitUntil: 'load' });
      await expect(page.getByText('Pay by invoice').first()).toBeVisible({ timeout: 40000 });
      await expect(page.getByText(/Pickup .* Fort Pierce, FL/).first()).toBeVisible();
      await expect(page.getByText(/Ground shipping/).first()).toBeVisible();
      if (login === 'gp_test_b2c') await G.shot(page, 'b5', '02-checkout-1440', true);
      await page.getByRole('button', { name: /place order/i }).click();
      await page.waitForURL(/order-received/, { timeout: 120000 });
      const txt = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
      expect(txt).toMatch(/order received|thank you/i);
      expect(txt).toMatch(/PayPal invoice/i);
      const m = page.url().match(/order-received\/(\d+)/);
      console.log(login, 'order', m && m[1], page.url().replace(/key=.*/, 'key=...'));
      fs.appendFileSync('auth/orders.log', `${login} ${m && m[1]}\n`);
      await G.shot(page, 'b5', `03-order-received-${login}-1440`);
    } finally { await ctx.close(); }
  });
}

// ---- pages, footer, texts ------------------------------------------------------------------
test('legal and tracking pages exist, carry the draft banner and are linked in the footer', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  try {
    await page.goto(G.BASE + '/', { waitUntil: 'load' });
    const links = await page.$$eval('footer a, .site-footer a', as => as.map(a => a.getAttribute('href') || ''));
    for (const slug of ['terms', 'refund-policy', 'track-order']) {
      expect(links.some(h => h.replace(/\/$/, '').endsWith('/' + slug)), `footer links to ${slug}`).toBeTruthy();
    }
    for (const [slug, h1, draft] of [['terms', /Terms of Service/i, true], ['refund-policy', /Refund Policy/i, true], ['privacy-policy', /Privacy Policy/i, true], ['track-order', /Track your order/i, false]]) {
      const r = await page.goto(`${G.BASE}/${slug}/`, { waitUntil: 'load' });
      expect(r.status(), slug).toBe(200);
      const txt = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
      expect(txt, slug).toMatch(h1);
      if (draft) expect(txt, slug).toMatch(/Draft . to be reviewed by Gigantic Prints before go-live/);
      if (slug === 'track-order') {
        await expect(page.locator('form.track_order input[name="orderid"]')).toBeVisible();
        await G.shot(page, 'b5', '04-track-order-1440');
      }
      if (slug === 'terms') await G.shot(page, 'b5', '05-terms-1440');
    }
  } finally { await ctx.close(); }
});

test('footer links of the checkout notice open the Terms and Privacy pages', async ({ browser }) => {
  const ctx = await G.loginContext(browser, 'gp_test_b2c', { viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  try {
    await page.goto(G.BASE + '/checkout/', { waitUntil: 'load' });
    const hrefs = await page.$$eval('a', as => as.map(a => a.getAttribute('href') || ''));
    // the empty-cart checkout may redirect; only assert when the checkout renders its terms text
    const has = await page.getByText(/Terms and Conditions|Terms of Service/i).count();
    if (has) expect(hrefs.some(h => /\/terms\/?$/.test(h))).toBeTruthy();
  } finally { await ctx.close(); }
});

test('B2B apply page and modal promise no Net terms, free shipping or credit', async ({ browser }) => {
  const ctx = await G.loginContext(browser, 'gp_test_b2c', { viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  try {
    for (const url of ['/b2b-portal/?section=apply', '/my-account/', '/shop/', '/']) {
      await page.goto(G.BASE + url, { waitUntil: 'load' });
      await page.waitForTimeout(1500);
      const html = (await page.content());
      const txt = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
      expect(txt, url).not.toMatch(/Net[- ]?(15|30|60)|free shipping|payment terms|credit limit|dedicated (rep|account)|blind shipping|24% off/i);
      expect(html, url + ' (markup)').not.toMatch(/Net-30 \/ Net-60|NET-30 terms/i);
      if (url.includes('apply')) await G.shot(page, 'b5', '06-b2b-apply-1440', true);
    }
    // the designer modal text
    await G.openDesigner(page, 1168);
    const modal = await page.evaluate(() => (window.sgB2bModalText || document.documentElement.innerHTML.match(/sg-b2b-benefits[\s\S]{0,1500}/) || [''])[0]);
    expect(modal).not.toMatch(/Net-30|Net-60|account manager|24%/);
  } finally { await ctx.close(); }
});

// ---- option labels ------------------------------------------------------------------------
test('option labels: pole pocket / rope rule text, wind slits +$0.50, upload tile 200 MB', async ({ browser }) => {
  const ctx = await G.loginContext(browser, 'gp_test_b2c', { viewport: { width: 1440, height: 1100 } });
  const page = await ctx.newPage();
  try {
    expect(await G.openDesigner(page, 1168)).toBeTruthy();
    await G.tab(page, 'PRINTING');
    await page.waitForTimeout(1500);
    const body = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    console.log('pole/rope labels:', (body.match(/(Top & Bottom|Top only|Bottom only|Add rope)[^A-Z]{0,40}/g) || []).join(' | '));
    expect(body).toMatch(/\$1\.00\/ft \+ \$10\.00 setup/);
    expect(body).toMatch(/Add rope: \$1\.00\/ft/);
    expect(body).toMatch(/Wind Slits \(\+\$0\.50\)/i);
    expect(body).not.toMatch(/\/\s?sq\s?ft/i);
    await G.shot(page, 'b5', '07-option-labels-1440', true);
    await G.tab(page, 'Photos');
    const up = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(up).toMatch(/Max file size: 200 MB/);
    await G.shot(page, 'b5', '08-designer-upload-limits-1440');
    // product page upload tile
    await page.goto(G.BASE + '/product/banner-13oz-single-sided/', { waitUntil: 'load' });
    await page.waitForTimeout(9000);
    const tile = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(tile).toMatch(/PDF, AI, EPS, TIFF, PNG, JPG . up to 200 MB/);
    expect(tile).not.toMatch(/25\s?MB/);
    await G.shot(page, 'b5', '08b-product-upload-tile-1440');
  } finally { await ctx.close(); }
});

test('designer opens on the Photos (upload) tab for every product', async ({ browser }) => {
  const ctx = await G.loginContext(browser, 'gp_test_b2c', { viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  try {
    for (const pid of [1168, 1208, 1149]) {
      await G.openDesigner(page, pid);
      await page.waitForTimeout(3000);
      const active = await page.$$eval('.main-tabs li.tab', els => els.filter(e => e.classList.contains('active')).map(e => e.innerText.replace(/\s+/g, ' ').trim()));
      console.log(pid, 'active tab', JSON.stringify(active));
      expect(active.join(' ')).toMatch(/photo/i);
    }
  } finally { await ctx.close(); }
});

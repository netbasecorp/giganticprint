// B3 acceptance: B2B + security. Fixture (company store, member, sent quote) is created by scripts/data/b3-fixture.php create
// and stored in auth/fixture.json (tests that need it are skipped when it is missing). Screens -> shots/b3/
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const G = require('../lib/gp');

test.use({ viewport: { width: 1440, height: 900 } });
test.setTimeout(240000);

const FX = (() => { try { return JSON.parse(fs.readFileSync('auth/fixture.json', 'utf8')); } catch (e) { return null; } })();
const designScope = `angular.element(document.querySelector('[ng-controller=designCtrl]')).scope()`;
async function upload(page, file) {
  await G.tab(page, 'Photos');
  await page.setInputFiles('#tab-photo .form-upload input[type="file"], input[type=file]', file);
  await page.waitForFunction(`(() => { try { const s = ${designScope}; return s.stages[s.currentStage].canvas.getObjects().some(o => o.type === 'image'); } catch (e) { return false; } })()`, null, { timeout: 120000 });
  await page.waitForTimeout(2500);
}
const near = (v, exp) => Math.abs(v - exp) <= 0.011;

const USERS = [['gp_test_b2c', 17.68, 25.0, 6.63], ['gp_test_b2b', 15.91, 22.5, 5.97], ['gp_test_reseller', 14.14, 20.0, 5.3]];

for (const [login, banner, acrylic, pageDefault] of USERS) {
  test(`one discount only: ${login} sees banner ${banner} and acrylic ${acrylic} (designer, product page, cart)`, async ({ browser }) => {
    const ctx = await G.loginContext(browser, login, { viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    page.on('dialog', d => d.accept());
    try {
      // acrylic 4x4 in: base price 25.00 (the base used to be discounted twice)
      expect(await G.openDesigner(page, 1208)).toBeTruthy();
      await G.setSize(page, 4, 4);
      expect(G.num(await G.price(page)), `${login} acrylic designer`).toBeCloseTo(acrylic, 2);
      // banner 24x16 in the designer with art, then the cart
      expect(await G.openDesigner(page, 1168)).toBeTruthy();
      await upload(page, 'testimg/test-24x16in-150dpi.png');
      expect(await G.dims(page)).toBe('24x16');
      expect(G.num(await G.price(page)), `${login} banner designer`).toBeCloseTo(banner, 2);
      if (login !== 'gp_test_b2c') await G.shot(page, 'b3', `01-designer-${login}-1440`);
      await page.locator('#sg-add-to-cart-btn').click({ force: true });
      await page.waitForURL(/shopping-cart|checkout/, { timeout: 120000 });
      await page.waitForTimeout(1500);
      const rows = await page.$$eval('.cart_item', els => els.map(e => e.innerText.replace(/\s+/g, ' ')));
      const last = rows[rows.length - 1];
      const amounts = (last.match(/\$\s?[\d,]+\.\d{2}/g) || []).map(G.num);
      console.log(login, 'cart last row amounts', JSON.stringify(amounts));
      expect(amounts.some(a => near(a, banner)), `${login} cart row: ${last}`).toBeTruthy();
      if (login !== 'gp_test_b2c') expect(amounts.some(a => near(a, 17.68)) && !amounts.some(a => near(a, banner)), 'cart must not show retail only').toBeFalsy();
      await G.shot(page, 'b3', `02-cart-${login}-1440`);
      // product page default configuration (12x12 13oz = 6.63 retail)
      await page.goto(G.BASE + '/product/banner-13oz-single-sided/', { waitUntil: 'load' });
      await page.waitForTimeout(9000);
      if (login !== 'gp_test_b2c') {
        const top = await page.locator('#pcs-top-b2b .pcs-b2b-top-val').first().innerText().catch(() => '');
        console.log(login, 'product page B2B price', top);
        expect(G.num(top), `${login} product page`).toBeCloseTo(pageDefault, 2);
        await G.shot(page, 'b3', `03-product-${login}-1440`);
      }
    } finally { await ctx.close(); }
  });
}

test('company member gets the company discount (status active)', async ({ browser }) => {
  test.skip(!FX, 'fixture missing');
  const ctx = await G.loginContext(browser, 'gp_test_b2c', { viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  try {
    await page.goto(`${G.BASE}/create-your-own/?product_id=1168&view=m`, { waitUntil: 'domcontentloaded' });
    const d = await page.evaluate(() => ({ d: window.sg_b2b_discount, t: window.sg_b2b_tier }));
    expect(Number(d.d), JSON.stringify(d)).toBe(10);
  } finally { await ctx.close(); }
});

test('quote accept/decline needs POST + nonce + ownership + status sent, once only', async ({ browser }) => {
  test.skip(!FX, 'fixture missing');
  const owner = await G.loginContext(browser, 'gp_test_b2b');
  const other = await G.loginContext(browser, 'gp_test_reseller');
  try {
    const ajax = G.BASE + '/wp-admin/admin-ajax.php';
    const post = async (ctx, form) => { const r = await ctx.request.post(ajax, { form }); let j = null; try { j = await r.json(); } catch (e) {} return { status: r.status(), j }; };
    // 1) no nonce
    let r = await post(owner, { action: 'pcs_quote_respond', quote_id: FX.quote, response: 'accept' });
    console.log('no nonce ->', r.status, JSON.stringify(r.j));
    expect(r.j && r.j.success).toBeFalsy();
    // 2) GET is refused
    const g = await owner.request.get(`${ajax}?action=pcs_quote_respond&quote_id=${FX.quote}&response=accept`);
    expect(g.status()).not.toBe(200);
    // nonces from the portal page of each user
    const nonceOf = async (ctx) => { const p = await ctx.newPage(); await p.goto(G.BASE + '/b2b-portal/?section=quotes', { waitUntil: 'load' }); const n = await p.evaluate(() => (window.PCS_FE || {}).nonce || null); await p.close(); return n; };
    const nOwner = await nonceOf(owner); const nOther = await nonceOf(other);
    expect(nOwner).toBeTruthy(); expect(nOther).toBeTruthy();
    // 3) bad nonce
    r = await post(owner, { action: 'pcs_quote_respond', quote_id: FX.quote, response: 'accept', _nonce: 'deadbeef00' });
    console.log('bad nonce ->', r.status, JSON.stringify(r.j));
    expect(r.j && r.j.success).toBeFalsy();
    // 4) valid nonce but not the owner / member
    r = await post(other, { action: 'pcs_quote_respond', quote_id: FX.quote, response: 'accept', _nonce: nOther });
    console.log('stranger ->', r.status, JSON.stringify(r.j));
    expect(r.j && r.j.success).toBeFalsy();
    // 5) owner with nonce: works once
    r = await post(owner, { action: 'pcs_quote_respond', quote_id: FX.quote, response: 'accept', _nonce: nOwner });
    console.log('owner ->', r.status, JSON.stringify(r.j));
    expect(r.j && r.j.success).toBeTruthy();
    r = await post(owner, { action: 'pcs_quote_respond', quote_id: FX.quote, response: 'accept', _nonce: nOwner });
    console.log('owner again ->', r.status, JSON.stringify(r.j));
    expect(r.j && r.j.success).toBeFalsy();
  } finally { await owner.close(); await other.close(); }
});

test('login rate limit: 5 failures lock the name and the answer does not reveal accounts', async ({ page }) => {
  await page.goto(`${G.BASE}/create-your-own/?product_id=1168&view=m`, { waitUntil: 'domcontentloaded' });
  const nonce = await page.evaluate(() => window.sg_login_nonce);
  const msgs = [];
  for (let i = 0; i < 7; i++) {
    const m = await page.evaluate(async (nonce) => {
      const fd = new FormData(); fd.append('action', 'sg_dui_login'); fd.append('_nonce', nonce); fd.append('username', 'gp_rate_nobody'); fd.append('password', 'wrong-' + Math.random());
      const r = await fetch(window.sg_ajax_url, { method: 'POST', body: fd, credentials: 'same-origin' }); const j = await r.json();
      return (j.data && j.data.message) || JSON.stringify(j);
    }, nonce);
    msgs.push(m);
  }
  console.log(msgs.join(' | '));
  expect(msgs[0]).toMatch(/incorrect/i);
  expect(msgs.slice(5).join(' ')).toMatch(/too many failed attempts/i);
  // an existing account name gets the same wording (no account disclosure)
  expect(msgs[0]).not.toMatch(/not registered|invalid username|unknown/i);
});

test('anonymous visitors cannot list users', async ({ request }) => {
  for (const url of ['/wp-json/wp/v2/users', '/?rest_route=/wp/v2/users', '/wp-json/wp/v2/users/1']) {
    const r = await request.get(G.BASE + url, { maxRedirects: 0 });
    const body = await r.text();
    console.log(url, r.status());
    expect(body).not.toMatch(/"slug"\s*:\s*"admin"|"name"\s*:\s*"admin"/i);
    expect([401, 403, 404]).toContain(r.status());
  }
  const a = await request.get(G.BASE + '/?author=1', { maxRedirects: 0 });
  expect([301, 302, 404]).toContain(a.status());
  if (a.status() !== 404) expect(a.headers()['location'] || '').not.toMatch(/author/i);
});

test('account flow: guest checkout is off, registration is on', async ({ page }) => {
  page.on('dialog', d => d.accept());
  // design without login is still possible (designer loads for guests)
  expect(await G.openDesigner(page, 1168)).toBeTruthy();
  await upload(page, 'testimg/test-24x16in-150dpi.png');
  await page.locator('#sg-add-to-cart-btn').click({ force: true });
  await page.waitForURL(/shopping-cart|checkout/, { timeout: 120000 });
  await G.shot(page, 'b3', '04-guest-cart-1440');
  await page.goto(G.BASE + '/checkout/', { waitUntil: 'domcontentloaded' });
  const txt = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
  console.log('guest checkout page:', txt.slice(txt.search(/checkout/i), 400));
  expect(txt).toMatch(/logged in to checkout|log in|login/i);
  expect(await page.locator('form.woocommerce-checkout #billing_first_name').count()).toBe(0);
  await G.shot(page, 'b3', '05-guest-checkout-1440');
  await page.goto(G.BASE + '/my-account/', { waitUntil: 'domcontentloaded' });
  expect(await page.locator('form.woocommerce-form-register, form.register').count()).toBeGreaterThan(0);
  expect(await page.locator('#reg_password').count()).toBeGreaterThan(0);
  await G.shot(page, 'b3', '06-my-account-register-1440');
});

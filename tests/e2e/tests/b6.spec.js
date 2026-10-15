// B6 acceptance: demo orders through the real flow with realistic art, Image Zone thumbnail, Approval Queue hidden. Screens -> shots/b6/
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const G = require('../lib/gp');

test.setTimeout(420000);
const DESIGN = `angular.element(document.querySelector('[ng-controller=designCtrl]')).scope()`;
async function artIn(page) {
  await page.waitForFunction(`(() => { try { const s = ${DESIGN}; return s.stages[s.currentStage].canvas.getObjects().some(o => o.type === 'image'); } catch (e) { return false; } })()`, null, { timeout: 120000 });
  await page.waitForTimeout(2500);
}

const DEMO = [
  ['gp_test_b2c', 'testimg/demo-open-house.png', 'retail, no company'],
  ['gp_test_b2b', 'testimg/demo-grand-opening.png', 'Demo Signs Co owner'],
  ['gp_test_reseller', 'testimg/demo-trade-show.png', 'Demo Reseller Co owner'],
  ['gp_test_staff', 'testimg/demo-summer-sale.png', 'Demo Signs Co member'],
];
for (const [login, art, label] of DEMO) {
  test(`demo order as ${login} (${label}): upload art -> cart -> checkout -> Order received`, async ({ browser }) => {
    const ctx = await G.loginContext(browser, login, { viewport: { width: 1440, height: 1100 } });
    const page = await ctx.newPage();
    page.on('dialog', d => d.accept());
    try {
      expect(await G.emptyCart(page), 'cart emptied').toBeTruthy();
      expect(await G.openDesigner(page, 1168)).toBeTruthy();
      await G.tab(page, 'Photos');
      await page.setInputFiles('#tab-photo .form-upload input[type="file"], input[type=file]', art);
      await artIn(page);
      await G.shot(page, 'b6', `01-demo-art-in-designer-${login}`);
      await page.locator('#sg-add-to-cart-btn').click({ force: true });
      await page.waitForURL(/shopping-cart/, { timeout: 120000 });
      await page.waitForTimeout(1500);
      expect(await page.locator('.cart_item').count(), 'exactly one cart line').toBe(1);
      await page.goto(G.BASE + '/checkout/', { waitUntil: 'load' });
      await expect(page.getByText('Pay by invoice').first()).toBeVisible({ timeout: 40000 });
      await page.getByRole('button', { name: /place order/i }).click();
      await page.waitForURL(/order-received/, { timeout: 120000 });
      const m = page.url().match(/order-received\/(\d+)/);
      console.log(login, 'demo order', m && m[1]);
      fs.appendFileSync('auth/demo-orders.log', `${login} ${m && m[1]}\n`);
    } finally { await ctx.close(); }
  });
}

test('Image Zone thumbnails are visible without scrolling or waiting (no lazy-load deadlock)', async ({ browser }) => {
  for (const login of ['gp_test_b2b', 'gp_test_staff']) {
    const ctx = await G.loginContext(browser, login, { viewport: { width: 1440, height: 1000 } });
    const page = await ctx.newPage();
    try {
      await page.goto(G.BASE + '/b2b-portal/?section=images', { waitUntil: 'load' });
      await page.waitForSelector('.pcs-iz-thumb img', { timeout: 30000 });
      await page.waitForFunction(() => { const i = document.querySelector('.pcs-iz-thumb img'); return i && i.complete && i.naturalWidth > 0; }, null, { timeout: 15000 });
      const r = await page.$eval('.pcs-iz-thumb img', e => ({ w: e.getBoundingClientRect().width, h: e.getBoundingClientRect().height, nw: e.naturalWidth, lazy: e.loading }));
      console.log(login, 'image zone thumb', JSON.stringify(r));
      expect(r.nw).toBeGreaterThan(0);
      expect(r.w).toBeGreaterThan(50);
      expect(r.lazy).not.toBe('lazy');
      if (login === 'gp_test_b2b') await G.shot(page, 'b6', '02-image-zone-thumbnail-1440');
    } finally { await ctx.close(); }
  }
});

test('Approval Queue is hidden from the portal (menu and direct URL)', async ({ browser }) => {
  const ctx = await G.loginContext(browser, 'gp_test_b2b', { viewport: { width: 1440, height: 1000 } });
  const page = await ctx.newPage();
  try {
    await page.goto(G.BASE + '/b2b-portal/', { waitUntil: 'load' });
    const nav = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(nav).not.toMatch(/Approval Queue/i);
    await G.shot(page, 'b6', '03-portal-menu-1440');
    await page.goto(G.BASE + '/b2b-portal/?section=approvals', { waitUntil: 'load' });
    const txt = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(txt).not.toMatch(/Approval Queue|Coming soon/i);
    expect(txt).toMatch(/Overview/);
  } finally { await ctx.close(); }
});

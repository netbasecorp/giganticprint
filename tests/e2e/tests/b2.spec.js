// B2 acceptance: upload -> preview. Screens -> shots/b2/ (desktop 1440 + mobile 390)
const { test, expect } = require('@playwright/test');
const G = require('../lib/gp');

test.use({ viewport: { width: 1440, height: 900 } });
test.setTimeout(240000);

const designScope = `angular.element(document.querySelector('[ng-controller=designCtrl]')).scope()`;
async function art(page) {
  return page.evaluate(`(() => { const s = ${designScope}; const c = s.stages[s.currentStage].canvas; const objs = c.getObjects().filter(o => o.type === 'image');
    const o = objs[0]; const z = c.getZoom ? c.getZoom() : 1;
    return { n: c.getObjects().length, imgs: objs.length, cw: c.getWidth() / z, ch: c.getHeight() / z,
      w: o ? o.getScaledWidth() : 0, h: o ? o.getScaledHeight() : 0, l: o ? o.left : 0, t: o ? o.top : 0, nw: o ? (o._element && o._element.naturalWidth) : 0, nh: o ? (o._element && o._element.naturalHeight) : 0 }; })()`);
}
async function upload(page, file) {
  await G.tab(page, 'Photos');
  const t0 = Date.now();
  await page.setInputFiles('#tab-photo .form-upload input[type="file"], input[type=file]', file);
  return t0;
}
async function waitArt(page, ms = 90000) {
  await page.waitForFunction(`(() => { try { const s = ${designScope}; return s.stages[s.currentStage].canvas.getObjects().some(o => o.type === 'image'); } catch (e) { return false; } })()`, null, { timeout: ms });
  await page.waitForTimeout(2500);
}
const close = (a, b, tol = 0.02) => Math.abs(a - b) <= Math.max(0.5, Math.abs(b) * tol);

test('upload is placed on the canvas immediately, size from pixels/DPI, price updates', async ({ page }) => {
  page.on('dialog', d => d.accept());
  expect(await G.openDesigner(page, 1168)).toBeTruthy();
  const t0 = await upload(page, 'testimg/test-24x16in-150dpi.png');
  await waitArt(page);
  const secs = (Date.now() - t0) / 1000;
  console.log(`art placed ${secs.toFixed(1)}s after choosing the file`);
  expect(secs).toBeLessThan(45);
  const a = await art(page);
  expect(a.imgs).toBe(1);
  expect(await G.dims(page)).toBe('24x16');
  expect(G.num(await G.price(page))).toBeCloseTo(17.68, 2);
  await expect(page.locator('#sg-artbar')).toBeVisible();
  await expect(page.locator('#sg-art-actual')).toContainText(/24\D+16/);
  await expect(page.locator('[data-sg-art="stretch"]')).toHaveClass(/sg-active/);
  expect(await page.locator('#sg-lowres-warning').isVisible().catch(() => false)).toBeFalsy();
  expect(close(a.w, a.cw) && close(a.h, a.ch)).toBeTruthy();
  await G.shot(page, 'b2', '01-upload-placed-1440');
});

test('fit modes and same-screen size edit keep the art and update the price', async ({ page }) => {
  page.on('dialog', d => d.accept());
  expect(await G.openDesigner(page, 1168)).toBeTruthy();
  await upload(page, 'testimg/test-24x16in-150dpi.png');
  await waitArt(page);
  // keep proportions: W 48 -> H 32, price of 48x32 (1536 sq in * 0.04604167)
  await page.locator('#sg-art-w').fill('48');
  await page.locator('#sg-art-w').press('Enter');
  await page.waitForTimeout(3500);
  expect(await page.locator('#sg-art-h').inputValue()).toMatch(/^32(\.0+)?$/);
  expect(await G.dims(page)).toMatch(/^48x32/);
  expect(G.num(await G.price(page))).toBeCloseTo(70.72, 2);
  let a = await art(page);
  expect(a.imgs).toBe(1);
  expect(close(a.w, a.cw) && close(a.h, a.ch)).toBeTruthy(); // stretch re-applied after the resize
  // unlock, make it 36 x 16: stretch distorts, fit keeps the ratio, center keeps the physical size
  await page.locator('#sg-art-lock').uncheck();
  await page.locator('#sg-art-w').fill('36');
  await page.locator('#sg-art-w').press('Enter');
  await page.waitForTimeout(2500);
  await page.locator('#sg-art-h').fill('16');
  await page.locator('#sg-art-h').press('Enter');
  await page.waitForTimeout(3500);
  expect(G.num(await G.price(page))).toBeCloseTo(26.52, 2);
  await page.locator('[data-sg-art="stretch"]').click();
  await page.waitForTimeout(1200);
  a = await art(page);
  expect(close(a.w, a.cw) && close(a.h, a.ch)).toBeTruthy();
  await G.shot(page, 'b2', '02-stretch-36x16-1440');
  await page.locator('[data-sg-art="fit"]').click();
  await page.waitForTimeout(1200);
  a = await art(page);
  expect(close(a.w / a.h, 1.5, 0.03)).toBeTruthy();             // art ratio 3600:2400 kept
  expect(a.w <= a.cw + 1 && a.h <= a.ch + 1).toBeTruthy();       // inside the print area
  expect(close(a.l + a.w / 2, a.cw / 2, 0.03) || close(a.l, a.cw / 2, 0.03)).toBeTruthy();
  await G.shot(page, 'b2', '03-fit-36x16-1440');
  await page.locator('[data-sg-art="center"]').click();
  await page.waitForTimeout(1200);
  a = await art(page);
  expect(close(a.w / a.h, 1.5, 0.03)).toBeTruthy();
  await expect(page.locator('[data-sg-art="center"]')).toHaveClass(/sg-active/);
  await G.shot(page, 'b2', '04-center-36x16-1440');
});

test('low resolution is warned about (not blocked)', async ({ page }) => {
  page.on('dialog', d => d.accept());
  expect(await G.openDesigner(page, 1168)).toBeTruthy();
  await upload(page, 'testimg/test-lowres-72dpi.png');
  await waitArt(page);
  await expect(page.locator('#sg-lowres-warning')).toBeVisible();
  await expect(page.locator('#sg-lowres-warning')).toContainText(/7\d\s*dpi/i);
  await G.shot(page, 'b2', '05-lowres-warning-1440');
  // a good file stretched far too large warns as well, and the warning goes away again
  await page.reload({ waitUntil: 'load' });
  await G.waitReady(page); await G.killTour(page);
  await upload(page, 'testimg/test-24x16in-150dpi.png');
  await waitArt(page);
  expect(await page.locator('#sg-lowres-warning').isVisible().catch(() => false)).toBeFalsy();
  await page.locator('#sg-art-w').fill('48');
  await page.locator('#sg-art-w').press('Enter');
  await page.waitForTimeout(3500);
  await expect(page.locator('#sg-lowres-warning')).toBeVisible();
  await page.locator('#sg-art-w').fill('24');
  await page.locator('#sg-art-w').press('Enter');
  await page.waitForTimeout(3500);
  expect(await page.locator('#sg-lowres-warning').isVisible().catch(() => false)).toBeFalsy();
});

test('visible upload progress for a 30 MB file, then the art appears', async ({ page }) => {
  page.on('dialog', d => d.accept());
  expect(await G.openDesigner(page, 1168)).toBeTruthy();
  await upload(page, 'testimg/big-30mb.png');
  await expect(page.locator('#sg-upload-progress')).toBeVisible({ timeout: 8000 });
  const txt = await page.locator('#sg-upload-progress').innerText();
  console.log('progress text:', txt.replace(/\s+/g, ' '));
  await G.shot(page, 'b2', '06-upload-progress-1440');
  await waitArt(page, 150000);
  await expect(page.locator('#sg-upload-progress')).toBeHidden();
  const a = await art(page);
  expect(a.imgs).toBe(1);
});

test('Add to Cart is blocked when the print area is empty', async ({ page }) => {
  const posts = [];
  page.on('request', r => { if (r.method() === 'POST' && /\/product\//.test(r.url())) posts.push(r.url()); });
  page.on('dialog', d => d.accept());
  expect(await G.openDesigner(page, 1168)).toBeTruthy();
  await page.locator('#sg-add-to-cart-btn').click({ force: true });
  await expect(page.locator('#sg-empty-msg')).toBeVisible({ timeout: 5000 });
  await expect(page.locator('#sg-empty-msg')).toContainText(/artwork/i);
  await page.waitForTimeout(4000);
  expect(page.url()).toContain('create-your-own');
  expect(posts.length).toBe(0);
  await G.shot(page, 'b2', '07-empty-canvas-blocked-1440');
  // with art it goes through
  await upload(page, 'testimg/test-24x16in-150dpi.png');
  await waitArt(page);
  await expect(page.locator('#sg-empty-msg')).toBeHidden();
});

test('upload limits and types are advertised', async ({ page }) => {
  expect(await G.openDesigner(page, 1168)).toBeTruthy();
  await G.tab(page, 'Photos');
  const t = await page.locator('#tab-photo .allow-size').innerText();
  expect(t).toMatch(/Max file size:\s*200 MB/i);
  const words = t.split(/[,\n:]/).map(x => x.trim().toLowerCase());
  expect(words).toEqual(expect.arrayContaining(["ai", "eps", "tiff"]));
  await G.shot(page, 'b2', '08-photos-tab-limits-1440');
});

async function expectPlaced(page, file, label, waitMs = 150000) {
  const dialogs = [];
  page.on('dialog', d => { dialogs.push(d.message()); d.accept(); });
  expect(await G.openDesigner(page, 1168)).toBeTruthy();
  const t0 = await upload(page, file);
  await waitArt(page, waitMs);
  console.log(label, 'placed after', ((Date.now() - t0) / 1000).toFixed(1), 's, dims', await G.dims(page), 'price', await G.price(page));
  expect(dialogs, label).toEqual([]);
  const a = await art(page);
  expect(a.imgs).toBe(1);
  return a;
}

test('TIFF upload is converted and placed at its size (16 x 10.67 in)', async ({ page }) => {
  await expectPlaced(page, 'testimg/test-print.tif', 'tiff');
  expect(await G.dims(page)).toMatch(/^16x10\.6/);
  await G.shot(page, 'b2', '10-tiff-placed-1440');
});

test('EPS upload is converted and placed', async ({ page }) => {
  await expectPlaced(page, 'testimg/test-vector.eps', 'eps');
  expect(G.num(await G.price(page))).toBeGreaterThan(0);
  await G.shot(page, 'b2', '11-eps-placed-1440');
});

test('AI upload (PDF-compatible) is placed', async ({ page }) => {
  await expectPlaced(page, 'testimg/test-artwork.ai', 'ai');
  expect(G.num(await G.price(page))).toBeGreaterThan(0);
  await G.shot(page, 'b2', '12-ai-placed-1440');
});

test('130 MB file goes through the chunked uploader and is placed', async ({ page }) => {
  test.setTimeout(540000);
  let n = 0;
  page.on('request', r => { if (r.method() === 'POST' && /admin-ajax/.test(r.url())) n++; });
  await expectPlaced(page, 'testimg/big-130mb.png', 'chunked 130MB', 330000);
  console.log('admin-ajax POST requests', n);
  expect(n).toBeGreaterThan(15); // init + 16 parts of 8 MB + finish, not one big request (Cloudflare caps a request body at 100 MB)
  await G.shot(page, 'b2', '13-chunked-130mb-placed-1440');
});

test('file larger than 200 MB is refused with a clear message', async ({ page }) => {
  const dialogs = [];
  page.on('dialog', d => { dialogs.push(d.message()); d.accept(); });
  const posts = [];
  page.on('request', r => { if (r.method() === 'POST' && /admin-ajax/.test(r.url())) posts.push(r.url()); });
  expect(await G.openDesigner(page, 1168)).toBeTruthy();
  const before = posts.length;
  await upload(page, 'testimg/big-205mb.png');
  await page.waitForTimeout(4000);
  expect(dialogs.join(' ')).toMatch(/200 MB/);
  expect(posts.length - before).toBe(0);
});

test('mobile 390: designer loads, art bar does not break the page', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const page = await ctx.newPage();
  const errs = [];
  page.on('pageerror', e => errs.push(String(e).slice(0, 160)));
  try {
    await page.goto(`${G.BASE}/create-your-own/?product_id=1168&view=m`, { waitUntil: 'load', timeout: 90000 });
    await page.waitForTimeout(9000);
    await G.shot(page, 'b2', '09-designer-390');
    console.log('mobile designer scrollWidth', await page.evaluate(() => document.documentElement.scrollWidth), 'pageerrors', errs.length);
    expect(errs.filter(e => /SG|sg-|artbar/i.test(e))).toEqual([]);
  } finally { await ctx.close(); }
});

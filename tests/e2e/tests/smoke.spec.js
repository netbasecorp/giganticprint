const { test, expect } = require('@playwright/test');

test('home returns 200 and renders', async ({ page }) => {
  const res = await page.goto('/', { waitUntil: 'domcontentloaded' });
  expect(res.status()).toBe(200);
  await expect(page.locator('body')).not.toBeEmpty();
  await page.screenshot({ path: 'shots/home.png', fullPage: false });
});

test('product 1168 renders', async ({ page }) => {
  const res = await page.goto('/?p=1168', { waitUntil: 'domcontentloaded' }); // redirects to the canonical product URL
  expect(res.status()).toBe(200);
  await expect(page.locator('h1').first()).toBeVisible();
  await expect(page.locator('body')).toHaveClass(/single-product/);
  await page.screenshot({ path: 'shots/product-1168.png', fullPage: false });
});

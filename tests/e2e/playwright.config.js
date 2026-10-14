// Run: /srv/gp-dev/e2e/run.sh [playwright args]   (BASE_URL defaults to the staging site)
const { defineConfig } = require('@playwright/test');
module.exports = defineConfig({
  testDir: './tests',
  timeout: 60000,
  retries: 0,
  workers: 1,
  reporter: [['list']],
  outputDir: './shots/results',
  use: {
    baseURL: process.env.BASE_URL || 'https://giganticprints.printcart.com',
    ignoreHTTPSErrors: false,
    screenshot: 'only-on-failure',
    viewport: { width: 1366, height: 900 },
  },
});

const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    viewport: { width: 1400, height: 1000 },
    httpCredentials: { username: 'teima', password: 'fareja' },
  });
  const page = await context.newPage();
  await page.goto('https://teima.space/ensaios/musica/frequencia', { waitUntil: 'networkidle' });
  await page.screenshot({ path: 'screenshot.png' });
  await browser.close();
})();

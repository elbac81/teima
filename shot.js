const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    httpCredentials: { username: 'teima', password: 'fareja' },
    viewport: { width: 1400, height: 900 },
  });
  const page = await context.newPage();
  await page.goto('https://teima.space/ensaios/letra/frequencia?v=' + Date.now(), { waitUntil: 'networkidle' });
  await page.waitForTimeout(400);
  await page.screenshot({ path: 'letra.png', fullPage: true });

  const list = await context.newPage();
  await list.goto('https://teima.space/ensaios/letras?v=' + Date.now(), { waitUntil: 'networkidle' });
  await list.waitForTimeout(300);
  await list.screenshot({ path: 'letras-list.png' });

  await browser.close();
})();

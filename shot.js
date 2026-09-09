const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    httpCredentials: { username: 'teima', password: 'fareja' },
    viewport: { width: 1400, height: 900 },
  });
  const page = await context.newPage();

  await page.goto('https://teima.space/ensaios/repertorios?v=' + Date.now(), { waitUntil: 'networkidle' });
  await page.screenshot({ path: 'rep.png' });

  const page2 = await context.newPage();
  await page2.goto('https://teima.space/?v=' + Date.now(), { waitUntil: 'networkidle' });
  await page2.screenshot({ path: 'root.png' });

  await browser.close();
})();

const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 420, height: 1000 } });
  await page.goto('https://teima.space/ensaios/musica/lixo', { waitUntil: 'networkidle' });
  await page.click('.mini-btn.toggle');
  await page.waitForTimeout(400);
  await page.screenshot({ path: 'screenshot.png', fullPage: true });
  await browser.close();
})();

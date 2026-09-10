const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: null });
  await page.setViewportSize({ width: 500, height: 900 });
  await page.goto('https://teima.space/?v=' + Date.now(), { waitUntil: 'networkidle' });
  await page.waitForTimeout(300);
  await page.screenshot({ path: 'root-mobile.png' });
  await page.setViewportSize({ width: 1400, height: 900 });
  await page.goto('https://teima.space/?v=' + Date.now(), { waitUntil: 'networkidle' });
  await page.waitForTimeout(300);
  const box = await page.locator('.social-row').boundingBox();
  console.log('SOCIAL_ROW_BOX', JSON.stringify(box));
  await page.screenshot({ path: 'root-desktop.png', clip: { x: 0, y: 0, width: 1400, height: 900 } });
  await browser.close();
})();

const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    httpCredentials: { username: 'teima', password: 'fareja' },
    viewport: { width: 1400, height: 900 },
  });

  const repList = await context.newPage();
  await repList.goto('https://teima.space/ensaios/repertorios?v=' + Date.now(), { waitUntil: 'networkidle' });
  await repList.screenshot({ path: 'rep-list.png' });
  const card = await repList.$('.repertorio-card');
  if (card) {
    await card.click();
    await repList.waitForTimeout(400);
    await repList.screenshot({ path: 'rep-detail.png' });
  }

  const eventos = await context.newPage();
  await eventos.goto('https://teima.space/ensaios/eventos?v=' + Date.now(), { waitUntil: 'networkidle' });
  await eventos.screenshot({ path: 'eventos.png' });

  const root = await context.newPage();
  await root.goto('https://teima.space/?v=' + Date.now(), { waitUntil: 'networkidle' });
  await root.waitForTimeout(600);
  await root.screenshot({ path: 'root.png', fullPage: true });

  await browser.close();
})();

const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    httpCredentials: { username: 'teima', password: 'fareja' },
  });
  const page = await context.newPage();
  await page.goto('https://teima.space/ensaios/letra/frequencia?v=' + Date.now(), { waitUntil: 'networkidle' });
  await page.waitForTimeout(400);

  const info = await page.evaluate(() => {
    const sections = Array.from(document.querySelectorAll('.letra-section'));
    return sections.map((sec) => {
      const title = sec.querySelector('.letra-section-title')?.textContent;
      const pre = sec.querySelector('.letra-text');
      const spans = Array.from(pre.querySelectorAll('span')).slice(0, 4).map((s) => ({
        cls: s.className,
        text: s.textContent,
        color: getComputedStyle(s).color,
        weight: getComputedStyle(s).fontWeight,
      }));
      return { title, innerHTMLSnippet: pre.innerHTML.slice(0, 300), spans };
    });
  });
  console.log(JSON.stringify(info, null, 2));

  await browser.close();
})();

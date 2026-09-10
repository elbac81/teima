const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    httpCredentials: { username: 'teima', password: 'fareja' },
    viewport: { width: 1400, height: 900 },
  });
  const page = await context.newPage();
  await page.goto('https://teima.space/ensaios/musica/frequencia?v=' + Date.now(), { waitUntil: 'networkidle' });
  await page.waitForTimeout(400);

  const before = await page.$$eval('#structure .section-card', (cards) => cards.map((c) => c.dataset.id));
  console.log('BEFORE', JSON.stringify(before));

  const cards = await page.$$('#structure .section-card');
  await page.dragAndDrop('#structure .section-card:nth-child(1)', '#structure .section-card:nth-child(2)');
  await page.waitForTimeout(500);

  const after = await page.$$eval('#structure .section-card', (cards) => cards.map((c) => c.dataset.id));
  console.log('AFTER_DOM', JSON.stringify(after));

  await page.waitForTimeout(1200); // let debounced save finish

  const api = await page.request.get('https://teima.space/ensaios/api.php');
  const data = await api.json();
  const song = data.songs.find((s) => s.id === 'frequencia');
  const persistedOrder = [...song.sections].sort((a, b) => (a.order || 0) - (b.order || 0)).map((s) => s.id);
  console.log('AFTER_PERSISTED', JSON.stringify(persistedOrder));

  const changed = JSON.stringify(before) !== JSON.stringify(persistedOrder);
  console.log(changed ? 'DRAG_REORDER_OK' : 'DRAG_REORDER_FAILED');

  // restore original order
  song.sections.forEach((s) => {
    const idx = before.indexOf(s.id);
    if (idx !== -1) s.order = idx;
  });
  await page.request.post('https://teima.space/ensaios/api.php', {
    headers: { 'Content-Type': 'application/json' },
    data: JSON.stringify({
      songs: data.songs,
      repertorios: data.repertorios,
      eventos: data.eventos,
      notasGerais: data.notasGerais,
      notaPropria: data.notaPropria,
    }),
  });

  const verify = await page.request.get('https://teima.space/ensaios/api.php');
  const verifyData = await verify.json();
  const verifySong = verifyData.songs.find((s) => s.id === 'frequencia');
  const restoredOrder = [...verifySong.sections].sort((a, b) => (a.order || 0) - (b.order || 0)).map((s) => s.id);
  console.log('RESTORED', JSON.stringify(restoredOrder));
  console.log(JSON.stringify(restoredOrder) === JSON.stringify(before) ? 'RESTORE_OK' : 'RESTORE_FAILED');

  await browser.close();
})();

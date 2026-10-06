<?php
/* ==========================================================================
   Estruturas / guias — Teima (teima.space/privado/estruturas.php)

   As estruturas das músicas para ensaio (partes, compassos, BPM, linha do
   tempo, notas por compasso). Vieram do tarimbo.pt/teima-estruturas.php
   (out. 2026), onde estavam enquanto o teima.space esteve em baixo.

   Entra-se com o mesmo login das Notas de Ensaio (Basic Auth de /privado/,
   ver .htaccess). Os dados gravam-se em dados/estruturas.json pela API
   estruturas-dados.php — esse ficheiro vive só no servidor (fora do git e
   do deploy).
   ========================================================================== */
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Estruturas / guias — Teima</title>
<link rel="icon" href="/privado/assets/favicon.ico" sizes="any">
<link rel="icon" type="image/png" href="/privado/assets/favicon-32.png" sizes="32x32">
<link rel="apple-touch-icon" href="/privado/assets/favicon-180.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Barlow:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap">
<style>
  :root{
    color-scheme: dark;
    --ground:#0b0a08;
    --panel:#15130f;
    --panel2:#1d1a15;
    --line:#332f28;
    --ink:#f4eee1;
    --dim:#a39a88;
    --faint:#6f685b;
    --amber:#d9a233;
    --amber-ink:#1a1305;
    --ok:#7fb069;
    --warn:#e0703d;
    --t-intro:#6c8fb3; --t-verso:#8a9a5b; --t-pre:#b3915a; --t-refrao:#d9a233;
    --t-ponte:#9a78b0; --t-solo:#d4674a; --t-inter:#5e9e98; --t-break:#8c8577; --t-outro:#5f7896;
    --display:"Barlow Condensed", "Arial Narrow", sans-serif;
    --body:"Barlow", ui-sans-serif, system-ui, sans-serif;
    --mono:"IBM Plex Mono", ui-monospace, "SFMono-Regular", monospace;
  }
  *{box-sizing:border-box}
  html,body{background:var(--ground)}
  body{margin:0;color:var(--ink); font-family:var(--body); font-size:15px; line-height:1.45}
  button,input,select,textarea{font:inherit; color:inherit}
  :focus-visible{outline:2px solid var(--amber); outline-offset:2px}

  .app{max-width:1180px; margin:0 auto; padding-inline:16px; padding-block:24px 64px; display:grid; grid-template-columns:240px minmax(0,1fr); gap:28px}
  @media (max-width:820px){ .app{grid-template-columns:minmax(0,1fr); gap:18px} }

  .topbar{display:flex; justify-content:space-between; max-width:1180px; margin:0 auto; padding:10px 16px 0}
  .topbar a{font-family:var(--mono); font-size:.72rem; color:var(--faint); text-decoration:none}
  .topbar a:hover{color:var(--dim)}

  .brand{font-family:var(--mono); font-size:.72rem; letter-spacing:.14em; text-transform:uppercase; color:var(--amber); margin:0 0 14px}
  aside h2{font-family:var(--mono); font-size:.7rem; letter-spacing:.14em; text-transform:uppercase; color:var(--dim); margin:0 0 8px; font-weight:500}
  .songlist{list-style:none; margin:0; padding:0; display:flex; flex-direction:column}
  .songlist button{width:100%; text-align:left; background:none; border:0; border-bottom:1px solid var(--line); padding:9px 4px; cursor:pointer; display:flex; justify-content:space-between; gap:8px; align-items:baseline}
  .songlist li:first-child button{border-top:1px solid var(--line)}
  .songlist .nm{font-family:var(--display); font-weight:600; font-size:1.2rem; letter-spacing:.01em}
  .songlist .meta{font-family:var(--mono); font-size:.72rem; color:var(--faint); font-variant-numeric:tabular-nums; white-space:nowrap}
  .songlist button:hover .nm{color:var(--amber)}
  .songlist button[aria-current="true"]{background:var(--panel)}
  .songlist button[aria-current="true"] .nm{color:var(--amber)}
  .mobile-pick{display:none}
  @media (max-width:820px){
    .songlist{display:none}
    .mobile-pick{display:flex; gap:8px}
    .mobile-pick select{flex:1}
  }

  .btn{background:var(--panel2); border:1px solid var(--line); border-radius:4px; padding:7px 12px; cursor:pointer; font-size:.9rem; white-space:nowrap}
  .btn:hover{border-color:var(--dim)}
  .btn.primary{background:var(--amber); color:var(--amber-ink); border-color:var(--amber); font-weight:600}
  .btn.primary:hover{filter:brightness(1.08)}
  .btn.ghost{background:none}
  .btn.danger{color:var(--warn)}
  .btn:disabled{opacity:.4; cursor:default}
  .addsong{margin-top:12px; width:100%}
  .importar, .exportar{display:block; text-align:center; text-decoration:none; color:var(--dim); font-size:.82rem; margin-top:8px; cursor:pointer}

  .field{background:var(--panel); border:1px solid var(--line); border-radius:4px; padding:6px 8px; width:100%; min-width:0}
  .field:hover{border-color:#4a443a}
  .field:focus{border-color:var(--amber); outline:none}
  .field::placeholder{color:var(--faint)}
  select.field{padding-right:4px}
  label.lab{display:flex; flex-direction:column; gap:4px; font-family:var(--mono); font-size:.66rem; letter-spacing:.1em; text-transform:uppercase; color:var(--dim)}

  .banner{border:1px solid var(--line); border-left:3px solid var(--warn); background:var(--panel); padding:10px 12px; margin-bottom:16px; font-size:.9rem; color:var(--dim)}

  /* song header */
  .songhead{display:flex; flex-direction:column; gap:14px; padding-bottom:18px; border-bottom:1px solid var(--line)}
  .titlerow{display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap}
  .title-in{flex:1 1 280px; background:none; border:0; border-bottom:1px dashed transparent; padding:0; font-family:var(--display); font-weight:700; font-size:clamp(2.2rem,6vw,3.4rem); line-height:1; text-transform:uppercase; letter-spacing:.01em; min-width:0}
  .title-in:hover{border-bottom-color:var(--line)}
  .title-in:focus{outline:none; border-bottom-color:var(--amber)}
  .total{font-family:var(--mono); text-align:right}
  .total b{display:block; font-size:2rem; font-weight:500; color:var(--amber); font-variant-numeric:tabular-nums; line-height:1}
  .total span{font-size:.7rem; letter-spacing:.12em; text-transform:uppercase; color:var(--dim)}
  .params{display:grid; grid-template-columns:90px 110px 120px minmax(0,1fr); gap:10px}
  @media (max-width:640px){ .params{grid-template-columns:repeat(2,minmax(0,1fr))} .params .wide{grid-column:1/-1} }
  .hint{font-size:.78rem; color:var(--faint); margin:0}

  /* timeline */
  .tl-wrap{margin-top:20px}
  .tl-head{display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:8px; flex-wrap:wrap}
  .tl-head h3, .parts-head h3{font-family:var(--mono); font-size:.7rem; letter-spacing:.14em; text-transform:uppercase; color:var(--dim); margin:0; font-weight:500}
  .tl{position:relative; display:flex; height:64px; background:var(--panel); border:1px solid var(--line); border-radius:4px; overflow:hidden}
  .tl .blk{position:relative; height:100%; border-right:1px solid var(--ground); cursor:pointer; padding:6px 7px; overflow:hidden; min-width:3px; display:flex; flex-direction:column; justify-content:space-between; color:#0e0d0b; transition:filter .15s}
  .tl .blk:hover{filter:brightness(1.12)}
  .tl .blk.cur{box-shadow:inset 0 0 0 2px var(--ink)}
  .tl .blk .bn{font-family:var(--display); font-weight:700; font-size:.95rem; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; text-transform:uppercase; position:relative}
  .tl .blk .bt{font-family:var(--mono); font-size:.66rem; white-space:nowrap; opacity:.8; position:relative}
  .tl .blk .bars{position:absolute; inset:0; display:flex}
  .tl .blk .barcell{height:100%; box-sizing:border-box; border-right:1px solid rgba(0,0,0,.28); display:flex; align-items:center; justify-content:center; padding:1px; overflow:hidden}
  .tl .blk .barcell:last-child{border-right:none}
  .tl .blk .barnote{font-family:var(--mono); font-size:.56rem; line-height:1.1; color:#0e0d0b; opacity:.85; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%}
  .tl .empty{margin:auto; color:var(--faint); font-size:.9rem}
  .playhead{position:absolute; top:0; bottom:0; width:2px; background:var(--ink); left:0; pointer-events:none; box-shadow:0 0 0 1px var(--ground)}
  .ruler{position:relative; height:18px; font-family:var(--mono); font-size:.64rem; color:var(--faint); font-variant-numeric:tabular-nums}
  .ruler span{position:absolute; top:3px; transform:translateX(-50%)}
  .ruler span:first-child{transform:none}
  .ruler i{position:absolute; top:0; width:1px; height:4px; background:var(--line)}

  .readout{display:none; margin-top:10px; padding:10px 12px; border:1px solid var(--line); background:var(--panel); border-radius:4px; align-items:baseline; gap:18px; flex-wrap:wrap}
  .readout.on{display:flex}
  .readout .clock{font-family:var(--mono); font-size:1.8rem; color:var(--amber); font-variant-numeric:tabular-nums; line-height:1}
  .readout .sec{font-family:var(--display); font-size:1.6rem; font-weight:700; text-transform:uppercase; line-height:1}
  .readout .bar{font-family:var(--mono); font-size:.85rem; color:var(--dim); font-variant-numeric:tabular-nums}
  .readout .next{font-size:.85rem; color:var(--faint)}

  /* parts */
  .parts-head{display:flex; justify-content:space-between; align-items:center; margin:26px 0 8px; gap:10px; flex-wrap:wrap}
  .cols, .part .row1{display:grid; grid-template-columns:30px 132px minmax(0,1fr) 72px 64px 78px 104px 96px; gap:8px; align-items:center}
  .cols{font-family:var(--mono); font-size:.62rem; letter-spacing:.1em; text-transform:uppercase; color:var(--faint); padding:0 10px 6px}
  .parts{display:flex; flex-direction:column; gap:6px}
  .part{background:var(--panel); border:1px solid var(--line); border-left:4px solid var(--c, var(--line)); border-radius:4px; padding:8px 10px; display:flex; flex-direction:column; gap:6px}
  .part.cur{border-color:var(--ink); border-left-color:var(--c)}
  .part .num{font-family:var(--mono); font-size:.75rem; color:var(--faint); text-align:center}
  .part .times{font-family:var(--mono); font-size:.8rem; font-variant-numeric:tabular-nums; color:var(--dim); white-space:nowrap}
  .part .times b{color:var(--ink); font-weight:500}
  .part .acts{display:flex; gap:2px; justify-content:flex-end}
  .icon{background:none; border:1px solid transparent; border-radius:4px; width:28px; height:28px; cursor:pointer; color:var(--dim); display:inline-grid; place-items:center; padding:0}
  .icon:hover{border-color:var(--line); color:var(--ink)}
  .icon.del:hover{color:var(--warn)}
  .icon svg{width:15px; height:15px}
  .part .row2{display:grid; grid-template-columns:30px minmax(0,1fr); gap:8px; align-items:start}
  .barnotes{display:flex; flex-wrap:wrap; gap:5px}
  .bnote{display:flex; align-items:center; gap:3px}
  .bnote-n{font-family:var(--mono); font-size:.62rem; color:var(--faint); width:14px; text-align:right; flex:none}
  .bnote input{width:84px; padding:4px 6px; font-size:.8rem}
  .mlab{display:none}
  @media (max-width:980px){
    .cols{display:none}
    .part .row1{grid-template-columns:repeat(6,minmax(0,1fr)); row-gap:8px}
    .part .row1 > .num{display:none}
    .part .row1 > .c-type{grid-column:span 3}
    .part .row1 > .c-label{grid-column:span 3}
    .part .row1 > .c-bars, .part .row1 > .c-bpm, .part .row1 > .c-dur{grid-column:span 2}
    .part .row1 > .c-times{grid-column:span 4; align-self:center}
    .part .row1 > .acts{grid-column:span 2}
    .part .row2{grid-template-columns:minmax(0,1fr)}
    .part .row2 > .num{display:none}
    .mlab{display:block; font-family:var(--mono); font-size:.6rem; letter-spacing:.1em; text-transform:uppercase; color:var(--faint); margin-bottom:3px}
  }
  .addrow{display:flex; gap:6px; flex-wrap:wrap; margin-top:10px}
  .addrow .btn{font-size:.82rem; padding:5px 10px; border-left:3px solid var(--c)}

  .foot{display:flex; gap:10px; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-top:28px; padding-top:16px; border-top:1px solid var(--line)}
  .saved{font-family:var(--mono); font-size:.72rem; color:var(--faint)}
  .confirm{display:inline-flex; gap:6px; align-items:center; font-size:.88rem; color:var(--warn)}
  .copybox{width:100%; margin-top:10px; font-family:var(--mono); font-size:.8rem; height:160px}
  .emptystate{padding:40px 0; color:var(--dim)}
  @media (prefers-reduced-motion: reduce){ *{transition:none!important} }
</style>
</head>
<body>

<div class="topbar"><a href="/privado/">‹ Notas de ensaio</a><a href="/privado/logout.php">Sair</a></div>

<div class="app">
  <aside>
    <p class="brand">Teima · Estruturas / guias</p>
    <h2>Músicas</h2>
    <ul class="songlist" id="songlist"></ul>
    <div class="mobile-pick"><select class="field" id="songpick" aria-label="Escolher música"></select></div>
    <button class="btn addsong" id="addsong">+ Nova música</button>
    <!-- Importar: o ficheiro descarregado do tarimbo.pt (ou uma cópia daqui).
         Só junta as músicas que ainda não existem; as que existem ficam. -->
    <label class="btn ghost addsong importar" title="Juntar estruturas de um ficheiro">⬆ Importar
      <input type="file" id="importar" accept="application/json,.json" hidden>
    </label>
    <a class="btn ghost addsong exportar" href="estruturas-dados.php?exportar=1">⬇ Descarregar cópia</a>
  </aside>

  <main id="main">
    <div id="banner"></div>
    <div id="editor"><p class="emptystate">A carregar estruturas…</p></div>
  </main>
</div>

<script>
(() => {
  const TYPES = [
    ['intro','Intro'],['verso','Verso'],['pre','Pré-refrão'],['refrao','Refrão'],
    ['ponte','Ponte'],['solo','Solo'],['inter','Interlúdio'],['break','Break'],['outro','Outro']
  ];
  const TYPE_NAME = Object.fromEntries(TYPES);
  // pulsações (à velocidade do BPM) por compasso
  const SIGS = {'4/4':4,'3/4':3,'2/4':2,'5/4':5,'6/8':2,'12/8':4,'7/8':3.5};
  const API = 'estruturas-dados.php';
  // Cabeçalho próprio: um formulário de outro site não o consegue pôr (CSRF).
  const POST = {'Content-Type': 'application/json', 'X-Teima': '1'};

  const $ = (s, r=document) => r.querySelector(s);
  const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  const uid = () => Math.random().toString(36).slice(2, 10);

  let offline = false;
  const songs = new Map();      // id -> data
  let currentId = null;
  const dirty = new Set();
  const timers = {}, saving = {}, again = {};
  let confirmDel = false;
  let play = null; // {t0, raf}

  /* ---------- tempo ---------- */
  function parseDur(s){
    s = String(s || '').trim(); if (!s) return null;
    const m = s.match(/^(\d+):(\d{1,2})(?:[.,](\d))?$/);
    if (m) return (+m[1])*60 + (+m[2]) + (m[3] ? +m[3]/10 : 0);
    const n = parseFloat(s.replace(',', '.'));
    return isFinite(n) ? n : null;
  }
  function fmt(t){
    t = Math.max(0, t || 0);
    const m = Math.floor(t/60), s = Math.floor(t%60);
    return m + ':' + String(s).padStart(2,'0');
  }
  function barCount(p){ return Math.max(1, Math.round(parseFloat(p.bars) || 0)); }
  function setBarNote(p, k, val){
    if (!Array.isArray(p.barNotes)) p.barNotes = [];
    while (p.barNotes.length <= k) p.barNotes.push('');
    p.barNotes[k] = val;
  }
  function partSecs(song, p){
    const d = parseDur(p.dur);
    if (d != null) return d;
    const bars = parseFloat(p.bars) || 0;
    const bpm = parseFloat(p.bpm) || parseFloat(song.bpm) || 0;
    const beats = SIGS[song.sig] || 4;
    if (!bars || !bpm) return 0;
    return bars * beats * 60 / bpm;
  }
  function calcSecs(song, p){
    const bars = parseFloat(p.bars) || 0;
    const bpm = parseFloat(p.bpm) || parseFloat(song.bpm) || 0;
    return (bars && bpm) ? bars * (SIGS[song.sig]||4) * 60 / bpm : 0;
  }
  function layout(song){
    let t = 0;
    return (song.parts || []).map(p => { const d = partSecs(song, p); const r = {p, start:t, dur:d, end:t+d}; t += d; return r; });
  }
  function total(song){ return layout(song).reduce((a, r) => a + r.dur, 0); }
  function labelOf(song, i){
    const p = song.parts[i];
    if (p.label && p.label.trim()) return p.label.trim();
    const base = TYPE_NAME[p.type] || 'Parte';
    const same = song.parts.filter(x => x.type === p.type && !(x.label && x.label.trim()));
    if (same.length < 2) return base;
    return base + ' ' + (same.indexOf(p) + 1);
  }
  /* ---------- guardar ---------- */
  function touch(id){
    dirty.add(id);
    setSaved('Por guardar…');
    clearTimeout(timers[id]);
    timers[id] = setTimeout(() => flush(id), 700);
  }
  async function flush(id){
    if (offline) { dirty.delete(id); setSaved('Sem ligação — não guardado'); return; }
    if (saving[id]) { again[id] = true; return; }
    saving[id] = true;
    const data = JSON.parse(JSON.stringify(songs.get(id)));
    data.updatedAt = new Date().toISOString();
    try {
      const r = await fetch(API, {
        method: 'POST',
        headers: POST,
        body: JSON.stringify({acao: 'gravar', id, dados: data})
      });
      const j = await r.json();
      if (!j.ok) throw new Error(j.erro || 'falhou');
      setSaved('Guardado ' + new Date().toLocaleTimeString('pt-PT', {hour:'2-digit', minute:'2-digit'}));
    } catch (e) {
      setSaved('Falhou a gravação — tenta de novo');
    }
    saving[id] = false;
    if (again[id]) { again[id] = false; return flush(id); }
    dirty.delete(id);
  }
  function setSaved(t){ const el = $('#saved'); if (el) el.textContent = t; }
  function showBanner(){
    const b = $('#banner');
    b.innerHTML = offline
      ? '<div class="banner">Sem ligação ao servidor. Podes experimentar, mas as alterações não ficam guardadas — recarrega a página.</div>'
      : '';
  }

  /* ---------- lista ---------- */
  function ordered(){
    return [...songs.entries()].sort((a, b) => (a[1].sort ?? 999) - (b[1].sort ?? 999) || String(a[1].title).localeCompare(b[1].title, 'pt'));
  }
  function renderList(){
    const list = ordered();
    $('#songlist').innerHTML = list.map(([id, s]) => {
      const t = total(s);
      return `<li><button data-song="${id}" aria-current="${id === currentId}"><span class="nm">${esc(s.title || 'Sem título')}</span><span class="meta">${s.parts && s.parts.length ? fmt(t) : '—'}</span></button></li>`;
    }).join('');
    $('#songpick').innerHTML = list.map(([id, s]) => `<option value="${id}" ${id === currentId ? 'selected' : ''}>${esc(s.title || 'Sem título')}${s.parts && s.parts.length ? ' · ' + fmt(total(s)) : ''}</option>`).join('');
  }

  /* ---------- editor ---------- */
  const ICON = {
    up:'<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 10l4-4 4 4"/></svg>',
    down:'<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 6l4 4 4-4"/></svg>',
    dup:'<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="5" y="5" width="8" height="8" rx="1"/><path d="M3 11V3h8"/></svg>',
    del:'<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 4l8 8M12 4l-8 8"/></svg>'
  };

  function render(){
    renderList();
    showBanner();
    const ed = $('#editor');
    const s = songs.get(currentId);
    if (!s) { ed.innerHTML = '<p class="emptystate">Ainda não há músicas. Carrega em "+ Nova música" para começar.</p>'; return; }
    const sigOpts = Object.keys(SIGS).map(k => `<option ${k === s.sig ? 'selected' : ''}>${k}</option>`).join('');
    ed.innerHTML = `
      <div class="songhead">
        <div class="titlerow">
          <input class="title-in" id="f-title" value="${esc(s.title)}" placeholder="Nome da música" aria-label="Nome da música">
          <div class="total"><b id="tot">${fmt(total(s))}</b><span id="totbars"></span></div>
        </div>
        <div class="params">
          <label class="lab">BPM<input class="field" id="f-bpm" inputmode="decimal" value="${esc(s.bpm)}" placeholder="120"></label>
          <label class="lab">Compasso<select class="field" id="f-sig">${sigOpts}</select></label>
          <label class="lab">Tonalidade<input class="field" id="f-key" value="${esc(s.key)}" placeholder="Lá m"></label>
          <label class="lab wide">Notas da música<input class="field" id="f-notes" value="${esc(s.notes)}" placeholder="Afinação, click, pré-contagem, referências…"></label>
        </div>
        <p class="hint">A duração de cada parte é calculada a partir dos compassos e do BPM. Em 6/8 e 12/8 o BPM conta a semínima com ponto. Escreve uma duração (m:ss) para a fixar à mão.</p>
      </div>

      <div class="tl-wrap">
        <div class="tl-head">
          <h3>Linha do tempo</h3>
          <div style="display:flex;gap:6px">
            <button class="btn" id="playbtn" ${s.parts.length ? '' : 'disabled'}>▶ Percorrer</button>
          </div>
        </div>
        <div class="tl" id="tl"></div>
        <div class="ruler" id="ruler"></div>
        <div class="readout" id="readout"><span class="clock" id="r-clock">0:00</span><span class="sec" id="r-sec"></span><span class="bar" id="r-bar"></span><span class="next" id="r-next"></span></div>
      </div>

      <div class="parts-head">
        <h3>Partes · <span id="nparts">${s.parts.length}</span></h3>
      </div>
      <div class="cols"><span>#</span><span>Tipo</span><span>Nome</span><span>Compassos</span><span>BPM</span><span>Duração</span><span>Início – fim</span><span></span></div>
      <div class="parts" id="parts">${s.parts.map((p, i) => partHTML(s, p, i)).join('')}</div>
      <div class="addrow">${TYPES.map(([k, n]) => `<button class="btn" data-add="${k}" style="--c:var(--t-${k})">+ ${n}</button>`).join('')}</div>

      <div class="foot">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <button class="btn" id="copybtn">Copiar estrutura em texto</button>
          ${confirmDel
            ? `<span class="confirm">Apagar "${esc(s.title || 'esta música')}" para toda a banda? <button class="btn danger" id="delyes">Apagar</button><button class="btn ghost" id="delno">Cancelar</button></span>`
            : `<button class="btn ghost danger" id="delsong">Apagar música</button>`}
        </div>
        <span class="saved" id="saved"></span>
      </div>
      <div id="copyarea"></div>`;
    refreshDerived();
  }

  function partHTML(s, p, i){
    const calc = calcSecs(s, p);
    return `<div class="part" data-i="${i}" style="--c:var(--t-${p.type})">
      <div class="row1">
        <span class="num">${i + 1}</span>
        <div class="c-type"><span class="mlab">Tipo</span><select class="field" data-f="type" aria-label="Tipo">${TYPES.map(([k, n]) => `<option value="${k}" ${k === p.type ? 'selected' : ''}>${n}</option>`).join('')}</select></div>
        <div class="c-label"><span class="mlab">Nome</span><input class="field" data-f="label" value="${esc(p.label)}" placeholder="${esc(labelOf(s, i))}" aria-label="Nome da parte"></div>
        <div class="c-bars"><span class="mlab">Compassos</span><input class="field" data-f="bars" inputmode="numeric" value="${esc(p.bars)}" placeholder="8" aria-label="Compassos"></div>
        <div class="c-bpm"><span class="mlab">BPM</span><input class="field" data-f="bpm" inputmode="decimal" value="${esc(p.bpm)}" placeholder="${esc(s.bpm || '—')}" aria-label="BPM desta parte" title="Deixa vazio para usar o BPM da música"></div>
        <div class="c-dur"><span class="mlab">Duração</span><input class="field" data-f="dur" value="${esc(p.dur)}" placeholder="${calc ? fmt(calc) : 'm:ss'}" aria-label="Duração fixa (m:ss)"></div>
        <div class="c-times"><span class="mlab">Início – fim</span><span class="times" data-times></span></div>
        <div class="acts">
          <button class="icon" data-act="up" aria-label="Subir" ${i === 0 ? 'disabled' : ''}>${ICON.up}</button>
          <button class="icon" data-act="down" aria-label="Descer" ${i === s.parts.length - 1 ? 'disabled' : ''}>${ICON.down}</button>
          <button class="icon" data-act="dup" aria-label="Duplicar">${ICON.dup}</button>
          <button class="icon del" data-act="del" aria-label="Apagar parte">${ICON.del}</button>
        </div>
      </div>
      <div class="row2">
        <span class="num"></span>
        <div class="barnotes" data-barnotes>${barNotesHTML(p)}</div>
      </div>
    </div>`;
  }

  function barNotesHTML(p){
    const n = barCount(p);
    return Array.from({length: n}, (_, k) => `<label class="bnote"><span class="bnote-n">${k + 1}</span><input class="field" data-barnote="${k}" value="${esc(p.barNotes?.[k] || '')}" placeholder="c${k + 1}" aria-label="Anotação do compasso ${k + 1}"></label>`).join('');
  }

  // atualiza o que depende dos números sem reconstruir os campos
  function refreshDerived(){
    const s = songs.get(currentId); if (!s) return;
    const L = layout(s), T = L.reduce((a, r) => a + r.dur, 0);
    const tot = $('#tot'); if (tot) tot.textContent = fmt(T);
    const bars = (s.parts || []).reduce((a, p) => a + (parseFloat(p.bars) || 0), 0);
    const tb = $('#totbars'); if (tb) tb.textContent = bars ? `${bars} compassos` : 'duração total';
    document.querySelectorAll('.part').forEach(el => {
      const i = +el.dataset.i, r = L[i]; if (!r) return;
      el.querySelector('[data-times]').innerHTML = `<b>${fmt(r.start)}</b> – ${fmt(r.end)}`;
      const d = el.querySelector('[data-f="dur"]'); const c = calcSecs(s, r.p);
      d.placeholder = c ? fmt(c) : 'm:ss';
      const lb = el.querySelector('[data-f="label"]'); lb.placeholder = labelOf(s, i);
      el.style.setProperty('--c', `var(--t-${r.p.type})`);
      const bn = el.querySelector('[data-barnotes]');
      if (bn && bn.children.length !== barCount(r.p)) bn.innerHTML = barNotesHTML(r.p);
    });
    // timeline
    const tl = $('#tl'); if (!tl) return;
    if (!T) {
      tl.innerHTML = `<span class="empty">${s.parts.length ? 'Indica o BPM e os compassos para ver a linha do tempo' : 'Adiciona partes em baixo'}</span>`;
      $('#ruler').innerHTML = '';
    } else {
      tl.innerHTML = L.map((r, i) => {
        if (!r.dur) return '';
        const n = barCount(r.p);
        const cells = Array.from({length: n}, (_, k) => {
          const note = (r.p.barNotes && r.p.barNotes[k]) || '';
          const t = `Compasso ${k + 1}${note ? ': ' + note : ''}`;
          return `<div class="barcell" style="width:${100 / n}%" title="${esc(t)}">${note ? `<span class="barnote">${esc(note)}</span>` : ''}</div>`;
        }).join('');
        return `<div class="blk" data-jump="${i}" style="flex:${r.dur} 0 0;background:var(--t-${r.p.type})"><div class="bars">${cells}</div><span class="bn">${esc(labelOf(s, i))}</span><span class="bt">${fmt(r.dur)}${r.p.bars ? ' · ' + r.p.bars + 'c' : ''}</span></div>`;
      }).join('') + '<div class="playhead" id="ph" hidden></div>';
      const step = T > 300 ? 60 : T > 120 ? 30 : T > 45 ? 15 : 5;
      let h = '';
      for (let t = 0; t <= T + 0.01; t += step) h += `<i style="left:${t / T * 100}%"></i><span style="left:${t / T * 100}%">${fmt(t)}</span>`;
      $('#ruler').innerHTML = h;
    }
    const np = $('#nparts'); if (np) np.textContent = s.parts.length;
    const pb = $('#playbtn'); if (pb) pb.disabled = !T;
    renderList();
  }

  /* ---------- percorrer (cronómetro) ---------- */
  function startPlay(from = 0){
    stopPlay(true);
    play = {t0: performance.now() - from * 1000};
    $('#readout').classList.add('on');
    $('#playbtn').textContent = '■ Parar';
    tick();
  }
  function stopPlay(silent){
    if (play) cancelAnimationFrame(play.raf);
    play = null;
    if (silent) return;
    const ro = $('#readout'); if (ro) ro.classList.remove('on');
    const pb = $('#playbtn'); if (pb) pb.textContent = '▶ Percorrer';
    const ph = $('#ph'); if (ph) ph.hidden = true;
    document.querySelectorAll('.cur').forEach(e => e.classList.remove('cur'));
  }
  function tick(){
    const s = songs.get(currentId); if (!s || !play) return;
    const L = layout(s), T = L.reduce((a, r) => a + r.dur, 0);
    const t = (performance.now() - play.t0) / 1000;
    if (t >= T) { stopPlay(); return; }
    const i = L.findIndex(r => t >= r.start && t < r.end);
    const r = L[i];
    const ph = $('#ph'); if (ph) { ph.hidden = false; ph.style.left = (t / T * 100) + '%'; }
    $('#r-clock').textContent = fmt(t);
    $('#r-sec').textContent = r ? labelOf(s, i) : '';
    $('#r-sec').style.color = r ? `var(--t-${r.p.type})` : '';
    const bars = r && (parseFloat(r.p.bars) || 0);
    $('#r-bar').textContent = bars ? `compasso ${Math.min(bars, Math.floor((t - r.start) / r.dur * bars) + 1)} de ${bars}` : (r ? `faltam ${fmt(r.end - t)}` : '');
    const n = L.slice(i + 1).find(x => x.dur);
    $('#r-next').textContent = n ? `a seguir: ${labelOf(s, L.indexOf(n))} em ${fmt(r.end - t)}` : 'última parte';
    document.querySelectorAll('.blk').forEach(b => b.classList.toggle('cur', +b.dataset.jump === i));
    document.querySelectorAll('.part').forEach(p => p.classList.toggle('cur', +p.dataset.i === i));
    play.raf = requestAnimationFrame(tick);
  }

  /* ---------- texto ---------- */
  function asText(s){
    const L = layout(s);
    const head = [(s.title || 'Sem título').toUpperCase(), s.bpm && s.bpm + ' BPM', s.sig, s.key].filter(Boolean).join(' · ');
    const lines = L.map((r, i) => {
      const notas = (r.p.barNotes || []).map((n, k) => n ? `\n       c${k + 1}: ${n}` : '').join('');
      return `${fmt(r.start).padStart(5)}  ${labelOf(s, i)}${r.p.bars ? ` (${r.p.bars} c.)` : ''} — ${fmt(r.dur)}${notas}`;
    });
    return head + '\n' + lines.join('\n') + `\n${fmt(total(s)).padStart(5)}  FIM`;
  }

  /* ---------- eventos ---------- */
  document.addEventListener('click', async e => {
    const t = e.target.closest('button, .blk'); if (!t) return;
    const s = songs.get(currentId);
    if (t.dataset.song) { select(t.dataset.song); return; }
    if (t.id === 'addsong') { addSong(); return; }
    if (t.dataset.jump != null && s) {
      const r = layout(s)[+t.dataset.jump];
      if (play) startPlay(r.start);
      const el = document.querySelector(`.part[data-i="${t.dataset.jump}"]`);
      if (el) { el.scrollIntoView({behavior:'smooth', block:'center'}); el.querySelector('[data-f="bars"]')?.focus({preventScroll:true}); }
      return;
    }
    if (!s) return;
    if (t.id === 'playbtn') { play ? stopPlay() : startPlay(0); return; }
    if (t.id === 'copybtn') {
      const txt = asText(s);
      try { await navigator.clipboard.writeText(txt); t.textContent = 'Copiado'; setTimeout(() => t.textContent = 'Copiar estrutura em texto', 1600); }
      catch { $('#copyarea').innerHTML = `<textarea class="field copybox" readonly>${esc(txt)}</textarea>`; const ta = $('#copyarea textarea'); ta.focus(); ta.select(); }
      return;
    }
    if (t.id === 'delsong') { confirmDel = true; render(); return; }
    if (t.id === 'delno') { confirmDel = false; render(); return; }
    if (t.id === 'delyes') { deleteSong(currentId); return; }
    if (t.dataset.add) {
      const last = s.parts[s.parts.length - 1];
      s.parts.push({id: uid(), type: t.dataset.add, label: '', bars: t.dataset.add === 'break' ? '2' : (last?.bars || '8'), bpm: '', dur: '', barNotes: []});
      stopPlay(); render(); touch(currentId);
      const rows = document.querySelectorAll('.part'); rows[rows.length - 1]?.scrollIntoView({block:'nearest'});
      return;
    }
    const row = t.closest('.part'); if (!row) return;
    const i = +row.dataset.i, act = t.dataset.act;
    if (act === 'up' && i > 0) [s.parts[i - 1], s.parts[i]] = [s.parts[i], s.parts[i - 1]];
    else if (act === 'down' && i < s.parts.length - 1) [s.parts[i + 1], s.parts[i]] = [s.parts[i], s.parts[i + 1]];
    else if (act === 'dup') s.parts.splice(i + 1, 0, {...s.parts[i], id: uid(), label: '', barNotes: [...(s.parts[i].barNotes || [])]});
    else if (act === 'del') s.parts.splice(i, 1);
    else return;
    stopPlay(); render(); touch(currentId);
  });

  document.addEventListener('input', e => {
    const s = songs.get(currentId); if (!s) return;
    const el = e.target;
    const map = {'f-title':'title','f-bpm':'bpm','f-key':'key','f-notes':'notes'};
    if (map[el.id]) { s[map[el.id]] = el.value; }
    else if (el.dataset.barnote != null) { const i = +el.closest('.part').dataset.i; setBarNote(s.parts[i], +el.dataset.barnote, el.value); }
    else if (el.dataset.f) { const i = +el.closest('.part').dataset.i; s.parts[i][el.dataset.f] = el.value; }
    else return;
    refreshDerived(); touch(currentId);
  });
  document.addEventListener('change', e => {
    const el = e.target;
    if (el.id === 'songpick') { select(el.value); return; }
    const s = songs.get(currentId); if (!s) return;
    if (el.id === 'f-sig') s.sig = el.value;
    else if (el.dataset.f === 'type') { const i = +el.closest('.part').dataset.i; s.parts[i].type = el.value; }
    else return;
    refreshDerived(); touch(currentId);
  });

  function select(id){
    if (id === currentId) return;
    stopPlay(); confirmDel = false; currentId = id;
    try { localStorage.setItem('teima-estr-cur', id); } catch {}
    render(); window.scrollTo({top: 0});
  }
  function blankSong(title){
    const max = Math.max(0, ...[...songs.values()].map(s => s.sort || 0));
    return {title, bpm: '', sig: '4/4', key: '', notes: '', sort: max + 1, parts: []};
  }
  function addSong(){
    const id = uid();
    songs.set(id, blankSong('Nova música'));
    select(id); touch(id);
    const f = $('#f-title'); if (f) { f.focus(); f.select(); }
  }
  async function deleteSong(id){
    stopPlay(); confirmDel = false;
    songs.delete(id); clearTimeout(timers[id]); dirty.delete(id);
    currentId = ordered()[0]?.[0] || null;
    render();
    if (!offline) {
      try {
        const r = await fetch(API, {method: 'POST', headers: POST, body: JSON.stringify({acao: 'apagar', id})});
        const j = await r.json();
        if (!j.ok) setSaved('Não foi possível apagar');
      } catch { setSaved('Não foi possível apagar'); }
    }
  }

  function normalize(d){
    return {title: '', bpm: '', sig: '4/4', key: '', notes: '', ...d,
      parts: Array.isArray(d.parts) ? d.parts.map(p => {
        const part = {id: uid(), type: 'verso', label: '', bars: '', bpm: '', dur: '', barNotes: [], ...p};
        // migração: uma versão anterior guardava uma única nota por parte
        if (!Array.isArray(part.barNotes) || !part.barNotes.length) {
          part.barNotes = part.notes ? [part.notes] : [];
        }
        return part;
      }) : []};
  }

  /* ---------- arranque ---------- */
  function pickInitial(){
    let saved = null; try { saved = localStorage.getItem('teima-estr-cur'); } catch {}
    if (saved && songs.has(saved)) return saved;
    return ordered()[0]?.[0] || null;
  }

  async function carregar(){
    const r = await fetch(API, {headers: {'Accept': 'application/json'}});
    if (!r.ok) throw new Error('http ' + r.status);
    return r.json();
  }

  async function boot(){
    try {
      const j = await carregar();
      songs.clear();
      Object.entries(j.songs || {}).forEach(([id, d]) => songs.set(id, normalize(d)));
      offline = false;
    } catch (e) {
      offline = true;
    }
    currentId = pickInitial();
    render();

    // Outra pessoa da banda pode ter gravado entretanto: ao voltar à aba,
    // vai buscar de novo (só o que não estiver a ser editado agora mesmo).
    document.addEventListener('visibilitychange', async () => {
      if (document.visibilityState !== 'visible') return;
      try {
        const j = await carregar();
        offline = false;
        const vindos = j.songs || {};
        Object.entries(vindos).forEach(([id, d]) => {
          if (dirty.has(id) || saving[id]) return;
          songs.set(id, normalize(d));
        });
        [...songs.keys()].forEach(id => {
          if (!(id in vindos) && !dirty.has(id) && !saving[id]) songs.delete(id);
        });
        if (!songs.has(currentId)) currentId = pickInitial();
        render();
      } catch {}
    });
  }
  /* ---------- importar ---------- */
  $('#importar').addEventListener('change', async ev => {
    const f = ev.target.files[0]; ev.target.value = '';
    if (!f) return;
    let j; try { j = JSON.parse(await f.text()); } catch { alert('O ficheiro não é das estruturas.'); return; }
    if (!j || typeof j.songs !== 'object') { alert('O ficheiro não é das estruturas.'); return; }
    try {
      const r = await fetch(API, {method: 'POST', headers: POST, body: JSON.stringify({acao: 'importar', songs: j.songs})});
      const k = await r.json();
      if (!k.ok) throw new Error(k.erro || 'falhou');
      alert(k.juntas ? `Importadas ${k.juntas} música(s).` + (k.ja ? ` ${k.ja} já cá estavam e ficaram como estão.` : '') : 'Nada de novo: essas músicas já cá estão.');
      location.reload();
    } catch (e) { alert('Não foi possível importar: ' + e.message); }
  });

  boot();
})();
</script>

</body>
</html>

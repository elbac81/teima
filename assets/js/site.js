const TIPOS = ["Intro", "Verso", "Pré-Refrão", "Refrão", "Break", "Solo", "Ponte", "Instrumental", "Outro"];
const ESTADOS = {
  composicao: "Em composição / projeto",
  ensaio: "Em ensaio",
  pronta: "Pronta a tocar",
};
const API = "/ensaios/api.php";
const POLL_MS = 8000;
const SAVE_DEBOUNCE_MS = 600;

let state = { songs: [], repertorios: [], eventos: [], notasGerais: "", notaPropria: "" };
let currentUser = null;
let currentSongId = null;
let currentRepertorioId = null;
let view = "home";
let saveTimer = null;
let pollTimer = null;
let dirty = false;

function uid() {
  return "id_" + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
}

function escapeHtml(s) {
  return (s || "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
}

function currentSong() {
  return state.songs.find((s) => s.id === currentSongId) || null;
}

function currentRepertorio() {
  return state.repertorios.find((r) => r.id === currentRepertorioId) || null;
}

const SONG_PATH_PREFIX = "/ensaios/musica/";
const REPERTORIO_PATH_PREFIX = "/ensaios/repertorio/";
const REPERTORIOS_LIST_PATH = "/ensaios/repertorios";
const EVENTOS_LIST_PATH = "/ensaios/eventos";
const NOTAS_PATH = "/ensaios/notas";

function songUrl(id) {
  return SONG_PATH_PREFIX + encodeURIComponent(id);
}

function repertorioUrl(id) {
  return REPERTORIO_PATH_PREFIX + encodeURIComponent(id);
}

function songIdFromPath() {
  if (!location.pathname.startsWith(SONG_PATH_PREFIX)) return null;
  const id = location.pathname.slice(SONG_PATH_PREFIX.length).replace(/\/$/, "");
  return id ? decodeURIComponent(id) : null;
}

function repertorioIdFromPath() {
  if (!location.pathname.startsWith(REPERTORIO_PATH_PREFIX)) return null;
  const id = location.pathname.slice(REPERTORIO_PATH_PREFIX.length).replace(/\/$/, "");
  return id ? decodeURIComponent(id) : null;
}

function isRepertoriosListPath() {
  return location.pathname.replace(/\/$/, "") === REPERTORIOS_LIST_PATH;
}

function isEventosListPath() {
  return location.pathname.replace(/\/$/, "") === EVENTOS_LIST_PATH;
}

function setPathForEventosList() {
  if (location.pathname !== EVENTOS_LIST_PATH) history.pushState(null, "", EVENTOS_LIST_PATH);
}

function isNotasPath() {
  return location.pathname.replace(/\/$/, "") === NOTAS_PATH;
}

function setPathForNotas() {
  if (location.pathname !== NOTAS_PATH) history.pushState(null, "", NOTAS_PATH);
}

function setPathForSong(id) {
  const target = id ? songUrl(id) : "/ensaios/";
  if (location.pathname !== target) history.pushState(null, "", target);
}

function setPathForRepertorio(id) {
  const target = id ? repertorioUrl(id) : REPERTORIOS_LIST_PATH;
  if (location.pathname !== target) history.pushState(null, "", target);
}

function setPathForRepertoriosList() {
  if (location.pathname !== REPERTORIOS_LIST_PATH) history.pushState(null, "", REPERTORIOS_LIST_PATH);
}

function formatDate(iso) {
  if (!iso) return "";
  const [y, m, d] = iso.split("-");
  return d && m && y ? `${d}/${m}/${y}` : iso;
}

function setSyncStatus(kind, label) {
  const el = document.getElementById("syncStatus");
  if (!el) return;
  el.classList.toggle("err", kind === "err");
  el.querySelector(".label").textContent = label;
}

async function loadState({ silent } = {}) {
  try {
    const res = await fetch(API, { cache: "no-store" });
    if (!res.ok) throw new Error("http " + res.status);
    const data = await res.json();
    state = data && Array.isArray(data.songs) ? data : { songs: [] };
    if (!Array.isArray(state.repertorios)) state.repertorios = [];
    if (!Array.isArray(state.eventos)) state.eventos = [];
    if (typeof state.notasGerais !== "string") state.notasGerais = "";
    if (typeof state.notaPropria !== "string") state.notaPropria = "";
    currentUser = data && data.user ? data.user : null;
    setSyncStatus("ok", "sincronizado");
    return true;
  } catch (e) {
    if (!silent) setSyncStatus("err", "sem ligação ao servidor");
    return false;
  }
}

function scheduleSave() {
  dirty = true;
  clearTimeout(saveTimer);
  saveTimer = setTimeout(saveState, SAVE_DEBOUNCE_MS);
}

async function saveState() {
  clearTimeout(saveTimer);
  setSyncStatus("ok", "a guardar…");
  try {
    const res = await fetch(API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(state),
    });
    if (!res.ok) throw new Error("http " + res.status);
    dirty = false;
    setSyncStatus("ok", "guardado");
  } catch (e) {
    setSyncStatus("err", "falha ao guardar — tenta de novo");
  }
}

function anyFieldFocused() {
  const el = document.activeElement;
  return el && (el.tagName === "INPUT" || el.tagName === "TEXTAREA" || el.tagName === "SELECT");
}

async function pollForUpdates() {
  if (dirty || anyFieldFocused()) return;
  const prevSongId = currentSongId;
  const prevRepId = currentRepertorioId;
  await loadState({ silent: true });
  if (prevSongId && !state.songs.find((s) => s.id === prevSongId)) {
    currentSongId = null;
  }
  if (prevRepId && !state.repertorios.find((r) => r.id === prevRepId)) {
    currentRepertorioId = null;
    if (view === "repertorio") view = "repertorios";
  }
  renderMain();
}

function renderHomeGrid() {
  const songs = [...state.songs].sort((a, b) => (a.order || 0) - (b.order || 0));
  if (songs.length === 0) {
    return `
    <div class="empty-state">
      <span class="eyebrow">Teima</span>
      <h2>Notas de ensaio</h2>
      <p>Ainda sem músicas. Cria a primeira.</p>
    </div>`;
  }
  return `
    <div class="song-grid">
      ${songs
        .map(
          (s) => `
      <a class="song-card" data-id="${s.id}" href="${songUrl(s.id)}">
        <span class="t"><span class="status-dot ${s.estado || "composicao"}" title="${ESTADOS[s.estado] || ESTADOS.composicao}"></span>${escapeHtml(s.title || "Sem título")}</span>
        <span class="meta">
          <span class="artist">${escapeHtml(s.artist || "")}</span>
          ${s.tom ? `<span class="tom-pill">${escapeHtml(s.tom)}</span>` : ""}
        </span>
      </a>`
        )
        .join("")}
    </div>`;
}

function renderRepertoriosList(main) {
  const reps = [...state.repertorios].sort((a, b) => (b.data || "").localeCompare(a.data || "") || (a.order || 0) - (b.order || 0));
  main.innerHTML = `
    <a class="back-link" href="/ensaios/" id="backLink">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
      Todas as músicas
    </a>
    <div class="repertorios-head">
      <h2>Repertórios</h2>
      <button class="new-song-btn page-btn" id="newRepertorioBtn">+ Novo repertório</button>
    </div>
    ${
      reps.length === 0
        ? '<div class="no-sections">Ainda sem repertórios. Cria o primeiro.</div>'
        : `<div class="repertorio-grid">
          ${reps
            .map(
              (r) => `
          <a class="repertorio-card" data-id="${r.id}" href="${repertorioUrl(r.id)}">
            <span class="t">${escapeHtml(r.nome || "Sem nome")}</span>
            <span class="meta">
              ${r.data ? `<span>${formatDate(r.data)}</span>` : ""}
              ${r.local ? `<span>${escapeHtml(r.local)}</span>` : ""}
            </span>
            <span class="rep-count">${(r.musicas || []).length} música${(r.musicas || []).length === 1 ? "" : "s"}</span>
          </a>`
            )
            .join("")}
        </div>`
    }
  `;
  document.getElementById("backLink").addEventListener("click", (e) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    currentRepertorioId = null;
    view = "home";
    setPathForSong(null);
    renderMain();
  });
  document.getElementById("newRepertorioBtn").addEventListener("click", addNewRepertorio);
  main.querySelectorAll(".repertorio-card").forEach((card) => {
    card.addEventListener("click", (e) => {
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      e.preventDefault();
      currentRepertorioId = card.dataset.id;
      view = "repertorio";
      setPathForRepertorio(currentRepertorioId);
      renderMain();
    });
  });
}

function renderRepertorioDetail(main, rep) {
  rep.musicas = rep.musicas || [];
  const musicas = rep.musicas;
  const availableSongs = state.songs.filter((s) => !musicas.includes(s.id));
  main.innerHTML = `
    <a class="back-link" href="${REPERTORIOS_LIST_PATH}" id="backToRepertorios">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
      Repertórios
    </a>
    <div class="song-header">
      <div class="title-row">
        <input class="title-field" id="repNomeInput" placeholder="Nome do repertório" value="${escapeHtml(rep.nome || "")}" />
        <button class="print-btn" id="repPrintBtn" title="Guardar e imprimir / PDF">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        </button>
        <button class="mini-btn del" id="repDeleteBtn" title="Eliminar repertório">✕</button>
      </div>
      <div class="field-row">
        <div class="field"><label>Data</label><input type="date" id="repDataInput" value="${escapeHtml(rep.data || "")}" /></div>
        <div class="field"><label>Local</label><input id="repLocalInput" placeholder="ex. Sala Tejo" value="${escapeHtml(rep.local || "")}" /></div>
      </div>
    </div>
    <div class="repertorio-picker">
      <label class="field-label">Adicionar música</label>
      <select id="repAddSongSelect">
        <option value="">Escolher música…</option>
        ${availableSongs.map((s) => `<option value="${s.id}">${escapeHtml(s.title || "Sem título")}</option>`).join("")}
      </select>
    </div>
    <div class="structure" id="repList">
      ${
        musicas.length === 0
          ? '<div class="no-sections">Ainda sem músicas neste repertório.</div>'
          : musicas
              .map((songId, i) => {
                const song = state.songs.find((s) => s.id === songId);
                return `
        <div class="section-card rep-item" data-id="${songId}">
          <div class="spine-col">
            <div class="spine-dot"></div>
            <div class="spine-line"></div>
          </div>
          <div class="section-body">
            <div class="section-top">
              <span class="rep-item-title">${i + 1}. ${escapeHtml(song ? song.title || "Sem título" : "(música removida)")}</span>
              <div class="section-actions">
                <button class="mini-btn up" title="Mover para cima">↑</button>
                <button class="mini-btn down" title="Mover para baixo">↓</button>
                <button class="mini-btn del" title="Remover do repertório">✕</button>
              </div>
            </div>
          </div>
        </div>`;
              })
              .join("")
      }
    </div>
  `;

  document.getElementById("backToRepertorios").addEventListener("click", (e) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    currentRepertorioId = null;
    view = "repertorios";
    setPathForRepertoriosList();
    renderMain();
  });
  document.getElementById("repNomeInput").addEventListener("input", (e) => {
    rep.nome = e.target.value;
    scheduleSave();
  });
  document.getElementById("repDataInput").addEventListener("input", (e) => {
    rep.data = e.target.value;
    scheduleSave();
  });
  document.getElementById("repLocalInput").addEventListener("input", (e) => {
    rep.local = e.target.value;
    scheduleSave();
  });
  document.getElementById("repPrintBtn").addEventListener("click", async () => {
    await saveState();
    window.print();
  });
  document.getElementById("repDeleteBtn").addEventListener("click", () => {
    if (!confirm("Eliminar este repertório?")) return;
    state.repertorios = state.repertorios.filter((r) => r.id !== rep.id);
    currentRepertorioId = null;
    view = "repertorios";
    setPathForRepertoriosList();
    saveState();
    renderMain();
  });
  document.getElementById("repAddSongSelect").addEventListener("change", (e) => {
    const songId = e.target.value;
    if (!songId) return;
    if (!musicas.includes(songId)) musicas.push(songId);
    saveState();
    renderMain();
  });
  main.querySelectorAll("#repList .rep-item").forEach((card, i) => {
    const songId = card.dataset.id;
    card.querySelector(".up").addEventListener("click", () => moveRepertorioSong(rep, i, -1));
    card.querySelector(".down").addEventListener("click", () => moveRepertorioSong(rep, i, 1));
    card.querySelector(".del").addEventListener("click", () => {
      rep.musicas = rep.musicas.filter((id) => id !== songId);
      saveState();
      renderMain();
    });
  });
}

function moveRepertorioSong(rep, index, dir) {
  const other = index + dir;
  const list = rep.musicas;
  if (other < 0 || other >= list.length) return;
  const tmp = list[index];
  list[index] = list[other];
  list[other] = tmp;
  saveState();
  renderMain();
}

function addNewRepertorio() {
  const orders = state.repertorios.map((r) => r.order || 0);
  const nextOrder = orders.length ? Math.max(...orders) + 1 : 0;
  const rep = { id: uid(), nome: "Novo repertório", data: "", local: "", order: nextOrder, musicas: [] };
  state.repertorios.push(rep);
  currentRepertorioId = rep.id;
  view = "repertorio";
  setPathForRepertorio(rep.id);
  saveState();
  renderMain();
}

function renderEventosList(main) {
  const eventos = [...state.eventos].sort((a, b) => (a.data || "9999-99-99").localeCompare(b.data || "9999-99-99"));
  main.innerHTML = `
    <a class="back-link" href="/ensaios/" id="backLink">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
      Todas as músicas
    </a>
    <div class="repertorios-head">
      <h2>Eventos</h2>
      <button class="new-song-btn page-btn" id="newEventoBtn">+ Novo evento</button>
    </div>
    <p class="events-hint">Estes eventos aparecem publicamente no calendário de teima.space.</p>
    <div class="structure" id="eventosStructure">
      ${
        eventos.length === 0
          ? '<div class="no-sections">Ainda sem eventos. Cria o primeiro.</div>'
          : eventos
              .map(
                (ev) => `
        <div class="section-card" data-id="${ev.id}">
          <div class="spine-col">
            <div class="spine-dot"></div>
            <div class="spine-line"></div>
          </div>
          <div class="section-body">
            <div class="section-top">
              <input class="evento-input date" type="date" data-field="data" value="${escapeHtml(ev.data || "")}" />
              <input class="evento-input time" type="time" data-field="hora" value="${escapeHtml(ev.hora || "")}" />
              <div class="section-actions">
                <button class="mini-btn del" title="Eliminar evento">✕</button>
              </div>
            </div>
            <div class="section-fields">
              <input class="evento-input" data-field="titulo" placeholder="Nome do evento / concerto" value="${escapeHtml(ev.titulo || "")}" />
              <input class="evento-input" data-field="local" placeholder="Local" value="${escapeHtml(ev.local || "")}" />
              <input class="evento-input" data-field="link" placeholder="Link (bilhetes, evento, etc.) — opcional" value="${escapeHtml(ev.link || "")}" />
            </div>
          </div>
        </div>`
              )
              .join("")
      }
    </div>
  `;
  document.getElementById("backLink").addEventListener("click", (e) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    view = "home";
    setPathForSong(null);
    renderMain();
  });
  document.getElementById("newEventoBtn").addEventListener("click", addNewEvento);
  main.querySelectorAll("#eventosStructure .section-card").forEach((card) => {
    const id = card.dataset.id;
    const ev = state.eventos.find((e) => e.id === id);
    card.querySelectorAll("[data-field]").forEach((input) => {
      input.addEventListener("input", (e) => {
        ev[e.target.dataset.field] = e.target.value;
        scheduleSave();
      });
    });
    card.querySelector(".del").addEventListener("click", () => {
      if (!confirm("Eliminar este evento?")) return;
      state.eventos = state.eventos.filter((e) => e.id !== id);
      saveState();
      renderMain();
    });
  });
}

function addNewEvento() {
  const evento = { id: uid(), titulo: "", data: "", hora: "", local: "", link: "" };
  state.eventos.push(evento);
  saveState();
  renderMain();
}

function renderNotasView(main) {
  main.innerHTML = `
    <a class="back-link" href="/ensaios/" id="backLink">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
      Todas as músicas
    </a>
    <div class="repertorios-head">
      <h2>Notas</h2>
    </div>
    <div class="notas-block">
      <label class="field-label">Notas gerais <span class="notas-visibility">— vê toda a gente</span></label>
      <textarea class="notas-textarea" id="notasGeraisInput" rows="6" placeholder="Notas partilhadas por toda a banda (avisos, ideias, recados de ensaio…)">${escapeHtml(state.notasGerais || "")}</textarea>
    </div>
    <div class="notas-block">
      <label class="field-label">As minhas notas ${currentUser ? `<span class="notas-visibility">— só ${escapeHtml(currentUser)} vê isto</span>` : ""}</label>
      <textarea class="notas-textarea" id="notaPropriaInput" rows="6" placeholder="Notas privadas, só tu vês (mesmo que outros usem a mesma password).">${escapeHtml(state.notaPropria || "")}</textarea>
    </div>
  `;
  document.getElementById("backLink").addEventListener("click", (e) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    view = "home";
    setPathForSong(null);
    renderMain();
  });
  document.getElementById("notasGeraisInput").addEventListener("input", (e) => {
    state.notasGerais = e.target.value;
    scheduleSave();
  });
  document.getElementById("notaPropriaInput").addEventListener("input", (e) => {
    state.notaPropria = e.target.value;
    scheduleSave();
  });
}

function renderMain() {
  const main = document.getElementById("main");
  if (view === "notas") {
    renderNotasView(main);
    return;
  }
  if (view === "eventos") {
    renderEventosList(main);
    return;
  }
  if (view === "repertorios") {
    renderRepertoriosList(main);
    return;
  }
  if (view === "repertorio") {
    const rep = currentRepertorio();
    if (rep) {
      renderRepertorioDetail(main, rep);
      return;
    }
    view = "repertorios";
    renderRepertoriosList(main);
    return;
  }
  const song = currentSong();
  if (!song) {
    main.innerHTML = renderHomeGrid();
    main.querySelectorAll(".song-card").forEach((card) => {
      card.addEventListener("click", (e) => {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        currentSongId = card.dataset.id;
        currentRepertorioId = null;
        view = "song";
        setPathForSong(currentSongId);
        renderMain();
      });
    });
    return;
  }

  const sections = [...(song.sections || [])].sort((a, b) => (a.order || 0) - (b.order || 0));

  const estado = song.estado || "composicao";
  main.innerHTML = `
    <a class="back-link" href="/ensaios/" id="backLink">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
      Todas as músicas
    </a>
    <div class="song-header">
      <div class="title-row">
        <input class="title-field" id="titleInput" placeholder="Título da música" value="${escapeHtml(song.title || "")}" />
        <button class="print-btn" id="printBtn" title="Imprimir / PDF">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        </button>
      </div>
      <div class="field-row">
        <div class="field"><label>Tom</label><input id="tomInput" placeholder="ex. Sol" value="${escapeHtml(song.tom || "")}" /></div>
        <div class="field"><label>BPM</label><input id="bpmInput" placeholder="ex. 96" inputmode="numeric" value="${escapeHtml(song.bpm || "")}" /></div>
        <div class="field">
          <label>Estado</label>
          <select id="estadoInput" class="estado-select ${estado}">
            ${Object.entries(ESTADOS).map(([v, label]) => `<option value="${v}" ${v === estado ? "selected" : ""}>${label}</option>`).join("")}
          </select>
        </div>
      </div>
    </div>
    <div class="structure" id="structure">
      ${
        sections.length === 0
          ? '<div class="no-sections">Ainda sem secções. Adiciona a primeira (intro, verso…).</div>'
          : sections
              .map((sec) => {
                return `
        <div class="section-card" data-id="${sec.id}">
          <div class="spine-col">
            <div class="spine-dot"></div>
            <div class="spine-line"></div>
          </div>
          <div class="section-body">
            <div class="section-top">
              <select class="type-select" data-field="tipo">
                ${TIPOS.map((t) => `<option value="${t}" ${t === sec.tipo ? "selected" : ""}>${t}</option>`).join("")}
              </select>
              <input class="tom-field" data-field="tom" placeholder="tom" value="${escapeHtml(sec.tom || "")}" />
              <div class="section-actions">
                <button class="mini-btn up" title="Mover para cima">↑</button>
                <button class="mini-btn down" title="Mover para baixo">↓</button>
                <button class="mini-btn del" title="Eliminar secção">✕</button>
              </div>
            </div>
            <div class="section-fields">
              <textarea class="cifra" data-field="cifra" rows="8" placeholder="        G          D&#10;Escreve os acordes acima da letra&#10;        Em         C&#10;linha a linha, acorde sobre a palavra">${escapeHtml(sec.cifra || "")}</textarea>
              <label class="field-label">Notas</label>
              <textarea class="notas" data-field="notas" rows="1" placeholder="Notas de ensaio (dinâmica, quem canta, dica de execução…)">${escapeHtml(sec.notas || "")}</textarea>
            </div>
          </div>
        </div>`;
              })
              .join("")
      }
    </div>
    <button class="add-section-btn" id="addSectionBtn">+ Adicionar secção</button>
  `;

  document.getElementById("titleInput").addEventListener("input", (e) => {
    song.title = e.target.value;
    scheduleSave();
  });
  document.getElementById("tomInput").addEventListener("input", (e) => {
    song.tom = e.target.value;
    scheduleSave();
  });
  document.getElementById("bpmInput").addEventListener("input", (e) => {
    song.bpm = e.target.value;
    scheduleSave();
  });
  document.getElementById("estadoInput").addEventListener("change", (e) => {
    song.estado = e.target.value;
    e.target.className = "estado-select " + e.target.value;
    scheduleSave();
  });
  document.getElementById("printBtn").addEventListener("click", () => window.print());
  document.getElementById("backLink").addEventListener("click", (e) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    currentSongId = null;
    view = "home";
    setPathForSong(null);
    renderMain();
  });
  document.getElementById("addSectionBtn").addEventListener("click", () => addSection(song));

  document.querySelectorAll("#structure .section-card").forEach((card, i, all) => {
    const secId = card.dataset.id;
    const sec = sections.find((s) => s.id === secId);
    card.querySelector('[data-field="tipo"]').addEventListener("change", (e) => {
      sec.tipo = e.target.value;
      scheduleSave();
    });
    card.querySelector('[data-field="tom"]').addEventListener("input", (e) => {
      sec.tom = e.target.value;
      scheduleSave();
    });
    card.querySelector('[data-field="cifra"]').addEventListener("input", (e) => {
      sec.cifra = e.target.value;
      scheduleSave();
    });
    card.querySelector('[data-field="notas"]').addEventListener("input", (e) => {
      sec.notas = e.target.value;
      scheduleSave();
    });
    card.querySelector(".up").addEventListener("click", () => moveSection(song, i, -1, sections));
    card.querySelector(".down").addEventListener("click", () => moveSection(song, i, 1, sections));
    card.querySelector(".del").addEventListener("click", () => deleteSection(song, secId));
  });
}

function moveSection(song, index, dir, sections) {
  const other = index + dir;
  if (other < 0 || other >= sections.length) return;
  const a = sections[index];
  const b = sections[other];
  const tmp = a.order;
  a.order = b.order;
  b.order = tmp;
  saveState();
  renderMain();
}

function addSection(song) {
  const orders = (song.sections || []).map((s) => s.order || 0);
  const nextOrder = orders.length ? Math.max(...orders) + 1 : 0;
  song.sections = song.sections || [];
  song.sections.push({ id: uid(), tipo: "Verso", tom: "", cifra: "", notas: "", order: nextOrder });
  saveState();
  renderMain();
}

function deleteSection(song, id) {
  song.sections = (song.sections || []).filter((s) => s.id !== id);
  saveState();
  renderMain();
}

function addNewSong() {
  const orders = state.songs.map((s) => s.order || 0);
  const nextOrder = orders.length ? Math.max(...orders) + 1 : 0;
  const song = { id: uid(), title: "Nova música", artist: "", tom: "", bpm: "", estado: "composicao", order: nextOrder, sections: [] };
  state.songs.push(song);
  currentSongId = song.id;
  currentRepertorioId = null;
  view = "song";
  setPathForSong(currentSongId);
  saveState();
  renderMain();
}

function applyPath() {
  const songId = songIdFromPath();
  const repId = repertorioIdFromPath();
  if (songId && state.songs.find((s) => s.id === songId)) {
    currentSongId = songId;
    currentRepertorioId = null;
    view = "song";
  } else if (repId && state.repertorios.find((r) => r.id === repId)) {
    currentRepertorioId = repId;
    currentSongId = null;
    view = "repertorio";
  } else if (isRepertoriosListPath() || repId) {
    currentSongId = null;
    currentRepertorioId = null;
    view = "repertorios";
  } else if (isEventosListPath()) {
    currentSongId = null;
    currentRepertorioId = null;
    view = "eventos";
  } else if (isNotasPath()) {
    currentSongId = null;
    currentRepertorioId = null;
    view = "notas";
  } else {
    currentSongId = null;
    currentRepertorioId = null;
    view = "home";
  }
  renderMain();
}

function showPrintText() {
  document.querySelectorAll("#structure textarea").forEach((ta) => {
    const div = document.createElement("div");
    div.className = "print-text " + ta.className;
    div.textContent = ta.value;
    ta.insertAdjacentElement("afterend", div);
    ta.classList.add("print-hidden");
  });
}

function hidePrintText() {
  document.querySelectorAll(".print-text").forEach((div) => div.remove());
  document.querySelectorAll(".print-hidden").forEach((ta) => ta.classList.remove("print-hidden"));
}

async function init() {
  document.getElementById("newSongBtn").addEventListener("click", () => {
    const menu = document.getElementById("menuToggle");
    if (menu) menu.open = false;
    addNewSong();
  });
  document.getElementById("homeLink").addEventListener("click", (e) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    currentSongId = null;
    currentRepertorioId = null;
    view = "home";
    setPathForSong(null);
    renderMain();
  });
  document.getElementById("repertoriosLink").addEventListener("click", (e) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    const menu = document.getElementById("menuToggle");
    if (menu) menu.open = false;
    currentSongId = null;
    currentRepertorioId = null;
    view = "repertorios";
    setPathForRepertoriosList();
    renderMain();
  });
  document.getElementById("eventosLink").addEventListener("click", (e) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    const menu = document.getElementById("menuToggle");
    if (menu) menu.open = false;
    currentSongId = null;
    currentRepertorioId = null;
    view = "eventos";
    setPathForEventosList();
    renderMain();
  });
  document.getElementById("notasLink").addEventListener("click", (e) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    const menu = document.getElementById("menuToggle");
    if (menu) menu.open = false;
    currentSongId = null;
    currentRepertorioId = null;
    view = "notas";
    setPathForNotas();
    renderMain();
  });
  setSyncStatus("ok", "a carregar…");
  await loadState();
  applyPath();
  window.addEventListener("popstate", applyPath);
  window.addEventListener("beforeprint", showPrintText);
  window.addEventListener("afterprint", hidePrintText);
  pollTimer = setInterval(pollForUpdates, POLL_MS);
  window.addEventListener("beforeunload", () => {
    if (dirty) {
      navigator.sendBeacon(API, new Blob([JSON.stringify(state)], { type: "application/json" }));
    }
  });
}

init();

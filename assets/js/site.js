const TIPOS = ["Intro", "Verso", "Pré-Refrão", "Refrão", "Break", "Solo", "Ponte", "Instrumental", "Outro"];
const ESTADOS = {
  composicao: "Em composição / projeto",
  ensaio: "Em ensaio",
  pronta: "Pronta a tocar",
};
const API = "api.php";
const POLL_MS = 8000;
const SAVE_DEBOUNCE_MS = 600;

let state = { songs: [] };
let currentSongId = null;
let saveTimer = null;
let pollTimer = null;
let dirty = false;
let expandedSections = new Set();

function uid() {
  return "id_" + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
}

function escapeHtml(s) {
  return (s || "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
}

function currentSong() {
  return state.songs.find((s) => s.id === currentSongId) || null;
}

const SONG_PATH_PREFIX = "/ensaios/musica/";

function songUrl(id) {
  return SONG_PATH_PREFIX + encodeURIComponent(id);
}

function songIdFromPath() {
  if (!location.pathname.startsWith(SONG_PATH_PREFIX)) return null;
  const id = location.pathname.slice(SONG_PATH_PREFIX.length).replace(/\/$/, "");
  return id ? decodeURIComponent(id) : null;
}

function setPathForSong(id) {
  const target = id ? songUrl(id) : "/ensaios/";
  if (location.pathname !== target) history.pushState(null, "", target);
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
  await loadState({ silent: true });
  renderSongList();
  if (prevSongId && !state.songs.find((s) => s.id === prevSongId)) {
    currentSongId = null;
    renderMain();
  } else if (prevSongId) {
    renderMain();
  }
}

function renderSongList() {
  const el = document.getElementById("songList");
  const songs = [...state.songs].sort((a, b) => (a.order || 0) - (b.order || 0));
  if (songs.length === 0) {
    el.innerHTML = '<div class="rail-empty">Ainda sem músicas. Cria a primeira.</div>';
    return;
  }
  el.innerHTML = songs
    .map(
      (s) => `
    <a class="song-item ${s.id === currentSongId ? "active" : ""}" data-id="${s.id}" href="${songUrl(s.id)}">
      <span class="t"><span class="status-dot ${s.estado || "composicao"}" title="${ESTADOS[s.estado] || ESTADOS.composicao}"></span>${escapeHtml(s.title || "Sem título")}</span>
      <span class="meta">
        <span class="artist">${escapeHtml(s.artist || "")}</span>
        ${s.tom ? `<span class="tom-pill">${escapeHtml(s.tom)}</span>` : ""}
      </span>
    </a>`
    )
    .join("");
  el.querySelectorAll(".song-item").forEach((link) => {
    link.addEventListener("click", (e) => {
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      e.preventDefault();
      currentSongId = link.dataset.id;
      setPathForSong(currentSongId);
      const dropdown = document.getElementById("songDropdown");
      if (dropdown) dropdown.open = false;
      renderSongList();
      renderMain();
    });
  });
}

function renderMain() {
  const main = document.getElementById("main");
  const song = currentSong();
  if (!song) {
    main.innerHTML = `<div class="empty-state"></div>`;
    return;
  }

  const sections = [...(song.sections || [])].sort((a, b) => (a.order || 0) - (b.order || 0));

  const estado = song.estado || "composicao";
  main.innerHTML = `
    <div class="song-header">
      <div class="title-row">
        <input class="title-field" id="titleInput" placeholder="Título da música" value="${escapeHtml(song.title || "")}" />
        <button class="print-btn" id="printBtn" title="Imprimir / PDF">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        </button>
      </div>
      <input class="artist-field" id="artistInput" placeholder="Artista / autor" value="${escapeHtml(song.artist || "")}" />
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
                const open = expandedSections.has(sec.id);
                return `
        <div class="section-card ${open ? "open" : ""}" data-id="${sec.id}">
          <div class="spine-col">
            <div class="spine-dot"></div>
            <div class="spine-line"></div>
          </div>
          <div class="section-body">
            <div class="section-top">
              <button class="mini-btn toggle" title="${open ? "Fechar" : "Abrir"} secção">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
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
              <textarea class="cifra" data-field="cifra" rows="4" placeholder="        G          D&#10;Escreve os acordes acima da letra&#10;        Em         C&#10;linha a linha, tal como numa cifra">${escapeHtml(sec.cifra || "")}</textarea>
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
    renderSongList();
  });
  document.getElementById("artistInput").addEventListener("input", (e) => {
    song.artist = e.target.value;
    scheduleSave();
    renderSongList();
  });
  document.getElementById("tomInput").addEventListener("input", (e) => {
    song.tom = e.target.value;
    scheduleSave();
    renderSongList();
  });
  document.getElementById("bpmInput").addEventListener("input", (e) => {
    song.bpm = e.target.value;
    scheduleSave();
  });
  document.getElementById("estadoInput").addEventListener("change", (e) => {
    song.estado = e.target.value;
    e.target.className = "estado-select " + e.target.value;
    scheduleSave();
    renderSongList();
  });
  document.getElementById("printBtn").addEventListener("click", () => window.print());
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
    card.querySelector(".toggle").addEventListener("click", () => {
      if (expandedSections.has(secId)) expandedSections.delete(secId);
      else expandedSections.add(secId);
      card.classList.toggle("open");
    });
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
  setPathForSong(currentSongId);
  saveState();
  renderSongList();
  renderMain();
}

function applyPath() {
  const id = songIdFromPath();
  if (id && state.songs.find((s) => s.id === id)) {
    currentSongId = id;
  } else if (!id) {
    currentSongId = null;
  }
  renderSongList();
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
  document.getElementById("newSongBtn").addEventListener("click", addNewSong);
  setSyncStatus("ok", "a carregar…");
  await loadState();
  const initialId = songIdFromPath();
  if (initialId && state.songs.find((s) => s.id === initialId)) currentSongId = initialId;
  renderSongList();
  renderMain();
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

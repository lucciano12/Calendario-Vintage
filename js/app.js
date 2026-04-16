// ══════════════════════════════════════════════
//   CALENDARIO DE NOTAS XP — app.js
//   BLOQUE 6: Conectado a api/notas.php (PHP + MySQL)
//
//   Flujo:
//   1. init() → apiGetNotas() → fetch GET api/notas.php
//   2. notas[] cargadas en memoria
//   3. renderList() pinta el panel izquierdo
//   4. Al seleccionar nota → renderEditor()
//   5. Al escribir → autoSave() → apiActualizarNota()
//   6. scheduleReminder() pone un setTimeout por cada nota
// ══════════════════════════════════════════════

// ── ESTADO GLOBAL ─────────────────────────────
let notes       = [];     // array de notas en memoria
let currentId   = null;   // id de la nota activa en el editor
let notifTimers = {};      // timers de recordatorio por id
let filter      = 'all';  // filtro activo en la lista
let snoozedNote = null;   // nota pospuesta

const API_URL = 'api/notas.php';

// Categorías disponibles
const CATS = [
  { id:'general',  label:'General'  },
  { id:'trabajo',  label:'Trabajo'  },
  { id:'personal', label:'Personal' },
  { id:'ideas',    label:'Ideas'    },
  { id:'dev',      label:'Dev'      },
  { id:'fitness',  label:'Fitness'  },
];

// ── UTILS ──────────────────────────────────────
function escHtml(s) {
  // Previene XSS al insertar contenido de usuario en el DOM
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function timeAgo(iso) {
  if(!iso) return '';
  const d = (Date.now() - new Date(iso).getTime()) / 1000;
  if(d < 60)    return 'hace unos segundos';
  if(d < 3600)  return `hace ${Math.floor(d/60)} min`;
  if(d < 86400) return `hace ${Math.floor(d/3600)} h`;
  return `hace ${Math.floor(d/86400)} d`;
}

// ── RELOJ ─────────────────────────────────────
function updateClock() {
  const now = new Date();
  const t = now.toLocaleTimeString('es-CL',{hour:'2-digit',minute:'2-digit'});
  const d = now.toLocaleDateString('es-CL',{day:'2-digit',month:'2-digit',year:'numeric'});
  const el = document.getElementById('taskbarClock');
  if(el) el.innerHTML = `<span>${t}</span><span style="font-size:9px">${d}</span>`;
  const cs = document.getElementById('clockStatus');
  if(cs) cs.textContent = `${t} - ${d}`;
}
setInterval(updateClock, 1000);
updateClock();

// ── NOTIFICACIONES ────────────────────────────
function updateNotifStatus() {
  const dot   = document.getElementById('notifDot');
  const label = document.getElementById('notifLabel');
  if(!dot || !label) return;
  const p = Notification.permission;
  if(p === 'granted') {
    dot.className = 'xp-status-dot green';
    label.textContent = 'Notificaciones: activas';
  } else if(p === 'denied') {
    dot.className = 'xp-status-dot red';
    label.textContent = 'Notificaciones: bloqueadas';
  } else {
    dot.className = 'xp-status-dot yellow';
    label.textContent = 'Notificaciones: pendiente';
  }
}

function requestNotifPermission() {
  if(!('Notification' in window)) {
    showBalloon('Sistema', 'Tu navegador no soporta notificaciones de escritorio.', '⚠️');
    return;
  }
  Notification.requestPermission().then(p => {
    updateNotifStatus();
    if(p === 'granted') {
      showBalloon('Notificaciones activadas', 'Recibirás avisos en tu escritorio.', '✅');
    } else {
      showBalloon('Permiso denegado', 'Actívalas desde la barra del navegador.', '🔕');
    }
  });
}

// ── BALLOON / TOAST ─────────────────────────────
function showBalloon(title, msg, icon) {
  icon = icon || '📌';
  const c = document.getElementById('balloonContainer');
  if(!c) return;
  const b = document.createElement('div');
  b.className = 'xp-balloon';
  b.innerHTML = `
    <button class="xp-balloon-close" onclick="this.parentElement.remove()">✕</button>
    <div class="xp-balloon-icon">${icon}</div>
    <div class="xp-balloon-body">
      <div class="xp-balloon-title">${escHtml(title)}</div>
      <div class="xp-balloon-msg">${escHtml(msg)}</div>
    </div>`;
  c.appendChild(b);
  setTimeout(() => b.remove(), 5000);
}

// ── SONIDO ──────────────────────────────────────
function playAlert() {
  try {
    var ctx = new (window.AudioContext || window.webkitAudioContext)();
    [523.25, 659.25, 783.99, 1046.5].forEach(function(freq, i) {
      var osc  = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain); gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(freq, ctx.currentTime + i * 0.12);
      gain.gain.setValueAtTime(0.3, ctx.currentTime + i * 0.12);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.12 + 0.25);
      osc.start(ctx.currentTime + i * 0.12);
      osc.stop(ctx.currentTime + i * 0.12 + 0.3);
    });
  } catch(e) {}
}

// ── STATUS BAR ────────────────────────────────
function setStatus(msg) {
  var el = document.getElementById('statusMsg');
  if(el) el.textContent = msg;
}

// ── API CALLS (fetch → PHP) ────────────────────
// Los 4 métodos HTTP del CRUD:
// GET    → leer          → apiGetNotas()
// POST   → crear         → apiCrearNota()
// PUT    → actualizar    → apiActualizarNota()
// DELETE → eliminar      → apiEliminarNota()

async function apiGetNotas() {
  var r = await fetch(API_URL);
  var data = await r.json();
  return data.notas || [];
}

async function apiCrearNota(nota) {
  var r = await fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(nota)
  });
  return await r.json();
}

async function apiActualizarNota(id, nota) {
  var r = await fetch(API_URL + '?id=' + id, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(nota)
  });
  return await r.json();
}

async function apiEliminarNota(id) {
  var r = await fetch(API_URL + '?id=' + id, { method: 'DELETE' });
  return await r.json();
}

// ── NOTES CRUD ─────────────────────────────────
async function newNote() {
  var now = new Date();
  var nota = {
    titulo:      'Nueva nota',
    descripcion: '',
    categoria:   'general',
    prioridad:   'media',
    fecha:       now.toISOString().slice(0,10),
    hora:        now.toTimeString().slice(0,5),
  };
  var res = await apiCrearNota(nota);
  if(res.id) {
    var n = Object.assign({}, nota, { id: res.id, creado_en: now.toISOString() });
    notes.unshift(n);
    selectNote(n.id);
    renderList();
    showBalloon('Nueva nota creada', 'Escribe el título y descripción.', '📝');
    setStatus('Nueva nota creada');
    setTimeout(function() { var t = document.getElementById('titleInput'); if(t) t.focus(); }, 50);
  } else {
    showBalloon('Error', res.mensaje || 'No se pudo crear la nota', '❌');
  }
}

async function deleteNote(id, e) {
  if(e) e.stopPropagation();
  if(!confirm('¿Eliminar esta nota?')) return;
  var res = await apiEliminarNota(id);
  if(!res.error) {
    notes = notes.filter(function(n){ return n.id !== id; });
    if(notifTimers[id]) { clearTimeout(notifTimers[id]); delete notifTimers[id]; }
    if(currentId === id) { currentId = null; renderEditor(); }
    renderList();
    showBalloon('Nota eliminada', '', '🗑️');
    setStatus('Nota eliminada.');
  }
}

function deleteCurrentNote() {
  if(!currentId) return showBalloon('Sin selección', 'Primero selecciona una nota.', '⚠️');
  deleteNote(currentId);
}

async function saveCurrentNote(showFeedback) {
  if(!currentId) return;
  var n = notes.find(function(x){ return x.id === currentId; });
  if(!n) return;
  var titleEl   = document.getElementById('titleInput');
  var contentEl = document.getElementById('contentInput');
  var reminderEl = document.getElementById('reminderInput');
  if(titleEl)   n.titulo      = titleEl.value || '';
  if(contentEl) n.descripcion = contentEl.value || '';
  if(reminderEl && reminderEl.value) {
    var dt = new Date(reminderEl.value);
    n.fecha = dt.toISOString().slice(0,10);
    n.hora  = dt.toTimeString().slice(0,5);
  }
  var res = await apiActualizarNota(n.id, n);
  if(!res.error) {
    scheduleReminder(n);
    renderList();
    updateEditorStatus(n);
    if(showFeedback) showBalloon('Guardado', n.titulo || 'Sin título', '💾');
    setStatus('Guardado: ' + (n.titulo || 'Sin título'));
  }
}

// ── SCHEDULE REMINDER ─────────────────────────
// setTimeout dispara fireReminder() cuando llegue la fecha+hora
function scheduleReminder(n) {
  if(notifTimers[n.id]) { clearTimeout(notifTimers[n.id]); delete notifTimers[n.id]; }
  if(!n.fecha || !n.hora) return;
  var dt = new Date(n.fecha + 'T' + n.hora);
  var ms = dt - Date.now();
  if(ms <= 0) return; // fecha pasada, no programar
  notifTimers[n.id] = setTimeout(function(){ fireReminder(n); }, ms);
}

function scheduleAllReminders() {
  notes.forEach(function(n){ scheduleReminder(n); });
}

function fireReminder(n) {
  playAlert();
  document.getElementById('modalTitle').textContent   = n.titulo || 'Sin título';
  document.getElementById('modalContent').textContent = n.descripcion || '';
  document.getElementById('reminderModal').classList.add('active');
  snoozedNote = n;
  if(Notification.permission === 'granted') {
    new Notification('📅 Recordatorio — Calendario Vintage', {
      body: n.titulo + (n.descripcion ? '\n' + n.descripcion.slice(0,80) : ''),
    });
  }
}

function closeReminderModal() {
  document.getElementById('reminderModal').classList.remove('active');
  snoozedNote = null;
}

function snoozeReminder() {
  if(!snoozedNote) return;
  var n = snoozedNote;
  notifTimers[n.id] = setTimeout(function(){ fireReminder(n); }, 5 * 60 * 1000);
  closeReminderModal();
  showBalloon('Recordatorio pospuesto', '5 minutos', '🕒');
}

function testReminderNow() {
  if(!currentId) return showBalloon('Sin selección', 'Selecciona una nota primero.', '⚠️');
  var n = notes.find(function(x){ return x.id === currentId; });
  if(n) fireReminder(n);
}

// ── SELECT / RENDER ─────────────────────────────
function selectNote(id) {
  currentId = id;
  renderEditor();
  document.querySelectorAll('.xp-note-item').forEach(function(el) {
    el.classList.toggle('active', String(el.dataset.id) === String(id));
  });
}

function setFilter(f, btn) {
  filter = f;
  document.querySelectorAll('.xp-filter-btn').forEach(function(b){ b.classList.remove('active'); });
  if(btn) btn.classList.add('active');
  renderList();
}

function getFilteredNotes() {
  var q = (document.getElementById('searchInput') ? document.getElementById('searchInput').value : '').toLowerCase();
  return notes.filter(function(n) {
    if(q && !((n.titulo||'').toLowerCase().includes(q)) && !((n.descripcion||'').toLowerCase().includes(q))) return false;
    if(filter === 'pinned')   return n.pinned;
    if(filter === 'done')     return n.done;
    if(filter === 'high')     return n.prioridad === 'alta';
    if(filter === 'reminder') return n.fecha && n.hora;
    return true;
  });
}

function getPriorityIcon(p) {
  if(p === 'alta')  return '🔴';
  if(p === 'media') return '🟡';
  return '🟢';
}

function renderList() {
  var list    = document.getElementById('noteList');
  var badge   = document.getElementById('noteCount');
  var filtered = getFilteredNotes();
  if(badge) badge.textContent = notes.length;
  if(!list) return;

  if(filtered.length === 0) {
    list.innerHTML = '<div class="xp-empty-list"><div class="xp-empty-list-icon">📂</div><p>No hay notas aún.<br>Haz clic en "Nueva nota".</p></div>';
    return;
  }

  var sorted = filtered.slice().sort(function(a,b) {
    if(a.pinned && !b.pinned) return -1;
    if(!a.pinned && b.pinned) return 1;
    return new Date(a.creado_en||0) - new Date(b.creado_en||0);
  });

  list.innerHTML = sorted.map(function(n) {
    var isActive = n.id === currentId;
    var icon     = n.done ? '✅' : (n.pinned ? '📌' : getPriorityIcon(n.prioridad));
    var dateStr  = n.fecha ? (n.fecha + ' ' + (n.hora||'')) : '';
    return '<div class="xp-note-item' + (isActive?' active':'') + (n.done?' done':'') + '"' +
      ' data-id="' + n.id + '" onclick="selectNote(' + n.id + ')">' +
      '<div class="xp-note-item-icon">' + icon + '</div>' +
      '<div class="xp-note-item-body">' +
        '<div class="xp-note-item-title">' + escHtml(n.titulo||'Sin título') + '</div>' +
        '<div class="xp-note-item-date">' + escHtml(dateStr) + '</div>' +
      '</div>' +
      '<button class="xp-note-item-del" onclick="deleteNote(' + n.id + ',event)" title="Eliminar">✕</button>' +
    '</div>';
  }).join('');
}

function updateEditorStatus(n) {
  var el = document.getElementById('editorStatus');
  if(!el) return;
  el.textContent = n ? ('Editando: ' + (n.titulo||'Sin título') + ' — ' + timeAgo(n.creado_en)) : 'Sin nota seleccionada';
}

function togglePin() {
  if(!currentId) return;
  var n = notes.find(function(x){ return x.id === currentId; });
  if(!n) return;
  n.pinned = !n.pinned;
  apiActualizarNota(n.id, n);
  renderList();
  showBalloon(n.pinned ? 'Nota fijada' : 'Nota desfijada', '', n.pinned ? '📌' : '📄');
}

function toggleDone() {
  if(!currentId) return;
  var n = notes.find(function(x){ return x.id === currentId; });
  if(!n) return;
  n.done = !n.done;
  apiActualizarNota(n.id, n);
  renderList();
  showBalloon(n.done ? 'Marcada como completada' : 'Marcada como pendiente', '', n.done ? '✅' : '🔄');
}

function renderEditor() {
  var body = document.getElementById('editorBody');
  if(!body) return;

  if(!currentId) {
    body.innerHTML = '<div class="xp-welcome"><div class="xp-welcome-icon">📅</div><h2>Calendario de Notas</h2><p>Crea una nueva nota con fecha, hora, título y descripción.<br>Recibirás un aviso en tu escritorio cuando llegue el momento.</p><br><button class="xp-btn primary" onclick="newNote()">📝 Crear primera nota</button></div>';
    updateEditorStatus(null);
    return;
  }

  var n = notes.find(function(x){ return x.id === currentId; });
  if(!n) return;

  var reminderVal = (n.fecha && n.hora) ? (n.fecha + 'T' + n.hora) : '';
  var isOverdue   = reminderVal && new Date(reminderVal) < new Date();

  var prioHtml = ['baja','media','alta'].map(function(p) {
    return '<button class="xp-priority-btn' + (n.prioridad===p?' active-'+p:'') + '"' +
      ' onclick="setPriority(\'' + p + '\')">' + getPriorityIcon(p) + ' ' + p.charAt(0).toUpperCase()+p.slice(1) + '</button>';
  }).join('');

  var catHtml = CATS.map(function(c) {
    return '<button class="xp-cat-btn' + (n.categoria===c.id?' active':'') + '"' +
      ' onclick="setCategory(\'' + c.id + '\')">' + escHtml(c.label) + '</button>';
  }).join('');

  var overdueHtml = isOverdue ? '<div class="xp-reminder-box overdue"><div class="xp-reminder-icon">⚠️</div><div class="xp-reminder-info"><div class="xp-reminder-title">Recordatorio vencido</div><div class="xp-reminder-time">' + new Date(reminderVal).toLocaleString('es-CL') + '</div></div></div>' : '';

  body.innerHTML =
    '<div class="xp-form-section">' +
      '<div class="xp-field">' +
        '<label class="xp-label" for="titleInput">Título</label>' +
        '<input class="xp-input" id="titleInput" type="text"' +
          ' value="' + escHtml(n.titulo||'') + '"' +
          ' placeholder="Título de la nota..."' +
          ' oninput="autoSave()">' +
      '</div>' +
      '<div class="xp-form-row">' +
        '<div class="xp-field">' +
          '<label class="xp-label">Prioridad</label>' +
          '<div class="xp-priority-group">' + prioHtml + '</div>' +
        '</div>' +
        '<div class="xp-field">' +
          '<label class="xp-label">Categoría</label>' +
          '<div class="xp-cat-group">' + catHtml + '</div>' +
        '</div>' +
      '</div>' +
      '<div class="xp-groupbox">' +
        '<span class="xp-groupbox-title">⏰ Recordatorio</span>' +
        overdueHtml +
        '<div class="xp-field">' +
          '<label class="xp-label" for="reminderInput">Fecha y hora del aviso</label>' +
          '<input class="xp-input" id="reminderInput" type="datetime-local"' +
            ' value="' + escHtml(reminderVal) + '"' +
            ' oninput="autoSave()">' +
        '</div>' +
      '</div>' +
      '<div class="xp-field" style="margin-top:8px">' +
        '<label class="xp-label" for="contentInput">Descripción</label>' +
        '<textarea class="xp-textarea" id="contentInput"' +
          ' rows="6" placeholder="Describe la tarea o actividad..."' +
          ' oninput="autoSave()">' + escHtml(n.descripcion||'') + '</textarea>' +
      '</div>' +
    '</div>';

  updateEditorStatus(n);
}

var autoSaveTimer = null;
function autoSave() {
  clearTimeout(autoSaveTimer);
  autoSaveTimer = setTimeout(function(){ saveCurrentNote(false); }, 800);
}

function setPriority(p) {
  if(!currentId) return;
  var n = notes.find(function(x){ return x.id === currentId; });
  if(!n) return;
  n.prioridad = p;
  renderEditor();
  saveCurrentNote(false);
}

function setCategory(c) {
  if(!currentId) return;
  var n = notes.find(function(x){ return x.id === currentId; });
  if(!n) return;
  n.categoria = c;
  renderEditor();
  saveCurrentNote(false);
}

function exportCurrentNote() {
  if(!currentId) return showBalloon('Sin selección', 'Selecciona una nota primero.', '⚠️');
  var n = notes.find(function(x){ return x.id === currentId; });
  if(!n) return;
  var txt = 'TÍTULO: ' + (n.titulo||'Sin título') + '\nFECHA: ' + (n.fecha||'') + ' ' + (n.hora||'') + '\nPRIORIDAD: ' + (n.prioridad||'') + '\nCATEGORÍA: ' + (n.categoria||'') + '\n\n' + (n.descripcion||'');
  var a = document.createElement('a');
  a.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent(txt);
  a.download = ((n.titulo||'nota').replace(/[^a-z0-9]/gi,'_').slice(0,30)) + '.txt';
  a.click();
  showBalloon('Exportado', n.titulo || 'Sin título', '📤');
}

function showHelp() {
  showBalloon('Calendario Vintage XP', 'Ctrl+S para guardar. Ctrl+N para nueva nota.', '❓');
}

// ── KEYBOARD SHORTCUTS ────────────────────────
document.addEventListener('keydown', function(e) {
  if(e.ctrlKey && e.key === 's') { e.preventDefault(); saveCurrentNote(true); }
  if(e.ctrlKey && e.key === 'n') { e.preventDefault(); newNote(); }
});

// ── INIT — carga notas desde la API ──────────
async function init() {
  try {
    notes = await apiGetNotas();
    renderList();
    renderEditor();
    scheduleAllReminders();
    updateNotifStatus();
    setStatus(notes.length + ' nota(s) cargada(s) desde la base de datos');
    if(Notification.permission === 'default') {
      setTimeout(function() {
        showBalloon('Activar notificaciones', 'Haz clic en "Notificaciones" para recibir avisos de escritorio.', '🔔');
      }, 2000);
    }
  } catch(err) {
    setStatus('Error al cargar notas: ' + err.message);
    showBalloon('Error de conexión', 'No se pudo contactar la API. ¿Está MySQL corriendo?', '❌');
  }
}

init();

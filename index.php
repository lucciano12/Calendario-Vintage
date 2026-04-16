<?php
// ============================================================
// BLOQUE 6 — index.php  (integración real con API PHP)
// ============================================================
require_once 'config/database.php';

$notas = [];

try {
    $pdo = conectarDB();
    $stmt = $pdo->prepare(
        "SELECT id, titulo, fecha, hora, prioridad, categoria
         FROM notas
         ORDER BY fecha ASC, hora ASC"
    );
    $stmt->execute();
    $notas = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorBD = 'No se pudo conectar a la base de datos.';
    error_log('Error BD: ' . $e->getMessage());
}

$titulo_ventana = 'Calendario Vintage — Mis Notas';
$nota_activa    = null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo_ventana); ?></title>
    <link rel="stylesheet" href="css/xp-style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>&#128197;</text></svg>">
</head>
<body>

<!-- ══ DESKTOP ══════════════════════════════════ -->
<div class="desktop">
  <div class="xp-window xp-app-window">

    <!-- TITLEBAR -->
    <div class="xp-titlebar">
      <div class="xp-titlebar-icon">📅</div>
      <div class="xp-titlebar-text">Calendario de Notas — <?php echo htmlspecialchars($_SERVER['PHP_AUTH_USER'] ?? 'Usuario'); ?></div>
      <div class="xp-controls">
        <button class="xp-ctrl-btn" title="Minimizar">─</button>
        <button class="xp-ctrl-btn" title="Maximizar">□</button>
        <button class="xp-ctrl-btn close" title="Cerrar"
          onclick="if(confirm('¿Cerrar Calendario de Notas?'))
            document.body.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100vh;color:white;font-size:13px;font-family:Tahoma,sans-serif;\'>El programa fue cerrado. Recarga la página para volver.</div>'">✕</button>
      </div>
    </div>

    <!-- MENUBAR -->
    <div class="xp-menubar">
      <div class="xp-menu-item" onclick="newNote()">📄 Archivo</div>
      <div class="xp-menu-item" onclick="showHelp()">✏️ Editar</div>
      <div class="xp-menu-item">🔍 Ver</div>
      <div class="xp-menu-item" onclick="showHelp()">❓ Ayuda</div>
    </div>

    <!-- TOOLBAR -->
    <div class="xp-toolbar">
      <button class="xp-btn primary" onclick="newNote()">📝 Nueva nota</button>
      <div class="xp-separator-v"></div>
      <button class="xp-btn" id="saveBtn" onclick="saveCurrentNote(true)" title="Guardar (Ctrl+S)">💾 Guardar</button>
      <button class="xp-btn danger" id="deleteBtn" onclick="deleteCurrentNote()" title="Eliminar nota actual">🗑️ Eliminar</button>
      <div class="xp-separator-v"></div>
      <button class="xp-btn" onclick="requestNotifPermission()" title="Activar notificaciones de escritorio">🔔 Notificaciones</button>
      <button class="xp-btn" id="testNotifBtn" onclick="testReminderNow()" title="Probar alerta ahora">🧪 Probar aviso</button>
      <div class="xp-separator-v"></div>
      <button class="xp-btn" onclick="exportCurrentNote()">📤 Exportar .txt</button>
    </div>

    <!-- PANES -->
    <div class="xp-panes">

      <!-- LEFT PANE: LISTA DE NOTAS -->
      <div class="xp-left-pane">
        <div class="xp-left-header">
          📋 Mis Notas
          <span class="xp-count-badge" id="noteCount"><?php echo count($notas ?? []); ?></span>
        </div>

        <div class="xp-search-box">
          <input class="xp-input" id="searchInput" type="text"
            placeholder="🔍 Buscar notas..."
            oninput="renderList()" style="font-size:10px;">
        </div>

        <div class="xp-filter-bar">
          <button class="xp-filter-btn active" data-f="all" onclick="setFilter('all',this)">Todas</button>
          <button class="xp-filter-btn" data-f="reminder" onclick="setFilter('reminder',this)">⏰</button>
          <button class="xp-filter-btn" data-f="pinned" onclick="setFilter('pinned',this)">📌</button>
          <button class="xp-filter-btn" data-f="done" onclick="setFilter('done',this)">✅</button>
          <button class="xp-filter-btn" data-f="high" onclick="setFilter('high',this)">🔴 Alta</button>
        </div>

        <div class="xp-note-list" id="noteList">
          <div class="xp-empty-list">
            <div class="xp-empty-list-icon">📂</div>
            <p>No hay notas aún.<br>Haz clic en "Nueva nota".</p>
          </div>
        </div>
      </div>

      <!-- RIGHT PANE: EDITOR -->
      <div class="xp-right-pane">
        <div class="xp-editor-toolbar">
          <button class="xp-btn xp-btn-small" id="pinBtn" onclick="togglePin()" title="Fijar/Desfijar nota">📌 Fijar</button>
          <button class="xp-btn xp-btn-small success" id="doneBtn" onclick="toggleDone()">✅ Marcar lista</button>
          <div class="xp-separator-v"></div>
          <span style="font-size:10px; color:var(--xp-text-muted)" id="editorStatus">Sin nota seleccionada</span>
        </div>

        <div class="xp-editor-body" id="editorBody">
          <div class="xp-welcome">
            <div class="xp-welcome-icon">📅</div>
            <h2>Calendario de Notas</h2>
            <p>Crea una nueva nota con fecha, hora, título y descripción.<br>Recibirás un aviso en tu escritorio cuando llegue el momento.</p>
            <br>
            <button class="xp-btn primary" onclick="newNote()">📝 Crear primera nota</button>
          </div>
        </div>
      </div>

    </div><!-- /xp-panes -->

    <!-- STATUSBAR -->
    <div class="xp-statusbar">
      <div class="xp-statusbar-cell fill" id="statusMsg">
        <?php
          if (isset($errorBD)) {
              echo htmlspecialchars($errorBD);
          } else {
              echo count($notas ?? []) . ' nota(s) registrada(s)';
          }
        ?>
      </div>
      <div class="xp-statusbar-cell">
        <div class="xp-status-dot yellow" id="notifDot"></div>
        <span id="notifLabel">Notificaciones: pendiente</span>
      </div>
      <div class="xp-statusbar-cell" id="clockStatus">--:--</div>
    </div>

  </div><!-- /xp-app-window -->
</div><!-- /desktop -->

<!-- TASKBAR -->
<div class="xp-taskbar">
  <button class="xp-start-btn">
    <span style="font-size:16px">🪟</span> inicio
  </button>
  <div class="xp-taskbar-apps">
    <button class="xp-taskbar-btn">📅 Calendario de Notas</button>
  </div>
  <div class="xp-taskbar-clock" id="taskbarClock">
    <span>12:00</span>
    <span style="font-size:9px">00/00/0000</span>
  </div>
</div>

<!-- BALLOON CONTAINER -->
<div class="xp-balloon-container" id="balloonContainer"></div>

<!-- REMINDER MODAL -->
<div class="xp-modal-overlay" id="reminderModal">
  <div class="xp-window xp-alert-window">
    <div class="xp-titlebar">
      <div class="xp-titlebar-icon">⏰</div>
      <div class="xp-titlebar-text">Recordatorio — Calendario de Notas</div>
      <div class="xp-controls">
        <button class="xp-ctrl-btn close" onclick="closeReminderModal()">✕</button>
      </div>
    </div>
    <div class="xp-alert-body">
      <div class="xp-alert-icon">⏰</div>
      <div class="xp-alert-content">
        <h3 id="modalTitle">Título del recordatorio</h3>
        <p id="modalContent">Descripción del recordatorio.</p>
      </div>
    </div>
    <div class="xp-alert-footer">
      <button class="xp-btn primary" onclick="closeReminderModal()">Aceptar</button>
      <button class="xp-btn" onclick="snoozeReminder()">🕒 Posponer 5 min</button>
    </div>
  </div>
</div>

<!--
  SCRIPTS — al final del body.
  No usamos type="module" porque las funciones se llaman desde
  atributos onclick="" en el HTML, y los módulos ES6 tienen
  scope privado (window.newNote sería undefined).
-->
<script src="js/app.js"></script>

</body>
</html>

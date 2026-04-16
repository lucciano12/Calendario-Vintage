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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo_ventana); ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>&#128197;</text></svg>">
    <style>
/* ═══════════════════════════════════════════════════
   WINDOWS XP DESIGN SYSTEM
   Paleta: Azul Luna, Verde Hierba, Gris Classic
═══════════════════════════════════════════════════ */
:root {
  --xp-titlebar-start:  #0a246a;
  --xp-titlebar-mid:    #3a6ea5;
  --xp-titlebar-end:    #4d88c4;
  --xp-titlebar-text:   #ffffff;
  --xp-taskbar-start:   #1f5aad;
  --xp-taskbar-end:     #3a93d0;
  --xp-desktop:         #3b6ea5;
  --xp-window-bg:       #ece9d8;
  --xp-window-content:  #ffffff;
  --xp-panel:           #d4d0c8;
  --xp-panel-light:     #f2f1ec;
  --xp-panel-dark:      #a0a098;
  --xp-inset:           #b0adb0;
  --xp-border-light:    #ffffff;
  --xp-border-dark:     #7f7f7f;
  --xp-border-shadow:   #404040;
  --xp-menu-bg:         #f5f4ee;
  --xp-hover-bg:        #316ac5;
  --xp-hover-text:      #ffffff;
  --bevel-raised: inset 1px 1px 0 #ffffff, inset -1px -1px 0 #808080, 1px 1px 0 #404040;
  --bevel-sunken: inset 1px 1px 0 #808080, inset -1px -1px 0 #ffffff, 1px 1px 0 #404040;
  --bevel-btn:    inset -1px -1px 0 #404040, inset 1px 1px 0 #dfdfdf, inset -2px -2px 0 #808080, inset 2px 2px 0 #ffffff;
  --bevel-btn-active: inset 1px 1px 0 #808080, inset -1px -1px 0 #dfdfdf;
  --xp-text:        #000000;
  --xp-text-muted:  #808080;
  --xp-text-title:  #0d2670;
  --xp-link:        #0000ff;
  --xp-link-visited:#800080;
  --xp-success:     #008000;
  --xp-warning:     #c06000;
  --xp-error:       #cc0000;
  --xp-info:        #0057d8;
  --font-xp: 'Tahoma', 'Microsoft Sans Serif', Arial, sans-serif;
  --sp-1: 2px; --sp-2: 4px; --sp-3: 6px; --sp-4: 8px;
  --sp-5: 10px; --sp-6: 12px; --sp-8: 16px; --sp-10: 20px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  height: 100%;
  font-family: var(--font-xp);
  font-size: 11px;
  color: var(--xp-text);
  background: var(--xp-desktop);
  overflow: hidden;
  user-select: none;
}

body {
  background-color: #3b6ea5;
  background-image:
    radial-gradient(ellipse 120% 80% at 50% 110%, #1a4a8c 0%, transparent 60%),
    radial-gradient(ellipse 60% 40% at 20% 80%, #2a6ea0 0%, transparent 50%),
    radial-gradient(ellipse 40% 30% at 80% 70%, #4a8ec0 0%, transparent 50%);
}

/* ── XP WINDOW CHROME ── */
.xp-window {
  background: var(--xp-window-bg);
  border: 1px solid #0a246a;
  box-shadow: 2px 2px 8px rgba(0,0,0,0.5), inset 0 0 0 1px rgba(255,255,255,0.3);
  display: flex;
  flex-direction: column;
}

.xp-titlebar {
  background: linear-gradient(180deg,
    #4d88c4 0%, #3a6ea5 8%, #2060a8 15%,
    #0a246a 50%, #0a246a 85%,
    #1a3da0 92%, #3060b8 100%
  );
  display: flex;
  align-items: center;
  padding: 3px 4px;
  gap: 4px;
  height: 28px;
  position: relative;
  border-bottom: 1px solid #0a246a;
}

.xp-titlebar-icon {
  width: 16px; height: 16px;
  flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
  line-height: 1;
}

.xp-titlebar-text {
  flex: 1;
  color: white;
  font-size: 12px;
  font-weight: bold;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.xp-controls {
  display: flex;
  gap: 2px;
  align-items: center;
}

.xp-ctrl-btn {
  width: 21px; height: 21px;
  border: none;
  cursor: pointer;
  font-family: 'Marlett', 'Webdings', var(--font-xp);
  font-size: 11px;
  font-weight: bold;
  display: flex; align-items: center; justify-content: center;
  position: relative;
  border-radius: 3px;
  transition: none;
  background: linear-gradient(180deg, #f0f0f0 0%, #d8d8d8 45%, #c0c0c0 50%, #b8b8b8 100%);
  box-shadow: inset 1px 1px 0 #ffffff, inset -1px -1px 0 #808080;
  color: #000;
}

.xp-ctrl-btn.close {
  background: linear-gradient(180deg, #e08070 0%, #c84030 40%, #b82020 55%, #c03010 100%);
  box-shadow: inset 1px 1px 0 #e89080, inset -1px -1px 0 #901010;
  color: white;
  text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
}

.xp-ctrl-btn:hover { filter: brightness(1.1); }
.xp-ctrl-btn:active { filter: brightness(0.9); box-shadow: inset 1px 1px 0 #606060, inset -1px -1px 0 #e0e0e0; }

/* ── XP MENU BAR ── */
.xp-menubar {
  background: var(--xp-window-bg);
  border-bottom: 1px solid var(--xp-border-dark);
  display: flex;
  align-items: center;
  padding: 1px 2px;
  gap: 0;
  height: 22px;
}

.xp-menu-item {
  padding: 2px 8px;
  font-size: 11px;
  cursor: pointer;
  border-radius: 2px;
  color: var(--xp-text);
  transition: background 0.05s, color 0.05s;
  height: 20px;
  display: flex; align-items: center;
}

.xp-menu-item:hover {
  background: var(--xp-hover-bg);
  color: var(--xp-hover-text);
}

/* ── XP TOOLBAR ── */
.xp-toolbar {
  background: linear-gradient(180deg, #f5f4ee 0%, #ece9d8 100%);
  border-bottom: 1px solid var(--xp-border-dark);
  display: flex;
  align-items: center;
  padding: 2px 4px;
  gap: 2px;
  flex-wrap: wrap;
  min-height: 30px;
}

.xp-separator-v {
  width: 1px;
  height: 20px;
  background: var(--xp-border-dark);
  margin: 0 3px;
}

/* ── XP BUTTONS ── */
.xp-btn {
  background: linear-gradient(180deg, #f0f0f0 0%, #d8d4c8 50%, #c8c4b8 100%);
  border: none;
  box-shadow: var(--bevel-btn);
  padding: 3px 10px;
  font-family: var(--font-xp);
  font-size: 11px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
  min-height: 23px;
  color: var(--xp-text);
  border-radius: 3px;
}

.xp-btn:hover {
  background: linear-gradient(180deg, #e8e4f0 0%, #d0cce8 50%, #c0bcd8 100%);
}

.xp-btn:active, .xp-btn.active {
  background: linear-gradient(180deg, #c0c0c0 0%, #d0d0d0 100%);
  box-shadow: var(--bevel-btn-active);
  padding: 4px 10px 2px;
}

.xp-btn.primary {
  background: linear-gradient(180deg, #5090e0 0%, #1060c0 50%, #0850b0 100%);
  box-shadow: inset -1px -1px 0 #082060, inset 1px 1px 0 #80b0f0, inset -2px -2px 0 #1040a0, inset 2px 2px 0 #a0c8ff;
  color: white;
  text-shadow: 0 1px 1px rgba(0,0,0,0.5);
}
.xp-btn.primary:hover {
  background: linear-gradient(180deg, #60a0f0 0%, #2070d0 50%, #1060c0 100%);
}

.xp-btn.danger {
  background: linear-gradient(180deg, #e06050 0%, #c02010 50%, #b01808 100%);
  box-shadow: inset -1px -1px 0 #601008, inset 1px 1px 0 #f09080, inset -2px -2px 0 #a01808, inset 2px 2px 0 #ffa090;
  color: white;
}

.xp-btn.success {
  background: linear-gradient(180deg, #60c060 0%, #208020 50%, #107010 100%);
  box-shadow: inset -1px -1px 0 #084008, inset 1px 1px 0 #80e080;
  color: white;
}

.xp-btn-small {
  padding: 2px 6px;
  font-size: 10px;
  min-height: 20px;
  border-radius: 2px;
}

.xp-icon-btn {
  width: 22px; height: 22px;
  background: transparent;
  border: none;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  border-radius: 2px;
  padding: 0;
  font-size: 13px;
}

.xp-icon-btn:hover {
  background: linear-gradient(180deg, #e8e4f0 0%, #d0cce8 100%);
  box-shadow: var(--bevel-btn);
}

/* ── XP INPUTS ── */
.xp-input, .xp-textarea, .xp-select {
  background: white;
  border: none;
  box-shadow: inset 1px 1px 0 #808080, inset -1px -1px 0 #dfdfdf, inset 2px 2px 0 #404040, inset -2px -2px 0 #ffffff;
  padding: 3px 6px;
  font-family: var(--font-xp);
  font-size: 11px;
  color: var(--xp-text);
  outline: none;
  width: 100%;
  border-radius: 0;
}

.xp-input:focus, .xp-textarea:focus, .xp-select:focus {
  outline: 1px dotted #000;
  outline-offset: -1px;
}

.xp-textarea {
  resize: vertical;
  min-height: 100px;
  line-height: 1.5;
  padding: 4px 6px;
  font-size: 12px;
  font-family: 'Courier New', Courier, monospace;
}

.xp-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M0 0l6 8 6-8z' fill='%23000'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 4px center;
  padding-right: 20px;
  cursor: pointer;
}

/* ── XP LABEL/FORM ── */
.xp-field {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.xp-label {
  font-size: 11px;
  font-weight: bold;
  color: var(--xp-text);
}

.xp-label-faint {
  font-size: 10px;
  color: var(--xp-text-muted);
}

.xp-field-row {
  display: flex;
  gap: 8px;
  align-items: flex-start;
}

/* ── XP GROUPBOX ── */
.xp-groupbox {
  border: 1px solid var(--xp-border-dark);
  box-shadow: inset -1px -1px 0 #ffffff;
  padding: 8px;
  padding-top: 16px;
  position: relative;
  margin-top: 10px;
  background: var(--xp-window-bg);
}

.xp-groupbox-title {
  position: absolute;
  top: -8px;
  left: 8px;
  background: var(--xp-window-bg);
  padding: 0 4px;
  font-size: 11px;
  font-weight: bold;
}

/* ── STATUSBAR ── */
.xp-statusbar {
  background: var(--xp-window-bg);
  border-top: 1px solid var(--xp-border-dark);
  display: flex;
  align-items: center;
  min-height: 22px;
  padding: 0;
  flex-shrink: 0;
  gap: 0;
}

.xp-statusbar-cell {
  padding: 2px 8px;
  font-size: 10px;
  color: var(--xp-text-muted);
  border-right: 1px solid var(--xp-border-dark);
  height: 100%;
  display: flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

.xp-statusbar-cell.fill { flex: 1; }

.xp-status-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--xp-text-muted);
  flex-shrink: 0;
  box-shadow: inset 1px 1px 0 rgba(255,255,255,0.5);
}
.xp-status-dot.green { background: #00b020; }
.xp-status-dot.yellow { background: #e0a000; }
.xp-status-dot.red { background: #d01010; }

/* ── TASKBAR ── */
.xp-taskbar {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  height: 30px;
  background: linear-gradient(180deg, #245dbe 0%, #1c4ea8 4%, #1a50ac 40%, #0c3d94 95%, #0a3282 100%);
  border-top: 2px solid #4080cc;
  display: flex;
  align-items: center;
  z-index: 999;
  padding: 0;
  box-shadow: 0 -2px 4px rgba(0,0,0,0.3);
}

.xp-start-btn {
  height: 100%;
  padding: 0 14px 0 10px;
  background: linear-gradient(180deg, #4a9858 0%, #3a8448 20%, #247a34 60%, #1a6828 80%, #2a8840 100%);
  border: none;
  border-right: 1px solid #1a5020;
  box-shadow: inset 1px 0 0 rgba(255,255,255,0.2), 2px 0 4px rgba(0,0,0,0.3);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  font-family: var(--font-xp);
  font-size: 13px;
  font-weight: bold;
  font-style: italic;
  color: white;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
  border-radius: 0 12px 12px 0;
  margin-right: 4px;
}

.xp-start-btn:hover {
  background: linear-gradient(180deg, #5ab068 0%, #4a9458 20%, #348a44 60%, #2a7838 100%);
}

.xp-taskbar-apps {
  flex: 1;
  display: flex;
  align-items: center;
  padding: 2px 4px;
  gap: 2px;
  overflow: hidden;
}

.xp-taskbar-btn {
  height: 22px;
  padding: 2px 8px;
  background: linear-gradient(180deg, #3060b8 0%, #1848a0 50%, #1040a0 100%);
  border: 1px solid #0830a0;
  box-shadow: inset 1px 1px 0 rgba(255,255,255,0.15), inset -1px -1px 0 rgba(0,0,0,0.3);
  color: white;
  font-family: var(--font-xp);
  font-size: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
  border-radius: 2px;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.xp-taskbar-clock {
  padding: 0 12px;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  color: white;
  text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
  border-left: 1px solid #1040a0;
  white-space: nowrap;
  box-shadow: inset 1px 0 0 rgba(255,255,255,0.1);
  line-height: 1.3;
}

/* ══════════════════════════════════════════════════
   MAIN LAYOUT — 2 PANES
══════════════════════════════════════════════════ */
.desktop {
  height: calc(100vh - 30px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
}

.xp-app-window {
  width: min(1000px, 100%);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.xp-panes {
  display: flex;
  flex: 1;
  overflow: hidden;
  border: none;
  background: white;
}

/* LEFT PANE — note list */
.xp-left-pane {
  width: 240px;
  min-width: 240px;
  background: var(--xp-window-bg);
  border-right: 1px solid var(--xp-border-dark);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.xp-left-header {
  background: linear-gradient(180deg, #3a72c8 0%, #2060b0 100%);
  color: white;
  padding: 6px 10px;
  font-size: 11px;
  font-weight: bold;
  text-shadow: 1px 1px 1px rgba(0,0,0,0.4);
  border-bottom: 1px solid #1040a0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.xp-search-box {
  padding: 4px 6px;
  border-bottom: 1px solid var(--xp-border-dark);
  background: var(--xp-panel-light);
}

.xp-note-list {
  flex: 1;
  overflow-y: auto;
  padding: 2px;
}

.xp-note-list::-webkit-scrollbar { width: 16px; }
.xp-note-list::-webkit-scrollbar-track {
  background: var(--xp-panel);
  box-shadow: inset 1px 1px 0 #a0a0a0;
}
.xp-note-list::-webkit-scrollbar-thumb {
  background: linear-gradient(90deg, #c0c0c8 0%, #e0e0e8 50%, #b0b0b8 100%);
  border: 1px solid #808080;
  box-shadow: inset 1px 1px 0 #e8e8f0, inset -1px -1px 0 #a0a0a8;
}
.xp-note-list::-webkit-scrollbar-button {
  background: linear-gradient(180deg, #e8e8e8 0%, #c0c0c0 100%);
  border: 1px solid #808080;
  height: 16px;
  display: block;
}

.xp-note-item {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 4px 6px;
  cursor: pointer;
  border: 1px solid transparent;
  margin: 1px;
  border-radius: 1px;
}

.xp-note-item:hover {
  background: var(--xp-hover-bg);
  color: white;
  border-color: #0050e0;
}

.xp-note-item.active {
  background: var(--xp-hover-bg);
  color: white;
  border-color: #0060c0;
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.2);
}

.xp-note-item.active .xp-note-item-date,
.xp-note-item:hover .xp-note-item-date {
  color: rgba(255,255,255,0.75);
}

.xp-note-item-icon { font-size: 14px; flex-shrink: 0; margin-top: 1px; line-height: 1; }
.xp-note-item-body { flex: 1; min-width: 0; }
.xp-note-item-title {
  font-size: 11px;
  font-weight: bold;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.xp-note-item-date { font-size: 9px; color: var(--xp-text-muted); margin-top: 1px; }

.xp-note-item.done .xp-note-item-title {
  text-decoration: line-through;
  opacity: 0.6;
}

.xp-note-item-del {
  opacity: 0;
  font-size: 10px;
  padding: 1px 4px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--xp-error);
  flex-shrink: 0;
}
.xp-note-item:hover .xp-note-item-del { opacity: 1; color: white; }
.xp-note-item-del:hover { text-decoration: underline; }

/* RIGHT PANE — editor */
.xp-right-pane {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: white;
}

.xp-editor-toolbar {
  background: linear-gradient(180deg, #f5f4ee 0%, #ece9d8 100%);
  border-bottom: 1px solid var(--xp-border-dark);
  padding: 3px 6px;
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
  flex-shrink: 0;
}

.xp-editor-body {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  background: white;
}

/* ── WELCOME SCREEN ── */
.xp-welcome {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  gap: 12px;
  text-align: center;
  color: var(--xp-text-muted);
  padding: 20px;
}

.xp-welcome-icon { font-size: 48px; }

.xp-welcome h2 {
  font-size: 14px;
  font-weight: bold;
  color: var(--xp-text-title);
  font-style: italic;
}

.xp-welcome p {
  font-size: 11px;
  line-height: 1.6;
  max-width: 260px;
}

/* ── FORM SECTIONS ── */
.xp-form-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.xp-form-row { display: flex; gap: 8px; }
.xp-form-row .xp-field { flex: 1; }

/* Bloque fecha/hora del recordatorio — se muestra/oculta */
.xp-reminder-datetime {
  background: #f0f4ff;
  border: 1px solid #8090c0;
  padding: 8px 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 2px;
}

.xp-reminder-datetime.hidden { display: none; }

.xp-priority-group {
  display: flex;
  gap: 0;
}

.xp-priority-btn {
  flex: 1;
  padding: 3px 6px;
  font-family: var(--font-xp);
  font-size: 10px;
  background: linear-gradient(180deg, #f0f0f0 0%, #d0d0d0 100%);
  border: 1px solid var(--xp-border-dark);
  cursor: pointer;
  border-right: none;
  transition: none;
}
.xp-priority-btn:last-child { border-right: 1px solid var(--xp-border-dark); }
.xp-priority-btn:hover { background: linear-gradient(180deg, #e0e8f8 0%, #c0c8e8 100%); }
.xp-priority-btn.active-low { background: #b0e0a0; box-shadow: inset 1px 1px 0 #608050; }
.xp-priority-btn.active-medium { background: #e0e080; box-shadow: inset 1px 1px 0 #808020; }
.xp-priority-btn.active-high { background: #f0a0a0; box-shadow: inset 1px 1px 0 #905040; }

.xp-cat-group { display: flex; gap: 0; flex-wrap: wrap; }
.xp-cat-btn {
  padding: 3px 8px;
  font-family: var(--font-xp);
  font-size: 10px;
  background: linear-gradient(180deg, #f0f0f0 0%, #d0d0d0 100%);
  border: 1px solid var(--xp-border-dark);
  cursor: pointer;
  border-right: none;
}
.xp-cat-btn:last-child { border-right: 1px solid var(--xp-border-dark); }
.xp-cat-btn:hover { background: linear-gradient(180deg, #e0e8f8 0%, #c0c8e8 100%); }
.xp-cat-btn.active { background: #c8ddf8; box-shadow: inset 1px 1px 0 #4080c0; font-weight: bold; }

/* ── REMINDER BOX ── */
.xp-reminder-box {
  background: #fff8e0;
  border: 1px solid #e0c040;
  padding: 6px 8px;
  display: flex;
  align-items: flex-start;
  gap: 6px;
  margin-bottom: 8px;
}
.xp-reminder-box.overdue {
  background: #ffe0e0;
  border-color: #cc4040;
}
.xp-reminder-icon { font-size: 16px; line-height: 1; flex-shrink: 0; margin-top: 1px; }
.xp-reminder-info { flex: 1; }
.xp-reminder-title { font-size: 11px; font-weight: bold; }
.xp-reminder-time { font-size: 10px; color: var(--xp-text-muted); margin-top: 1px; }

/* ── MODAL OVERLAY ── */
.xp-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.3);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.xp-modal-overlay.active { display: flex; }

.xp-alert-window {
  width: 340px;
  animation: popIn 0.2s ease;
}
@keyframes popIn {
  from { opacity:0; transform: scale(0.9); }
  to { opacity:1; transform: scale(1); }
}

.xp-alert-body {
  background: white;
  padding: 16px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}
.xp-alert-icon { font-size: 32px; flex-shrink: 0; }
.xp-alert-content { flex: 1; }
.xp-alert-content h3 { font-size: 12px; font-weight: bold; margin-bottom: 6px; }
.xp-alert-content p { font-size: 11px; color: var(--xp-text-muted); line-height: 1.5; }
.xp-alert-footer {
  background: var(--xp-window-bg);
  border-top: 1px solid var(--xp-border-dark);
  padding: 8px;
  display: flex;
  justify-content: flex-end;
  gap: 6px;
}

/* ── EMPTY STATE ── */
.xp-empty-list {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24px 12px;
  gap: 8px;
  color: var(--xp-text-muted);
  text-align: center;
}
.xp-empty-list-icon { font-size: 28px; }
.xp-empty-list p { font-size: 10px; line-height: 1.5; }

/* ── SCROLLBAR (main editor) ── */
.xp-editor-body::-webkit-scrollbar { width: 16px; }
.xp-editor-body::-webkit-scrollbar-track {
  background: var(--xp-panel);
  box-shadow: inset 1px 1px 0 #a0a0a0;
}
.xp-editor-body::-webkit-scrollbar-thumb {
  background: linear-gradient(90deg, #c0c0c8 0%, #e0e0e8 50%, #b0b0b8 100%);
  border: 1px solid #808080;
  box-shadow: inset 1px 1px 0 #e8e8f0, inset -1px -1px 0 #a0a0a8;
}

/* ── BADGES ── */
.xp-badge {
  display: inline-block;
  font-size: 9px;
  padding: 1px 5px;
  border: 1px solid;
  font-weight: bold;
  vertical-align: middle;
}
.xp-badge-reminder { background: #e0ecff; border-color: #6090d0; color: #2060a0; }
.xp-badge-overdue { background: #ffe0e0; border-color: #c04040; color: #800000; }
.xp-badge-done { background: #d8f0d0; border-color: #609040; color: #206010; }
.xp-badge-pin { background: #fff8c0; border-color: #c0a020; color: #604000; }

/* ── COUNT ── */
.xp-count-badge {
  display: inline-block;
  background: var(--xp-hover-bg);
  color: white;
  font-size: 9px;
  font-weight: bold;
  padding: 0 5px;
  border-radius: 8px;
  min-width: 16px;
  text-align: center;
  margin-left: 4px;
}

/* ── FILTER PILLS ── */
.xp-filter-bar {
  padding: 3px 6px;
  border-bottom: 1px solid var(--xp-border-dark);
  display: flex;
  gap: 2px;
  flex-wrap: wrap;
  background: var(--xp-panel-light);
}

.xp-filter-btn {
  padding: 2px 7px;
  font-family: var(--font-xp);
  font-size: 10px;
  background: linear-gradient(180deg, #e8e8f0 0%, #d0d0e0 100%);
  border: 1px solid #9090b0;
  cursor: pointer;
  color: var(--xp-text);
  border-radius: 1px;
}
.xp-filter-btn:hover { background: #c0c8f0; }
.xp-filter-btn.active {
  background: linear-gradient(180deg, #3060d8 0%, #1848c0 100%);
  color: white;
  border-color: #1040a0;
  box-shadow: inset 1px 1px 0 rgba(255,255,255,0.2);
}

/* ── XP BALLOON / TOAST ── */
.xp-balloon-container {
  position: fixed;
  bottom: 38px;
  right: 4px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: flex-end;
}

.xp-balloon {
  background: #ffffc0;
  border: 1px solid #808040;
  box-shadow: 2px 2px 4px rgba(0,0,0,0.4);
  padding: 6px 12px 6px 10px;
  font-family: var(--font-xp);
  font-size: 11px;
  display: flex;
  align-items: flex-start;
  gap: 8px;
  max-width: 280px;
  animation: slideIn 0.2s ease;
  position: relative;
}

@keyframes slideIn {
  from { opacity:0; transform: translateY(10px); }
  to { opacity:1; transform: translateY(0); }
}

.xp-balloon-icon { font-size: 20px; flex-shrink: 0; }
.xp-balloon-content { flex: 1; }
.xp-balloon-title { font-weight: bold; margin-bottom: 2px; }
.xp-balloon-msg { font-size: 10px; color: var(--xp-text-muted); }
.xp-balloon-close {
  position: absolute; top: 2px; right: 4px;
  font-size: 10px; cursor: pointer; color: var(--xp-text-muted);
  background: none; border: none; padding: 0;
  line-height: 1;
}
.xp-balloon-close:hover { color: var(--xp-error); }

/* ── RESPONSIVE ── */
@media (max-width: 600px) {
  .xp-left-pane { width: 180px; min-width: 180px; }
  .xp-titlebar-text { font-size: 11px; }
}
    </style>
</head>
<body>

<!-- ══ DESKTOP ════════════════════════════════════════════ -->
<div class="desktop">
  <div class="xp-window xp-app-window">

    <!-- TITLEBAR -->
    <div class="xp-titlebar">
      <div class="xp-titlebar-icon">📅</div>
      <div class="xp-titlebar-text">Calendario de Notas — <?php echo htmlspecialchars($_SERVER['PHP_AUTH_USER'] ?? 'Usuario'); ?></div>
      <div class="xp-controls">
        <button class="xp-ctrl-btn" title="Minimizar">─</button>
        <button class="xp-ctrl-btn" title="Maximizar">□</button>
        <button class="xp-ctrl-btn close" title="Cerrar" onclick="if(confirm('¿Cerrar Calendario de Notas?')) document.body.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100vh;color:white;font-size:13px;font-family:Tahoma,sans-serif;\'>El programa fue cerrado. Recarga la página para volver.</div>'">✕</button>
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
      <button class="xp-btn" onclick="requestNotifPermission()" title="Activar notificaciones">🔔 Notificaciones</button>
      <button class="xp-btn" id="testNotifBtn" onclick="testReminderNow()" title="Probar alerta ahora">🧪 Probar aviso</button>
      <div class="xp-separator-v"></div>
      <button class="xp-btn" onclick="exportCurrentNote()">📤 Exportar .txt</button>
    </div>

    <!-- PANES -->
    <div class="xp-panes">

      <!-- LEFT: NOTE LIST -->
      <div class="xp-left-pane">
        <div class="xp-left-header">
          📋 Mis Notas
          <span class="xp-count-badge" id="noteCount">0</span>
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

      <!-- RIGHT: EDITOR -->
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
            <p>Crea una nueva nota con título, descripción y recordatorio.<br>Recibe un aviso cuando llegue el momento.</p>
            <br>
            <button class="xp-btn primary" onclick="newNote()">📝 Crear primera nota</button>
          </div>
        </div>
      </div>
    </div>

    <!-- STATUSBAR -->
    <div class="xp-statusbar">
      <div class="xp-statusbar-cell fill" id="statusMsg">
        <?php
          if (isset($errorBD)) {
              echo htmlspecialchars($errorBD);
          } else {
              echo count($notas ?? []) . ' nota(s) cargada(s) desde la base de datos';
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
    <span style="font-size:16px">🪟</span> Inicio
  </button>
  <div class="xp-taskbar-apps">
    <button class="xp-taskbar-btn">📅 Calendario de Notas</button>
  </div>
  <div class="xp-taskbar-clock" id="taskbarClock">12:00<br>00/00/0000</div>
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

<script>
// ══════════════════════════════════════════════════
//   CALENDARIO DE NOTAS XP — LÓGICA PRINCIPAL
// ══════════════════════════════════════════════════

let notes = [];
let currentId = null;
let notifTimers = {};
let filter = 'all';
let snoozedNote = null;

const CATS = [
  { id:'general',  label:'General' },
  { id:'trabajo',  label:'Trabajo' },
  { id:'personal', label:'Personal' },
  { id:'ideas',    label:'Ideas' },
  { id:'dev',      label:'Dev' },
  { id:'fitness',  label:'Fitness' },
];

// ── STORAGE ──────────────────────────────────────
function save() {
  try { localStorage.setItem('xp_notes', JSON.stringify(notes)); } catch(e) {}
}

function load() {
  try {
    const raw = localStorage.getItem('xp_notes');
    if(raw) notes = JSON.parse(raw);
    if(!notes || notes.length === 0) {
      const fromDB = <?php echo json_encode(array_map(function($n){ return [
        'id'       => (string)$n['id'],
        'title'    => $n['titulo'],
        'content'  => '',
        'date'     => $n['fecha'],
        'time'     => $n['hora'],
        'priority' => $n['prioridad'] ?? 'medium',
        'category' => $n['categoria'] ?? 'general',
        'done'     => false,
        'pinned'   => false,
        'reminder' => !empty($n['fecha'])
      ]; }, $notas ?? [])); ?>;
      if(fromDB && fromDB.length > 0) notes = fromDB;
    }
  } catch(e) { notes = []; }
}

// ── ID GEN ────────────────────────────────────────
function uid() { return Date.now().toString(36) + Math.random().toString(36).slice(2,6); }

// ── CLOCK ────────────────────────────────────────
function updateClock() {
  const now = new Date();
  const t = now.toLocaleTimeString('es-CL',{hour:'2-digit',minute:'2-digit'});
  const d = now.toLocaleDateString('es-CL',{day:'2-digit',month:'2-digit',year:'numeric'});
  const el = document.getElementById('taskbarClock');
  if(el) el.innerHTML = t + '<br><span style="font-size:9px">' + d + '</span>';
  const cs = document.getElementById('clockStatus');
  if(cs) cs.textContent = t + ' · ' + d;
}
setInterval(updateClock, 1000);
updateClock();

// ── FILTER ───────────────────────────────────────
function setFilter(f, btn) {
  filter = f;
  document.querySelectorAll('.xp-filter-btn').forEach(b => b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  renderList();
}

// ── NOTE LIST ─────────────────────────────────────
function getFiltered() {
  const q = (document.getElementById('searchInput')?.value || '').toLowerCase();
  return notes.filter(n => {
    if(q && !n.title.toLowerCase().includes(q) && !(n.content||'').toLowerCase().includes(q)) return false;
    if(filter === 'reminder') return n.reminder;
    if(filter === 'pinned')   return n.pinned;
    if(filter === 'done')     return n.done;
    if(filter === 'high')     return n.priority === 'high';
    return true;
  }).sort((a,b) => {
    if(a.pinned !== b.pinned) return a.pinned ? -1 : 1;
    return (b.id||'').localeCompare(a.id||'');
  });
}

function renderList() {
  const list = document.getElementById('noteList');
  const filtered = getFiltered();
  document.getElementById('noteCount').textContent = notes.length;

  if(!filtered.length) {
    list.innerHTML = `<div class="xp-empty-list">
      <div class="xp-empty-list-icon">📂</div>
      <p>No hay notas.<br>Prueba otro filtro o crea una nueva.</p>
    </div>`;
    return;
  }

  list.innerHTML = filtered.map(n => {
    const icon = n.pinned ? '📌' : n.done ? '✅' : n.reminder ? '⏰' : '📄';
    const dateStr = n.date ? n.date + (n.time ? ' ' + n.time : '') : '—';
    const priorityDot = n.priority === 'high' ? '🔴 ' : n.priority === 'low' ? '🟢 ' : '';
    return `<div class="xp-note-item ${n.id === currentId ? 'active' : ''} ${n.done ? 'done' : ''}"
               onclick="selectNote('${n.id}')">
      <div class="xp-note-item-icon">${icon}</div>
      <div class="xp-note-item-body">
        <div class="xp-note-item-title">${priorityDot}${escHtml(n.title||'Sin título')}</div>
        <div class="xp-note-item-date">${escHtml(dateStr)}</div>
      </div>
      <button class="xp-note-item-del" onclick="event.stopPropagation();deleteNote('${n.id}')" title="Eliminar">✕</button>
    </div>`;
  }).join('');
}

// ── SELECT NOTE ───────────────────────────────────
function selectNote(id) {
  currentId = id;
  renderList();
  renderEditor();
}

// ── TOGGLE FECHA/HORA (recordatorio) ─────────────
function toggleReminderBlock(checked) {
  const block = document.getElementById('reminderDatetimeBlock');
  if(!block) return;
  if(checked) {
    block.classList.remove('hidden');
  } else {
    block.classList.add('hidden');
  }
  updateField('reminder', checked);
}

// ── EDITOR ──────────────────────────────────────
// ORDEN: Título → Descripción → Recordatorio → Fecha/Hora → Prioridad → Categoría
function renderEditor() {
  const body = document.getElementById('editorBody');
  const statusEl = document.getElementById('editorStatus');
  const n = notes.find(x => x.id === currentId);

  if(!n) {
    body.innerHTML = `<div class="xp-welcome">
      <div class="xp-welcome-icon">📅</div>
      <h2>Calendario de Notas</h2>
      <p>Selecciona una nota de la lista o crea una nueva.</p>
      <br>
      <button class="xp-btn primary" onclick="newNote()">📝 Nueva nota</button>
    </div>`;
    statusEl.textContent = 'Sin nota seleccionada';
    document.getElementById('pinBtn').textContent = '📌 Fijar';
    document.getElementById('doneBtn').textContent = '✅ Marcar lista';
    return;
  }

  statusEl.textContent = `Editando: ${n.title||'Sin título'}`;
  document.getElementById('pinBtn').textContent = n.pinned ? '📌 Desfijar' : '📌 Fijar';
  document.getElementById('doneBtn').textContent = n.done ? '↩️ Desmarcar' : '✅ Marcar lista';

  // Banner de alerta si el recordatorio ya venció
  const now = new Date();
  let reminderBanner = '';
  if(n.reminder && n.date) {
    const noteDate = new Date(n.date + (n.time ? 'T' + n.time : 'T00:00'));
    const overdue = noteDate < now && !n.done;
    reminderBanner = `<div class="xp-reminder-box ${overdue ? 'overdue' : ''}">
      <div class="xp-reminder-icon">${overdue ? '⚠️' : '⏰'}</div>
      <div class="xp-reminder-info">
        <div class="xp-reminder-title">${overdue ? 'Recordatorio vencido' : 'Recordatorio activo'}</div>
        <div class="xp-reminder-time">${n.date}${n.time ? ' a las ' + n.time : ''}</div>
      </div>
    </div>`;
  }

  const datetimeHidden = n.reminder ? '' : 'hidden';

  body.innerHTML = `
    ${reminderBanner}
    <div class="xp-form-section">

      <!-- 1. TÍTULO -->
      <div class="xp-field">
        <label class="xp-label">📝 Título</label>
        <input class="xp-input" id="fTitle" value="${escAttr(n.title||'')}"
          oninput="updateField('title',this.value)" placeholder="Título de la nota...">
      </div>

      <!-- 2. DESCRIPCIÓN -->
      <div class="xp-field">
        <label class="xp-label">📄 Descripción</label>
        <textarea class="xp-textarea" id="fContent" rows="6"
          oninput="updateField('content',this.value)"
          placeholder="Escribe aquí los detalles de la nota...">${escHtml(n.content||'')}</textarea>
      </div>

      <!-- 3. RECORDATORIO (checkbox) -->
      <div class="xp-field">
        <label class="xp-label" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
          <input type="checkbox" id="fReminder" ${n.reminder?'checked':''}
            onchange="toggleReminderBlock(this.checked)">
          ⏰ Activar recordatorio
        </label>
      </div>

      <!-- 4. FECHA Y HORA DEL AVISO (visible solo si recordatorio activo) -->
      <div class="xp-reminder-datetime ${datetimeHidden}" id="reminderDatetimeBlock">
        <div style="font-size:10px;color:#4060a0;font-weight:bold;margin-bottom:2px;">📅 Fecha y hora del aviso</div>
        <div class="xp-form-row">
          <div class="xp-field">
            <label class="xp-label">Fecha</label>
            <input class="xp-input" type="date" id="fDate" value="${escAttr(n.date||'')}"
              onchange="updateField('date',this.value)">
          </div>
          <div class="xp-field">
            <label class="xp-label">Hora</label>
            <input class="xp-input" type="time" id="fTime" value="${escAttr(n.time||'')}"
              onchange="updateField('time',this.value)">
          </div>
        </div>
      </div>

      <!-- 5. PRIORIDAD -->
      <div class="xp-field">
        <label class="xp-label">⚡ Prioridad</label>
        <div class="xp-priority-group">
          <button class="xp-priority-btn ${n.priority==='low'?'active-low':''}"
            onclick="updateField('priority','low');renderEditor()">🟢 Baja</button>
          <button class="xp-priority-btn ${n.priority==='medium'?'active-medium':''}"
            onclick="updateField('priority','medium');renderEditor()">🟡 Media</button>
          <button class="xp-priority-btn ${n.priority==='high'?'active-high':''}"
            onclick="updateField('priority','high');renderEditor()">🔴 Alta</button>
        </div>
      </div>

      <!-- 6. CATEGORÍA -->
      <div class="xp-field">
        <label class="xp-label">📁 Categoría</label>
        <div class="xp-cat-group">
          ${CATS.map(c => `<button class="xp-cat-btn ${n.category===c.id?'active':''}"
            onclick="updateField('category','${c.id}');renderEditor()">${c.label}</button>`).join('')}
        </div>
      </div>

      <!-- ACCIONES -->
      <div style="display:flex;gap:6px;justify-content:flex-end;padding-top:4px;border-top:1px solid var(--xp-border-dark);margin-top:4px;">
        <button class="xp-btn primary" onclick="saveCurrentNote(true)">💾 Guardar</button>
        <button class="xp-btn danger" onclick="deleteCurrentNote()">🗑️ Eliminar</button>
      </div>
    </div>`;
}

// ── CRUD ──────────────────────────────────────────
function newNote() {
  const n = {
    id: uid(),
    title: 'Nueva nota',
    content: '',
    date: new Date().toISOString().slice(0,10),
    time: '',
    priority: 'medium',
    category: 'general',
    done: false,
    pinned: false,
    reminder: false,
  };
  notes.unshift(n);
  currentId = n.id;
  save();
  renderList();
  renderEditor();
  setStatus('Nueva nota creada');
  setTimeout(() => document.getElementById('fTitle')?.select(), 50);
}

function updateField(field, value) {
  const n = notes.find(x => x.id === currentId);
  if(n) { n[field] = value; save(); renderList(); }
}

function saveCurrentNote(notify) {
  const n = notes.find(x => x.id === currentId);
  if(!n) return;
  const title = document.getElementById('fTitle')?.value;
  const content = document.getElementById('fContent')?.value;
  if(title !== undefined) n.title = title;
  if(content !== undefined) n.content = content;
  save();
  renderList();
  if(notify) {
    setStatus('✅ Nota guardada');
    scheduleReminder(n);
  }
}

function deleteNote(id) {
  if(!confirm('¿Eliminar esta nota?')) return;
  cancelReminder(id);
  notes = notes.filter(x => x.id !== id);
  if(currentId === id) currentId = null;
  save();
  renderList();
  renderEditor();
  setStatus('Nota eliminada');
}

function deleteCurrentNote() {
  if(currentId) deleteNote(currentId);
}

function togglePin() {
  const n = notes.find(x => x.id === currentId);
  if(n) { n.pinned = !n.pinned; save(); renderList(); renderEditor(); }
}

function toggleDone() {
  const n = notes.find(x => x.id === currentId);
  if(n) { n.done = !n.done; save(); renderList(); renderEditor(); }
}

// ── EXPORT ───────────────────────────────────────
function exportCurrentNote() {
  const n = notes.find(x => x.id === currentId);
  if(!n) { setStatus('⚠️ Selecciona una nota primero'); return; }
  const text = `TÍTULO: ${n.title}\nFECHA: ${n.date} ${n.time}\nPRIORIDAD: ${n.priority}\nCATEGORÍA: ${n.category}\n\n${n.content||''}`;
  const blob = new Blob([text], {type:'text/plain'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = (n.title||'nota').replace(/[^a-z0-9]/gi,'_') + '.txt';
  a.click();
  setStatus('📤 Nota exportada');
}

// ── STATUS ───────────────────────────────────────
function setStatus(msg) {
  const el = document.getElementById('statusMsg');
  if(el) el.textContent = msg;
}

// ── NOTIFICATIONS ─────────────────────────────────
function updateNotifStatus() {
  const dot = document.getElementById('notifDot');
  const lbl = document.getElementById('notifLabel');
  if(Notification.permission === 'granted') {
    dot?.classList.replace('yellow','green');
    if(lbl) lbl.textContent = 'Notificaciones: activas';
  } else if(Notification.permission === 'denied') {
    dot?.classList.replace('yellow','red');
    if(lbl) lbl.textContent = 'Notificaciones: bloqueadas';
  }
}

function requestNotifPermission() {
  if(!('Notification' in window)) { setStatus('⚠️ Tu navegador no soporta notificaciones'); return; }
  Notification.requestPermission().then(p => {
    updateNotifStatus();
    if(p === 'granted') {
      setStatus('✅ Notificaciones activadas');
      scheduleAllReminders();
    } else {
      setStatus('⚠️ Notificaciones bloqueadas por el usuario');
    }
  });
}

function scheduleReminder(n) {
  if(!n.reminder || !n.date) return;
  cancelReminder(n.id);
  const target = new Date(n.date + (n.time ? 'T' + n.time : 'T09:00'));
  const diff = target - Date.now();
  if(diff <= 0) return;
  notifTimers[n.id] = setTimeout(() => {
    triggerReminder(n);
    // Marcar como enviado en la API
    fetch('api/recordatorios.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({id: parseInt(n.id) || n.id})
    }).catch(() => {});
  }, diff);
}

function cancelReminder(id) {
  if(notifTimers[id]) { clearTimeout(notifTimers[id]); delete notifTimers[id]; }
}

function scheduleAllReminders() {
  notes.forEach(n => scheduleReminder(n));
}

function triggerReminder(n) {
  showModal(n);
  if(Notification.permission === 'granted') {
    new Notification('⏰ ' + n.title, {
      body: (n.date||'') + (n.time ? ' a las ' + n.time : '') + '\n' + (n.content||'').slice(0,100),
      icon: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">📅</text></svg>'
    });
  }
  showBalloon(n.title, 'Recordatorio: ' + (n.date||'') + (n.time ? ' a las ' + n.time : ''), '⏰');
}

function testReminderNow() {
  const n = notes.find(x => x.id === currentId) || notes[0];
  if(!n) { setStatus('⚠️ No hay nota seleccionada'); return; }
  triggerReminder(n);
}

function showModal(n) {
  document.getElementById('modalTitle').textContent = n.title || 'Sin título';
  document.getElementById('modalContent').textContent =
    (n.date ? 'Fecha: ' + n.date + (n.time ? ' a las ' + n.time : '') + '\n' : '') +
    (n.content || '');
  document.getElementById('reminderModal').classList.add('active');
  snoozedNote = n;
}

function closeReminderModal() {
  document.getElementById('reminderModal').classList.remove('active');
}

function snoozeReminder() {
  if(!snoozedNote) return;
  closeReminderModal();
  setTimeout(() => triggerReminder(snoozedNote), 5 * 60 * 1000);
  setStatus('🕒 Recordatorio pospuesto 5 minutos');
}

// ── BALLOON TOASTS ───────────────────────────────
function showBalloon(title, msg, icon) {
  const c = document.getElementById('balloonContainer');
  if(!c) return;
  const b = document.createElement('div');
  b.className = 'xp-balloon';
  b.innerHTML = `<div class="xp-balloon-icon">${icon||'🔔'}</div>
    <div class="xp-balloon-content">
      <div class="xp-balloon-title">${escHtml(title)}</div>
      <div class="xp-balloon-msg">${escHtml(msg)}</div>
    </div>
    <button class="xp-balloon-close" onclick="this.parentElement.remove()">✕</button>`;
  c.appendChild(b);
  setTimeout(() => b.remove(), 6000);
}

// ── HELP ─────────────────────────────────────────
function showHelp() {
  showBalloon('Calendario de Notas XP',
    'Crea notas con título, descripción y recordatorio. Activa notificaciones para recibir avisos.',
    '❓');
}

// ── UTILS ────────────────────────────────────────
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
  return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── KEYBOARD SHORTCUTS ────────────────────────────
document.addEventListener('keydown', e => {
  if(e.ctrlKey && e.key === 's') { e.preventDefault(); saveCurrentNote(true); }
  if(e.ctrlKey && e.key === 'n') { e.preventDefault(); newNote(); }
});

// ── INIT ──────────────────────────────────────────
load();
renderList();
renderEditor();
scheduleAllReminders();
updateNotifStatus();
if(Notification.permission === 'default') {
  setTimeout(() => {
    showBalloon('Activar notificaciones',
      'Haz clic en "Notificaciones" para recibir avisos de escritorio.',
      '🔔');
  }, 2000);
}
</script>

</body>
</html>

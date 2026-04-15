// ============================================================
// BLOQUE 5c — js/xp-ui.js
// ============================================================
// ¿Qué hace este archivo?
//   Gestiona todos los efectos visuales estilo Windows XP:
//   - Reloj de la taskbar
//   - Mensajes de estado en la statusbar
//   - Diálogo de confirmación modal (reemplaza window.confirm)
//   - Globo de notificación (balloon tooltip)
//
// Conceptos JS que aprenderás aquí:
//   - Promise: objeto que representa un resultado futuro
//   - Resolver/Rechazar una Promesa manualmente
//   - Closures: función que recuerda su contexto externo
//   - Event delegation: un solo listener para múltiples botones
// ============================================================


// ------------------------------------------------------------
// RELOJ DE LA TASKBAR
// Actualiza el reloj cada segundo.
// ------------------------------------------------------------

export function actualizarReloj() {
  const reloj = document.getElementById('xp-clock');
  if (!reloj) return;

  function tick() {
    const ahora = new Date();

    // Formatear hora como 'HH:MM AM/PM'
    // toLocaleTimeString() formatea la hora según el locale del sistema
    // 'es-CL' = español Chile
    reloj.textContent = ahora.toLocaleTimeString('es-CL', {
      hour:   '2-digit',
      minute: '2-digit',
      hour12: false  // formato 24 horas
    });
  }

  tick(); // ejecutar de inmediato
  setInterval(tick, 1000); // repetir cada 1000 ms = 1 segundo
}


// ------------------------------------------------------------
// BARRA DE ESTADO — Actualizar texto
// ------------------------------------------------------------

export function actualizarStatusBar(texto) {
  const status = document.getElementById('status-texto');
  if (status) status.textContent = texto;
}


// ------------------------------------------------------------
// MENSAJE FLASH — Mensaje temporal en la statusbar
// El texto aparece y desaparece después de 3 segundos.
// ------------------------------------------------------------

export function mostrarMensajeXP(texto, tipo = 'ok') {
  const status = document.getElementById('status-texto');
  if (!status) return;

  // Guardar el texto anterior para restaurarlo después
  const textoAnterior = status.textContent;

  // Colores según el tipo del mensaje
  const colores = {
    ok:    '#008000', // verde
    error: '#cc0000'  // rojo
  };

  status.textContent = texto;
  status.style.color = colores[tipo] || '#000000';

  // setTimeout: restaurar el texto original después de 3 segundos
  setTimeout(() => {
    status.textContent = textoAnterior;
    status.style.color = ''; // restaurar color por defecto
  }, 3000);
}


// ------------------------------------------------------------
// DIÁLOGO DE CONFIRMACIÓN ESTILO XP
// Reemplaza el window.confirm() genérico del navegador.
// Devuelve una Promesa que se resuelve con true (Sí) o false (No).
// ------------------------------------------------------------
// ¿Qué es una Promesa?
//   Es un objeto que representa un valor que puede no estar disponible aún.
//   Tiene 3 estados: pendiente → resuelta (éxito) o rechazada (error).
//   resolve(valor) → la promesa terminó con éxito, el valor es el resultado.
//   reject(error)  → la promesa falló.
//   Se usa con async/await: const resultado = await miPromesa();
// ------------------------------------------------------------

export function confirmarXP(titulo, mensaje) {
  return new Promise((resolve) => {
    // resolve es la función que "resuelve" la promesa.
    // Cuando el usuario hace clic en Sí → resolve(true)
    // Cuando hace clic en No          → resolve(false)

    const overlay = document.getElementById('xp-overlay');
    const dialog  = document.getElementById('xp-dialog');
    if (!overlay || !dialog) {
      // Si no existe el diálogo HTML, usar confirm nativo como fallback
      resolve(window.confirm(`${titulo}\n${mensaje}`));
      return;
    }

    // Actualizar textos del diálogo con los parámetros recibidos
    const tituloEl  = dialog.querySelector('.xp-dialog-title-text');
    const mensajeEl = dialog.querySelector('.xp-dialog-message');
    if (tituloEl)  tituloEl.textContent  = titulo;
    if (mensajeEl) mensajeEl.textContent = mensaje;

    // Mostrar el overlay
    overlay.classList.add('visible');

    // Función para cerrar el diálogo y resolver la promesa
    // Es un closure: recuerda la variable 'resolve' de la función padre
    function cerrar(resultado) {
      overlay.classList.remove('visible');

      // Eliminar los listeners para evitar múltiples disparos
      btnSi.removeEventListener('click', clickSi);
      btnNo.removeEventListener('click', clickNo);

      resolve(resultado); // resolver la promesa con true o false
    }

    const btnSi = dialog.querySelector('#dialog-btn-si');
    const btnNo = dialog.querySelector('#dialog-btn-no');

    const clickSi = () => cerrar(true);
    const clickNo = () => cerrar(false);

    btnSi?.addEventListener('click', clickSi);
    btnNo?.addEventListener('click', clickNo);

    // Cerrar con Escape → equivale a No
    function teclaEscape(e) {
      if (e.key === 'Escape') {
        document.removeEventListener('keydown', teclaEscape);
        cerrar(false);
      }
    }
    document.addEventListener('keydown', teclaEscape);
  });
}


// ------------------------------------------------------------
// CERRAR GLOBO DE NOTIFICACIÓN
// El botón × del globo llama a esta función.
// ------------------------------------------------------------

export function cerrarGlobo() {
  const globo = document.getElementById('xp-balloon');
  if (globo) globo.classList.remove('visible');
}


// ------------------------------------------------------------
// INICIALIZAR CONTROLES XP (cerrar globo, etc.)
// Se llama desde app.js al cargar la página.
// ------------------------------------------------------------

export function inicializarUI() {
  // Botón de cerrar el globo de notificación
  const btnCerrarGlobo = document.querySelector('.xp-balloon-close');
  btnCerrarGlobo?.addEventListener('click', cerrarGlobo);

  // Tecla Escape también cierra el globo
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrarGlobo();
  });
}

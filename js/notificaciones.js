// ============================================================
// BLOQUE 5b — js/notificaciones.js
// ============================================================
// ¿Qué hace este archivo?
//   Maneja TODOS los recordatorios de las notas.
//   Compara la fecha/hora de cada nota con la hora actual
//   y dispara una notificación cuando llega el momento.
//
// Conceptos JS que aprenderás aquí:
//   - Notification API: notificaciones nativas del SO
//   - setInterval(): ejecutar código cada X milisegundos
//   - Date: trabajar con fechas y horas en JavaScript
//   - Set: colección de valores únicos (sin duplicados)
//   - Módulos ES6: export / import
// ============================================================


// ------------------------------------------------------------
// SET DE NOTAS YA NOTIFICADAS
// ------------------------------------------------------------
// Set es una colección que NO permite duplicados.
// Lo usamos para no disparar la misma notificación dos veces.
// Si usáramos un Array, podríamos agregar el mismo ID múltiples veces.
// Con Set: notificadas.add(5) → agrega 5 una sola vez.
// ------------------------------------------------------------

const notificadas = new Set();


// ------------------------------------------------------------
// SOLICITAR PERMISO DE NOTIFICACIONES
// ------------------------------------------------------------
// Los navegadores requieren que el usuario AUTORICE las notificaciones.
// Solo se puede pedir el permiso después de un gesto del usuario (clic).
// Estados posibles: 'default', 'granted' (concedido), 'denied' (denegado)
// ------------------------------------------------------------

export async function pedirPermisoNotificaciones() {
  // 'Notification' es la API nativa del navegador para notificaciones de escritorio.
  // ¿Funciona en Windows? Sí — usa las notificaciones del Centro de Acción.

  if (!('Notification' in window)) {
    console.warn('Este navegador no soporta notificaciones de escritorio.');
    return false;
  }

  if (Notification.permission === 'granted') return true;

  if (Notification.permission !== 'denied') {
    // requestPermission() muestra el popup del navegador preguntando al usuario
    const permiso = await Notification.requestPermission();
    return permiso === 'granted';
  }

  return false;
}


// ------------------------------------------------------------
// DISPARAR NOTIFICACIÓN DE ESCRITORIO
// Parámetros:
//   titulo   → texto del título de la notificación
//   cuerpo   → texto del cuerpo
//   notaId   → ID de la nota (para evitar duplicados)
// ------------------------------------------------------------

function dispararNotificacion(titulo, cuerpo, notaId) {
  if (Notification.permission !== 'granted') return;

  // new Notification() crea y muestra la notificación del SO
  const notif = new Notification(`📅 Recordatorio: ${titulo}`, {
    body: cuerpo,
    icon: 'assets/icono-xp.png', // icono opcional
    tag:  `nota-${notaId}`,      // tag evita notificaciones duplicadas a nivel del SO
  });

  // Al hacer clic en la notificación → enfocar la ventana del navegador
  notif.onclick = () => {
    window.focus();
    notif.close();
  };

  // Guardar el ID para no notificar de nuevo en el mismo ciclo
  notificadas.add(notaId);

  // También mostrar el globo XP dentro de la app
  mostrarGloboXP(titulo, cuerpo);

  // Reproducir sonido de alerta usando Web Audio API
  reproducirSonidoAlerta();
}


// ------------------------------------------------------------
// GLOBO DE NOTIFICACIÓN DENTRO DE LA APP (estilo XP)
// Es el tooltip amarillo sobre la taskbar de XP.
// ------------------------------------------------------------

function mostrarGloboXP(titulo, cuerpo) {
  const globo = document.getElementById('xp-balloon');
  if (!globo) return;

  const globoTitulo  = globo.querySelector('.xp-balloon-title');
  const globoCuerpo  = globo.querySelector('.xp-balloon-body');

  if (globoTitulo) globoTitulo.textContent = `⏰ ${titulo}`;
  if (globoCuerpo) globoCuerpo.textContent = cuerpo;

  globo.classList.add('visible');

  // Auto-ocultar después de 8 segundos
  // setTimeout() ejecuta la función UNA SOLA VEZ después del delay (ms)
  setTimeout(() => globo.classList.remove('visible'), 8000);
}


// ------------------------------------------------------------
// SONIDO DE ALERTA usando Web Audio API
// Sin archivos externos — genera el tono programáticamente.
// Es el "ding" clásico de Windows.
// ------------------------------------------------------------

function reproducirSonidoAlerta() {
  try {
    // AudioContext es la API de audio del navegador
    const ctx        = new (window.AudioContext || window.webkitAudioContext)();

    // OscillatorNode genera una onda sonora pura
    const oscilador  = ctx.createOscillator();

    // GainNode controla el volumen
    const ganancia   = ctx.createGain();

    // Conectar: oscilador → control de volumen → salida de audio
    oscilador.connect(ganancia);
    ganancia.connect(ctx.destination);

    oscilador.type = 'sine';         // onda sinusoidal (sonido suave)
    oscilador.frequency.value = 880; // 880 Hz = La5 (nota musical La)

    ganancia.gain.setValueAtTime(0.3, ctx.currentTime);
    // Bajar el volumen progresivamente (fade out) en 0.3 segundos
    ganancia.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);

    oscilador.start(ctx.currentTime);
    oscilador.stop(ctx.currentTime + 0.3);

  } catch (e) {
    // Si el navegador bloquea el audio, ignoramos el error silenciosamente
    console.warn('No se pudo reproducir sonido:', e);
  }
}


// ------------------------------------------------------------
// VERIFICAR RECORDATORIOS PENDIENTES
// Se ejecuta cada minuto vía setInterval.
// Compara la fecha/hora de cada nota con la hora actual.
// ------------------------------------------------------------

function verificarRecordatorios(notas) {
  // new Date() crea un objeto con la fecha y hora ACTUALES
  const ahora  = new Date();

  // Construir string 'YYYY-MM-DD' de la fecha actual
  // Esto es equivalente a ahora.toISOString().split('T')[0]
  const hoyFecha = ahora.toISOString().split('T')[0];

  // Hora actual en formato 'HH:MM' (sin segundos)
  const ahoraHora = `${String(ahora.getHours()).padStart(2,'0')}:${String(ahora.getMinutes()).padStart(2,'0')}`;

  notas.forEach(nota => {
    // Si ya fue notificada en esta sesión, saltar
    if (notificadas.has(nota.id)) return;

    // Comparar fecha y hora de la nota con el momento actual
    // substr(0,5) recorta '08:30:00' → '08:30'
    const esFechaHoy   = nota.fecha === hoyFecha;
    const esHoraAhora  = nota.hora && nota.hora.substr(0, 5) === ahoraHora;

    if (esFechaHoy && esHoraAhora) {
      dispararNotificacion(
        nota.titulo,
        nota.descripcion || 'Tienes una tarea programada.',
        nota.id
      );
    }
  });
}


// ------------------------------------------------------------
// INICIAR SISTEMA DE RECORDATORIOS
// Llamada desde app.js después de cargar las notas.
// ------------------------------------------------------------

export async function iniciarRecordatorios(notas) {
  // Primero pedir permiso (si aún no fue concedido)
  await pedirPermisoNotificaciones();

  // Verificar inmediatamente al cargar
  verificarRecordatorios(notas);

  // setInterval() repite la función cada X milisegundos
  // 60000 ms = 60 segundos = 1 minuto
  // Guardamos la referencia para poder cancelarla con clearInterval() si hace falta
  const intervalo = setInterval(() => {
    // Nota: 'notas' aquí es el array del momento de inicio.
    // Si quieres que siempre use los datos frescos, pasa una función que retorne el array actual.
    verificarRecordatorios(notas);
  }, 60_000); // 60_000 = separador de miles de JS (igual que 60000, más legible)

  return intervalo;
}


// ------------------------------------------------------------
// PROGRAMAR RECORDATORIO INDIVIDUAL
// Para notas futuras (no de hoy) programamos un timeout exacto.
// Llamada desde app.js cuando se crea o edita una nota.
// ------------------------------------------------------------

export function programarRecordatorio(nota) {
  if (!nota.fecha || !nota.hora) return;

  // Construir objeto Date combinando fecha y hora de la nota
  // '2026-04-20' + 'T' + '09:00' = '2026-04-20T09:00' → Date válido
  const fechaHoraNota = new Date(`${nota.fecha}T${nota.hora.substr(0,5)}`);
  const ahora         = new Date();

  // Diferencia en milisegundos entre la hora de la nota y ahora
  const diferencia = fechaHoraNota.getTime() - ahora.getTime();
  // .getTime() devuelve milisegundos desde el 1 de enero de 1970 (Unix Epoch)

  // Si la nota es en el pasado o ya fue notificada, ignorar
  if (diferencia <= 0 || notificadas.has(nota.id)) return;

  // setTimeout exacto para la hora precisa
  // Máximo útil: ~24.8 días (límite de setTimeout de 32 bits)
  setTimeout(() => {
    dispararNotificacion(
      nota.titulo,
      nota.descripcion || 'Tienes una tarea programada.',
      nota.id
    );
  }, diferencia);

  console.log(
    `⏰ Recordatorio programado: "${nota.titulo}" en ${Math.round(diferencia / 60000)} minutos`
  );
}

// ============================================================
// BLOQUE 5a — js/app.js
// ============================================================
// ¿Qué hace este archivo?
//   Es el CONTROLADOR del frontend. Conecta los partials PHP
//   (sidebar, form-nota) con la API REST del bloque 2.
//
// Flujo completo:
//   1. Al cargar la página → pide todas las notas a la API
//   2. Renderiza la lista en el sidebar
//   3. El usuario hace clic en una nota → carga el formulario
//   4. Al guardar → POST o PUT según sea nueva o edición
//   5. Al eliminar → DELETE
//   6. Inicia el sistema de recordatorios (notificaciones.js)
//
// Conceptos JS que aprenderás aquí:
//   - fetch() + async/await: peticiones HTTP sin recargar la página
//   - Promesas (Promise): manejo de operaciones asíncronas
//   - DOM Manipulation: leer y modificar el HTML
//   - Event Listeners: reaccionar a clics y teclado
//   - Módulos: separar responsabilidades en archivos distintos
//   - try/catch: manejo de errores
// ============================================================


// ------------------------------------------------------------
// 0. IMPORTACIONES
// ------------------------------------------------------------
// Importamos funciones de los otros módulos JS.
// Esto es separación de responsabilidades:
//   - notificaciones.js → todo lo relacionado con recordatorios
//   - xp-ui.js          → efectos visuales estilo Windows XP
// ------------------------------------------------------------

import { iniciarRecordatorios, programarRecordatorio } from './notificaciones.js';
import { mostrarMensajeXP, confirmarXP, actualizarReloj, actualizarStatusBar } from './xp-ui.js';


// ------------------------------------------------------------
// 1. CONSTANTE DE LA API
// ------------------------------------------------------------
// Centralizamos la URL base en una constante.
// Si cambia el servidor, solo lo cambiamos aquí.
// En producción sería: 'https://mi-servidor.com/api/notas.php'
// ------------------------------------------------------------

const API_URL = 'api/notas.php';


// ------------------------------------------------------------
// 2. REFERENCIAS AL DOM
// ------------------------------------------------------------
// Guardamos referencias a los elementos HTML que usaremos.
// Es más eficiente que llamar getElementById() repetidamente.
// Estos IDs vienen de los partials PHP del bloque 4.
// ------------------------------------------------------------

const listaNotas     = document.getElementById('lista-notas');     // <ul> sidebar
const buscador       = document.getElementById('buscador');         // <input> buscar
const btnNuevaNota   = document.getElementById('btn-nueva-nota');   // botón Nueva Nota
const btnGuardar     = document.getElementById('btn-guardar');      // botón Guardar
const btnEliminar    = document.getElementById('btn-eliminar');     // botón Eliminar (puede ser null)
const btnExportar    = document.getElementById('btn-exportar');     // botón Exportar .txt

// Campos del formulario
const campoId          = document.getElementById('nota-id');          // hidden
const campoTitulo      = document.getElementById('campo-titulo');
const campoDescripcion = document.getElementById('campo-descripcion');
const campoFecha       = document.getElementById('campo-fecha');
const campoHora        = document.getElementById('campo-hora');
const campoPrioridad   = document.getElementById('campo-prioridad');
const campoCategoria   = document.getElementById('campo-categoria');
const statusTexto      = document.getElementById('status-texto');     // barra de estado


// ------------------------------------------------------------
// 3. ESTADO LOCAL
// ------------------------------------------------------------
// Variable que guarda la lista completa de notas en memoria.
// Así podemos filtrar sin volver a pedir la API.
// En React/Vue esto sería el "state".
// ------------------------------------------------------------

let todasLasNotas = [];


// ============================================================
// FUNCIONES DE API — Comunicación con el backend PHP
// ============================================================


// ------------------------------------------------------------
// GET — Cargar todas las notas
// Llama: GET /api/notas.php
// ------------------------------------------------------------
// async/await = forma moderna de manejar operaciones asíncronas.
// 'async' marca que la función retorna una Promesa.
// 'await' pausa la ejecución hasta que la Promesa se resuelve.
// Sin async/await tendríamos que usar .then().catch() encadenados.
// ------------------------------------------------------------

async function cargarNotas() {
  try {
    // fetch() hace una petición HTTP. Devuelve una Promesa.
    // await espera que llegue la respuesta del servidor.
    const respuesta = await fetch(API_URL);

    // .ok es true si el código HTTP es 200-299
    if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);

    // .json() lee el cuerpo de la respuesta como JSON.
    // También es asíncrono, por eso otro await.
    todasLasNotas = await respuesta.json();

    renderizarLista(todasLasNotas);
    iniciarRecordatorios(todasLasNotas); // arrancar sistema de alertas
    actualizarStatusBar(`${todasLasNotas.length} notas cargadas`);

  } catch (error) {
    // catch atrapa cualquier error: red caída, JSON inválido, etc.
    console.error('Error cargando notas:', error);
    mostrarMensajeXP('⚠️ No se pudieron cargar las notas. Verifica el servidor.', 'error');
  }
}


// ------------------------------------------------------------
// POST — Crear nota nueva
// Llama: POST /api/notas.php  con body JSON
// ------------------------------------------------------------

async function crearNota(datos) {
  try {
    const respuesta = await fetch(API_URL, {
      method: 'POST',
      // Content-Type le dice al servidor que el body es JSON
      headers: { 'Content-Type': 'application/json' },
      // JSON.stringify() convierte el objeto JS a texto JSON
      // Es el proceso inverso a JSON.parse()
      body: JSON.stringify(datos)
    });

    const resultado = await respuesta.json();

    if (!respuesta.ok) throw new Error(resultado.error || 'Error al crear');

    mostrarMensajeXP('✅ Nota guardada correctamente', 'ok');
    await cargarNotas(); // recargar lista con la nueva nota
    seleccionarNota(resultado.id); // seleccionar la nota recién creada

  } catch (error) {
    console.error('Error creando nota:', error);
    mostrarMensajeXP(`❌ ${error.message}`, 'error');
  }
}


// ------------------------------------------------------------
// PUT — Editar nota existente
// Llama: PUT /api/notas.php?id=5  con body JSON
// ------------------------------------------------------------

async function editarNota(id, datos) {
  try {
    // Template literal: `texto ${variable}` — forma moderna de concatenar
    const respuesta = await fetch(`${API_URL}?id=${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(datos)
    });

    const resultado = await respuesta.json();
    if (!respuesta.ok) throw new Error(resultado.error || 'Error al editar');

    mostrarMensajeXP('✅ Nota actualizada', 'ok');
    await cargarNotas();
    seleccionarNota(id);

  } catch (error) {
    console.error('Error editando nota:', error);
    mostrarMensajeXP(`❌ ${error.message}`, 'error');
  }
}


// ------------------------------------------------------------
// DELETE — Eliminar nota
// Llama: DELETE /api/notas.php?id=5
// ------------------------------------------------------------

async function eliminarNota(id) {
  // Primero pedimos confirmación con el diálogo estilo XP
  const confirmado = await confirmarXP(
    '¿Eliminar esta nota?',
    'Esta acción no se puede deshacer.'
  );

  if (!confirmado) return; // el usuario canceló

  try {
    const respuesta = await fetch(`${API_URL}?id=${id}`, {
      method: 'DELETE'
    });

    const resultado = await respuesta.json();
    if (!respuesta.ok) throw new Error(resultado.error || 'Error al eliminar');

    mostrarMensajeXP('🗑️ Nota eliminada', 'ok');
    limpiarFormulario();
    await cargarNotas();

  } catch (error) {
    console.error('Error eliminando nota:', error);
    mostrarMensajeXP(`❌ ${error.message}`, 'error');
  }
}


// ============================================================
// FUNCIONES DE UI — Manipulación del DOM
// ============================================================


// ------------------------------------------------------------
// Renderizar la lista de notas en el sidebar
// Recibe: array de notas
// ------------------------------------------------------------

function renderizarLista(notas) {
  // innerHTML vacío limpia la lista antes de repintarla
  listaNotas.innerHTML = '';

  if (notas.length === 0) {
    listaNotas.innerHTML = `
      <li class="xp-note-item" style="cursor:default; color:#888;">
        <span>No hay notas aún.</span>
        <span style="font-size:10px;">Haz clic en "Nueva Nota".</span>
      </li>`;
    return;
  }

  // forEach recorre el array y por cada nota crea un <li>
  notas.forEach(nota => {
    const li = document.createElement('li');
    li.className = 'xp-note-item';
    li.setAttribute('role', 'option');
    li.setAttribute('data-id', nota.id); // guardamos el ID en el elemento
    li.setAttribute('tabindex', '0');

    // Formatear fecha: '2026-04-15' → '15/04/2026'
    const fechaFormateada = nota.fecha
      ? nota.fecha.split('-').reverse().join('/')
      : '';

    // Hora: '08:30:00' → '08:30'
    const horaFormateada = nota.hora ? nota.hora.substring(0, 5) : '';

    // Etiqueta de prioridad con símbolo
    const iconoPrioridad = { alta: '▲', media: '■', baja: '▼' };
    const labelPrioridad = iconoPrioridad[nota.prioridad] || '■';

    // Template literal multi-línea para el HTML del item
    li.innerHTML = `
      <span class="xp-note-item-title">${nota.titulo}</span>
      <span class="xp-note-item-date">${fechaFormateada} ${horaFormateada}</span>
      <span class="xp-priority ${nota.prioridad}">
        ${labelPrioridad} ${nota.prioridad.charAt(0).toUpperCase() + nota.prioridad.slice(1)}
      </span>`;
    // .charAt(0).toUpperCase() → primera letra mayúscula
    // .slice(1) → el resto del texto

    // Event listener: al hacer clic en la nota, la cargamos
    li.addEventListener('click', () => seleccionarNota(nota.id));

    // Accesibilidad: también responde a Enter y Space (teclado)
    li.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') seleccionarNota(nota.id);
    });

    listaNotas.appendChild(li);
  });
}


// ------------------------------------------------------------
// Cargar una nota en el formulario al seleccionarla
// ------------------------------------------------------------

function seleccionarNota(id) {
  // Buscar la nota en el array local (sin llamar a la API de nuevo)
  // .find() devuelve el PRIMER elemento que cumple la condición
  const nota = todasLasNotas.find(n => n.id == id);
  if (!nota) return;

  // Marcar item activo en el sidebar
  document.querySelectorAll('.xp-note-item').forEach(li => {
    li.classList.toggle('active', li.dataset.id == id);
    // classList.toggle(clase, condicion) agrega o quita la clase
  });

  // Rellenar el formulario con los datos de la nota
  campoId.value          = nota.id;
  campoTitulo.value      = nota.titulo;
  campoDescripcion.value = nota.descripcion || '';
  campoFecha.value       = nota.fecha;
  campoHora.value        = nota.hora ? nota.hora.substring(0, 5) : '';
  campoPrioridad.value   = nota.prioridad;
  campoCategoria.value   = nota.categoria || '';

  // Mostrar/ocultar botón Eliminar según si hay nota seleccionada
  if (btnEliminar) {
    btnEliminar.style.display = 'flex';
    btnEliminar.dataset.id = nota.id;
  }

  actualizarStatusBar(`Editando: ${nota.titulo}`);
}


// ------------------------------------------------------------
// Limpiar el formulario para una nota nueva
// ------------------------------------------------------------

function limpiarFormulario() {
  campoId.value          = '';
  campoTitulo.value      = '';
  campoDescripcion.value = '';

  // Date.now() = timestamp en milisegundos. Es la forma JS de obtener la hora actual.
  // new Date() crea un objeto fecha con la fecha/hora actual.
  const ahora = new Date();

  // toISOString() devuelve '2026-04-15T20:30:00.000Z' (formato ISO 8601)
  // .split('T')[0] toma solo la parte de la fecha: '2026-04-15'
  campoFecha.value = ahora.toISOString().split('T')[0];

  // .getHours() y .getMinutes() devuelven número.
  // .toString().padStart(2, '0') agrega un 0 si es de un dígito: 9 → '09'
  campoHora.value = `${ahora.getHours().toString().padStart(2,'0')}:${ahora.getMinutes().toString().padStart(2,'0')}`;

  campoPrioridad.value = 'media';
  campoCategoria.value = '';

  // Quitar selección activa del sidebar
  document.querySelectorAll('.xp-note-item').forEach(li => li.classList.remove('active'));

  // Ocultar botón eliminar (no existe en nota nueva)
  if (btnEliminar) btnEliminar.style.display = 'none';

  actualizarStatusBar('Nueva nota');
  campoTitulo.focus(); // poner el cursor en el primer campo
}


// ------------------------------------------------------------
// Leer los datos del formulario y construir el objeto nota
// ------------------------------------------------------------

function leerFormulario() {
  // Objeto literal: estructura clave-valor con los datos del form
  return {
    titulo:      campoTitulo.value.trim(),
    descripcion: campoDescripcion.value.trim(),
    fecha:       campoFecha.value,
    hora:        campoHora.value,
    prioridad:   campoPrioridad.value,
    categoria:   campoCategoria.value.trim() || 'General'
  };
}


// ------------------------------------------------------------
// Exportar nota como archivo .txt
// Concepto: crear un Blob (Binary Large Object) y
// forzar la descarga con un <a> temporal.
// ------------------------------------------------------------

function exportarNota() {
  const id = campoId.value;
  if (!id) {
    mostrarMensajeXP('Selecciona una nota primero', 'error');
    return;
  }

  const titulo      = campoTitulo.value;
  const descripcion = campoDescripcion.value;
  const fecha       = campoFecha.value;
  const hora        = campoHora.value;
  const prioridad   = campoPrioridad.value;
  const categoria   = campoCategoria.value;

  // Template literal multi-línea para el contenido del archivo
  const contenido = `CALENDARIO VINTAGE — NOTA
${'='.repeat(40)}
Título:     ${titulo}
Fecha:      ${fecha.split('-').reverse().join('/')}
Hora:       ${hora}
Prioridad:  ${prioridad}
Categoría:  ${categoria}
${'='.repeat(40)}

${descripcion}

--- Exportado el ${new Date().toLocaleString('es-CL')} ---`;
  // 'es-CL' = locale de Chile → formato de fecha chileno

  // Blob: objeto que representa datos binarios (aquí texto plano)
  const blob = new Blob([contenido], { type: 'text/plain;charset=utf-8' });

  // URL.createObjectURL() crea una URL temporal que apunta al Blob
  const url = URL.createObjectURL(blob);

  // Crear un <a> invisible, hacer clic y eliminarlo
  const enlace = document.createElement('a');
  enlace.href = url;
  enlace.download = `nota-${titulo.replace(/\s+/g, '-').toLowerCase()}.txt`;
  // .replace(/\s+/g, '-') reemplaza espacios por guiones (regex)
  enlace.click();

  // Liberar la memoria del URL temporal
  URL.revokeObjectURL(url);
}


// ============================================================
// EVENT LISTENERS — Conectar los botones con las funciones
// ============================================================
// Los event listeners escuchan eventos del usuario (clic, input, etc.)
// y ejecutan una función cuando ocurren.
// ============================================================


// --- Botón "Nueva Nota" ---
btnNuevaNota?.addEventListener('click', limpiarFormulario);
// El ?. es optional chaining: si btnNuevaNota es null, no lanza error.


// --- Botón "Guardar" ---
btnGuardar?.addEventListener('click', async () => {
  const datos = leerFormulario();

  // Validación básica en el frontend (antes de llamar a la API)
  if (!datos.titulo) {
    mostrarMensajeXP('El título es obligatorio', 'error');
    campoTitulo.focus();
    return;
  }

  const id = campoId.value;

  if (id) {
    // Tiene ID → es edición → PUT
    await editarNota(parseInt(id), datos);
  } else {
    // Sin ID → es nueva → POST
    await crearNota(datos);
  }
});


// --- Botón "Eliminar" ---
btnEliminar?.addEventListener('click', () => {
  const id = btnEliminar.dataset.id;
  if (id) eliminarNota(parseInt(id));
});


// --- Botón "Exportar .txt" ---
btnExportar?.addEventListener('click', exportarNota);


// --- Buscador en tiempo real ---
buscador?.addEventListener('input', () => {
  const termino = buscador.value.toLowerCase().trim();

  // filter() devuelve un nuevo array con los elementos que cumplen la condición
  // .includes() verifica si un string contiene otro string
  const notasFiltradas = todasLasNotas.filter(nota =>
    nota.titulo.toLowerCase().includes(termino) ||
    (nota.categoria && nota.categoria.toLowerCase().includes(termino))
  );

  renderizarLista(notasFiltradas);
});


// --- Atajo de teclado: Ctrl+S para guardar ---
document.addEventListener('keydown', (e) => {
  if (e.ctrlKey && e.key === 's') {
    e.preventDefault(); // evitar que el navegador abra "Guardar página"
    btnGuardar?.click();
  }
  // Ctrl+N → nueva nota
  if (e.ctrlKey && e.key === 'n') {
    e.preventDefault();
    limpiarFormulario();
  }
});


// ============================================================
// INICIALIZACIÓN — Se ejecuta cuando el DOM está listo
// ============================================================
// DOMContentLoaded: se dispara cuando el HTML fue parseado completamente.
// Es el momento seguro para leer/modificar el DOM.
// ============================================================

document.addEventListener('DOMContentLoaded', async () => {
  actualizarReloj();        // iniciar el reloj de la taskbar
  limpiarFormulario();      // preparar formulario vacío
  await cargarNotas();      // cargar notas desde la API
});

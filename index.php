<?php
// ============================================================
// BLOQUE 6 — index.php
// ============================================================
// ¿Qué hace este archivo?
//   Es el PUNTO DE ENTRADA del proyecto. El primer archivo
//   que ejecuta PHP cuando el usuario abre la aplicación.
//
// Su responsabilidad es ENSAMBLAR la página completa:
//   1. Conectar la base de datos (bloque 1)
//   2. Cargar las notas existentes (bloque 2)
//   3. Preparar variables para los partials (bloque 4)
//   4. Incluir los partials: header, sidebar, form-nota
//   5. Renderizar el HTML final que ve el navegador
//   6. Cargar el CSS (bloque 3) y el JS (bloque 5)
//
// Arquitectura que aprenders aquí:
//   - Separación de responsabilidades (MVC simplificado)
//   - include vs require: diferencias y cuándo usar cada uno
//   - Flujo de una petición web de principio a fin
//   - Cómo PHP genera HTML dinámico
// ============================================================


// ------------------------------------------------------------
// 1. ZONA PHP PURA — Lógica antes de emitir HTML
// ------------------------------------------------------------
// REGLA DE ORO: toda la lógica PHP va ANTES del primer echo
// o del primer <?php ... ?> que emita HTML.
// Si envías cabeceras HTTP (header()) después de imprimir
// HTML, PHP lanza un error: "headers already sent".
// ------------------------------------------------------------

// require_once: incluye el archivo UNA sola vez y lanza error
// fatal si no existe. Ideal para dependencias críticas.
// include: incluye el archivo pero solo emite un WARNING si
// no existe, el script sigue ejecutándose.
// require_once > require > include_once > include
// Usa require_once para dependencias críticas (BD, config).
require_once 'config/database.php';


// ------------------------------------------------------------
// 2. OBTENER LAS NOTAS DESDE LA BASE DE DATOS
// ------------------------------------------------------------
// Llamamos a la función del bloque 1 para conectar.
// En este caso consultamos directamente aquí para tener
// las notas disponibles al renderizar el sidebar.
// En una app más grande esto iría en un archivo Model.
// ------------------------------------------------------------

$notas = []; // valor por defecto: array vacío

try {
    // Intentar conectar a la BD
    $pdo = conectarDB();

    // Consulta para el sidebar: solo los campos necesarios
    // (no traemos descripción completa para no sobrecargar)
    $stmt = $pdo->prepare(
        "SELECT id, titulo, fecha, hora, prioridad, categoria
         FROM notas
         ORDER BY fecha ASC, hora ASC"
    );
    $stmt->execute();
    $notas = $stmt->fetchAll(); // array de todas las notas

} catch (PDOException $e) {
    // PDOException es el tipo de excepción específico de PDO.
    // La atrapamos aquí para no mostrar errores internos al usuario.
    // En producción: loguear el error, no mostrarlo.
    $errorBD = 'No se pudo conectar a la base de datos.';
    // $e->getMessage() tiene el detalle técnico del error
    // error_log() lo escribe en el log del servidor (invisible al usuario)
    error_log('Error BD: ' . $e->getMessage());
}


// ------------------------------------------------------------
// 3. VARIABLES PARA LOS PARTIALS
// ------------------------------------------------------------
// Los partials del bloque 4 esperan estas variables.
// Las definimos aquí antes de hacer los includes.
// ------------------------------------------------------------

$titulo_ventana = 'Calendario Vintage — Mis Notas';
$nota_activa    = null; // null = formulario en modo "nueva nota"

?>
<!DOCTYPE html>
<!--
  A partir de aquí emitimos HTML.
  El bloque PHP de arriba ya terminó su ejecución.
  PHP y HTML pueden alternarse libremente en el mismo archivo.
-->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Título dinámico desde PHP -->
    <title><?php echo htmlspecialchars($titulo_ventana); ?></title>

    <!--
        BLOQUE 3 — Carga el CSS de Windows XP.
        La ruta es relativa a este archivo (index.php está en la raíz).
    -->
    <link rel="stylesheet" href="css/xp-style.css">

    <!--
        Favicon: el icono que aparece en la pestaña del navegador.
        Usamos un emoji como base64 para no depender de un archivo externo.
    -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>&#128197;</text></svg>">
</head>
<body>

<!--
  ESTRUCTURA GENERAL:

  <body>
    └── .xp-window          ← La ventana principal de Windows XP
          ├── header.php      ← Barra de título + menú
          ├── .xp-content     ← Contenido en dos columnas
          │     ├── sidebar.php ← Panel izquierdo: lista de notas
          │     └── form-nota.php ← Panel derecho: formulario
          ├── .xp-statusbar   ← Barra de estado inferior
          └── .xp-taskbar     ← Taskbar fija al fondo
-->


<!-- ============================================================
     VENTANA XP PRINCIPAL
     ============================================================ -->
<div class="xp-window" role="main" aria-label="Calendario Vintage">

    <!-- =========================================================
         PARTIAL: BARRA DE TÍTULO + MENÚ
         Bloque 4a — views/partials/header.php
         Recibe: $titulo_ventana
         ========================================================= -->
    <?php include 'views/partials/header.php'; ?>
    <!--
        include ejecuta el archivo PHP y su salida HTML
        queda insertada exactamente aquí.
        Es como copiar y pegar el contenido del partial.
    -->


    <!-- =========================================================
         CONTENIDO PRINCIPAL — dos columnas
         ========================================================= -->
    <div class="xp-content">

        <!-- =====================================================
             PARTIAL: SIDEBAR — lista de notas
             Bloque 4b — views/partials/sidebar.php
             Recibe: $notas (array cargado arriba)
             ===================================================== -->
        <?php include 'views/partials/sidebar.php'; ?>


        <!-- =====================================================
             PARTIAL: FORMULARIO DE NOTA
             Bloque 4c — views/partials/form-nota.php
             Recibe: $nota_activa (null = nueva nota)
             ===================================================== -->
        <?php include 'views/partials/form-nota.php'; ?>

    </div><!-- fin .xp-content -->


    <!-- =========================================================
         BARRA DE ESTADO
         ========================================================= -->
    <div class="xp-statusbar" role="status" aria-live="polite">
        <span class="xp-statusbar-panel" id="status-texto">
            <?php
                // Mostrar error de BD si ocurrió, si no mostrar cuenta de notas
                if (isset($errorBD)) {
                    echo htmlspecialchars($errorBD);
                } else {
                    // count() devuelve el número de elementos de un array
                    echo count($notas) . ' nota(s) registrada(s)';
                }
            ?>
        </span>
        <span class="xp-statusbar-panel">
            <!-- Fecha actual formateada en español -->
            <?php echo date('l, d \de F \de Y'); ?>
            <!--
                date() con barras invertidas: el \ escapa la letra
                para que no sea interpretada como formato de fecha.
                'd \de F' imprime ej: '15 de Abril'
            -->
        </span>
    </div><!-- fin .xp-statusbar -->

</div><!-- fin .xp-window -->


<!-- ============================================================
     TASKBAR — Barra de tareas fija al fondo
     ============================================================ -->
<div class="xp-taskbar" role="toolbar" aria-label="Barra de tareas">

    <!-- Botón Inicio -->
    <button class="xp-start-btn" aria-label="Menú Inicio">
        <!-- El logo de Windows XP (cuatro cuadros de colores) en SVG -->
        <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true">
            <rect x="0" y="0" width="7" height="7" fill="#e74c3c"/>
            <rect x="9" y="0" width="7" height="7" fill="#27ae60"/>
            <rect x="0" y="9" width="7" height="7" fill="#3498db"/>
            <rect x="9" y="9" width="7" height="7" fill="#f1c40f"/>
        </svg>
        inicio
    </button>

    <!-- Botón de la ventana activa en la taskbar -->
    <button class="xp-taskbar-window-btn" aria-label="Calendario Vintage" style="
        height:24px; padding:0 10px;
        background:linear-gradient(to bottom,#4d8fcc,#2458a6);
        border:none; color:#fff;
        font-family:var(--xp-font); font-size:var(--xp-font-size);
        box-shadow:var(--xp-bevel-out); cursor:default;
        display:flex; align-items:center; gap:4px;
    ">
        📅 Calendario Vintage
    </button>

    <!-- Reloj digital -->
    <div class="xp-clock" id="xp-clock" aria-label="Reloj">--:--</div>

</div><!-- fin .xp-taskbar -->


<!-- ============================================================
     GLOBO DE NOTIFICACIÓN (Balloon Tooltip)
     Oculto por defecto. JS lo muestra cuando hay recordatorio.
     ============================================================ -->
<div class="xp-balloon" id="xp-balloon" role="alert" aria-live="assertive">
    <button class="xp-balloon-close"
            aria-label="Cerrar notificación"
            onclick="this.parentElement.classList.remove('visible')">
        &times;
    </button>
    <div class="xp-balloon-title">
        <span aria-hidden="true">⏰</span>
        <span>Recordatorio</span>
    </div>
    <div class="xp-balloon-body">Tienes una tarea pendiente.</div>
</div>


<!-- ============================================================
     DIÁLOGO MODAL DE CONFIRMACIÓN
     Usado por xp-ui.js → confirmarXP() para eliminar notas.
     ============================================================ -->
<div class="xp-overlay" id="xp-overlay" role="dialog"
     aria-modal="true" aria-labelledby="dialog-titulo">

    <div class="xp-dialog" id="xp-dialog">

        <!-- Barra de título del diálogo -->
        <div class="xp-dialog-titlebar">
            <span class="xp-dialog-title-text" id="dialog-titulo">
                Confirmar acción
            </span>
            <button class="xp-btn-control xp-btn-close"
                    id="dialog-btn-no"
                    aria-label="Cancelar">
                &times;
            </button>
        </div>

        <!-- Cuerpo del diálogo -->
        <div class="xp-dialog-body">
            <div class="xp-dialog-icon" aria-hidden="true">⚠️</div>
            <div class="xp-dialog-message">
                Esta acción no se puede deshacer.
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="xp-dialog-footer">
            <button class="xp-toolbar-btn" id="dialog-btn-si"
                    style="min-width:75px; justify-content:center;">
                Sí
            </button>
            <button class="xp-toolbar-btn" id="dialog-btn-no"
                    style="min-width:75px; justify-content:center;">
                No
            </button>
        </div>

    </div><!-- fin .xp-dialog -->

</div><!-- fin .xp-overlay -->


<!-- ============================================================
     SCRIPTS JavaScript — al final del body
     ============================================================
     Los scripts van ANTES del cierre de </body>.
     Razón: el HTML ya fue parseado, el DOM existe.
     Si los pusiéramos en el <head>, getElementById() devolvería
     null porque el HTML aún no habría sido procesado.

     type="module" activa los ES6 Modules:
       - Permite usar import/export entre archivos JS
       - El script se ejecuta en modo estricto (strict mode)
       - Tiene su propio scope (no contamina el scope global)
       - Se carga de forma diferida automáticamente (como defer)
     ============================================================ -->
<script type="module" src="js/app.js"></script>
<!--
    app.js importa notificaciones.js y xp-ui.js internamente.
    Solo necesitamos incluir el archivo principal aquí.
    El navegador resuelve las dependencias de import automáticamente.
-->

</body>
</html>

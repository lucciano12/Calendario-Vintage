<?php
// ============================================================
// BLOQUE 4a — views/partials/header.php
// ============================================================
// ¿Qué hace este archivo?
//   Genera la BARRA DE TÍTULO y la BARRA DE MENÚ de la ventana.
//   Es un "partial" — un fragmento de HTML reutilizable.
//
// ¿Qué es un partial en PHP?
//   En vez de repetir el mismo HTML en cada página,
//   lo escribimos una vez aquí y lo incluimos con:
//     include 'views/partials/header.php';
//   Es el principio DRY: Don't Repeat Yourself.
//
// ¿Qué variables recibe este partial?
//   $titulo_ventana  → texto que aparece en la barra de título
//   Se define en index.php antes de hacer el include.
// ============================================================

// htmlspecialchars() convierte caracteres peligrosos a entidades HTML.
// Ejemplo: <script> se convierte en &lt;script&gt;
// Siempre úsalo al imprimir variables en HTML → previene XSS.
$titulo = htmlspecialchars($titulo_ventana ?? 'Calendario Vintage XP');
?>

<!-- ============================================================
     BARRA DE TÍTULO — .xp-titlebar
     El gradiente azul, icono, título y botones de ventana.
     Los estilos vienen de css/xp-style.css (.xp-titlebar)
     ============================================================ -->
<div class="xp-titlebar">

    <!-- Lado izquierdo: icono + texto del título -->
    <div class="xp-titlebar-left">
        <!-- El icono usa un emoji de calendario como imagen rápida -->
        <span class="xp-titlebar-icon" aria-hidden="true">📅</span>
        <span class="xp-titlebar-title">
            <?php echo $titulo; ?>
            <!-- <?php echo $var; ?> imprime el valor de la variable en HTML -->
        </span>
    </div>

    <!-- Lado derecho: botones Minimizar, Maximizar, Cerrar -->
    <div class="xp-window-controls" role="group" aria-label="Controles de ventana">

        <!-- Botón Minimizar — solo visual en esta versión -->
        <button class="xp-btn-control xp-btn-min"
                title="Minimizar"
                aria-label="Minimizar ventana"
                onclick="this.closest('.xp-window').style.display='none'">
            <!-- El guión bajo es el icono de minimizar en XP -->
            <span aria-hidden="true">&#8211;</span>
        </button>

        <!-- Botón Maximizar — solo visual -->
        <button class="xp-btn-control xp-btn-max"
                title="Maximizar"
                aria-label="Maximizar ventana">
            <span aria-hidden="true">&#9633;</span>
        </button>

        <!-- Botón Cerrar — rojo XP -->
        <button class="xp-btn-control xp-btn-close"
                title="Cerrar"
                aria-label="Cerrar ventana"
                id="btn-cerrar">
            <span aria-hidden="true">&times;</span>
        </button>

    </div>
</div><!-- fin .xp-titlebar -->


<!-- ============================================================
     BARRA DE MENÚ — .xp-menubar
     Menú clásico: Archivo | Ver | Ayuda
     ============================================================ -->
<nav class="xp-menubar" role="menubar" aria-label="Menú principal">

    <!-- Menú Archivo -->
    <div class="xp-menu-item" role="menuitem" tabindex="0"
         onclick="document.getElementById('btn-nueva-nota').click()">
        Archivo
    </div>

    <!-- Menú Ver -->
    <div class="xp-menu-item" role="menuitem" tabindex="0">
        Ver
    </div>

    <!-- Menú Ayuda -->
    <div class="xp-menu-item" role="menuitem" tabindex="0">
        Ayuda
    </div>

</nav><!-- fin .xp-menubar -->

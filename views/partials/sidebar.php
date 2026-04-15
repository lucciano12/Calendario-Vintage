<?php
// ============================================================
// BLOQUE 4b — views/partials/sidebar.php
// ============================================================
// ¿Qué hace este archivo?
//   Muestra el PANEL IZQUIERDO con la lista de notas.
//   Incluye buscador y el botón "Nueva Nota".
//
// ¿Qué variables recibe?
//   $notas → array de notas que viene de api/notas.php
//            Cada $nota tiene: id, titulo, fecha, hora, prioridad
//
// Conceptos PHP que aprenderás aquí:
//   - foreach: recorrer arrays
//   - echo: imprimir HTML dinámico
//   - Operador ternario: condición ? valor_si : valor_no
//   - htmlspecialchars(): protección XSS al imprimir datos
//   - empty(): verificar si un array está vacío
// ============================================================
?>

<!-- ============================================================
     SIDEBAR — .xp-sidebar
     Panel izquierdo con lista de notas y buscador.
     ============================================================ -->
<aside class="xp-sidebar" role="complementary" aria-label="Lista de notas">

    <!-- Encabezado del sidebar -->
    <div class="xp-sidebar-header">
        📁 Mis Notas
        <!-- El emoji es decorativo, aria-hidden lo oculta a lectores -->
    </div>

    <!-- Botón Nueva Nota -->
    <div style="padding: 6px;">
        <button class="xp-toolbar-btn"
                id="btn-nueva-nota"
                style="width:100%; justify-content:center;"
                aria-label="Crear nueva nota">
            &#43; Nueva Nota
            <!-- &#43; es el código HTML del signo + -->
        </button>
    </div>

    <!-- Buscador -->
    <div class="xp-sidebar-search">
        <input type="search"
               id="buscador"
               class="xp-input"
               placeholder="Buscar nota..."
               aria-label="Buscar notas"
               autocomplete="off">
        <!--
            type="search" agrega un boton X para limpiar en Chrome.
            autocomplete="off" evita que el navegador sugiera valores.
        -->
    </div>

    <!-- Lista de notas -->
    <ul class="xp-notes-list"
        id="lista-notas"
        role="listbox"
        aria-label="Lista de notas">

        <?php if (empty($notas)): ?>
            <!--
                empty() devuelve true si el array está vacío o no existe.
                La sintaxis if(): ... endif; es la forma "template" de PHP,
                más legible dentro del HTML que las llaves {}.
            -->
            <li class="xp-note-item" style="cursor:default; color:#888;">
                <span>No hay notas aún.</span>
                <span style="font-size:10px;">Haz clic en "Nueva Nota".</span>
            </li>

        <?php else: ?>
            <!--
                foreach recorre cada elemento del array $notas.
                $nota es la variable temporal que tiene los datos
                de cada fila: id, titulo, fecha, hora, prioridad.
            -->
            <?php foreach ($notas as $nota): ?>

                <li class="xp-note-item"
                    role="option"
                    data-id="<?php echo (int) $nota['id']; ?>"
                    aria-selected="false"
                    tabindex="0">
                    <!--
                        data-id es un atributo HTML5 personalizado.
                        JavaScript lo lee con: elemento.dataset.id
                        (int) convierte el ID a entero por seguridad.
                    -->

                    <!-- Título de la nota -->
                    <span class="xp-note-item-title">
                        <?php echo htmlspecialchars($nota['titulo']); ?>
                        <!--
                            SIEMPRE usa htmlspecialchars() al imprimir
                            datos del usuario en HTML.
                            Evita que un título como:
                            <script>alert('hack')</script>
                            se ejecute como código (ataque XSS).
                        -->
                    </span>

                    <!-- Fecha y hora -->
                    <span class="xp-note-item-date">
                        <?php
                            // date() formatea fechas en PHP
                            // 'd/m/Y' = día/mes/año (formato latinoamericano)
                            // strtotime() convierte texto a timestamp Unix
                            echo date('d/m/Y', strtotime($nota['fecha']))
                               . ' ' . substr($nota['hora'], 0, 5);
                            // substr(str, inicio, largo) extrae parte de un string
                            // '08:30:00' → '08:30'
                        ?>
                    </span>

                    <!-- Etiqueta de prioridad -->
                    <span class="xp-priority <?php echo htmlspecialchars($nota['prioridad']); ?>">
                        <?php
                            // Operador ternario: condición ? valor_si : valor_no
                            // Es una forma corta de if/else para una sola expresión
                            echo $nota['prioridad'] === 'alta'  ? '▲ Alta'  :
                                ($nota['prioridad'] === 'media' ? '■ Media' : '▼ Baja');
                        ?>
                    </span>

                </li>

            <?php endforeach; ?>
            <!-- endforeach; cierra el foreach en sintaxis template -->

        <?php endif; ?>

    </ul><!-- fin .xp-notes-list -->

</aside><!-- fin .xp-sidebar -->

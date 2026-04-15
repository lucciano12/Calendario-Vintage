<?php
// ============================================================
// BLOQUE 4c — views/partials/form-nota.php
// ============================================================
// ¿Qué hace este archivo?
//   Muestra el PANEL DERECHO con el formulario para
//   crear o editar una nota.
//
// ¿Qué variables recibe?
//   $nota_activa → array con datos de la nota seleccionada
//                  (null si es nota nueva)
//
// Conceptos PHP que aprenderás aquí:
//   - isset(): verificar si una variable existe y no es null
//   - Null coalescing operator ??  (PHP 7+)
//   - selected / checked en HTML con PHP
//   - Separación entre lógica y presentación
// ============================================================

// Null coalescing ??: si $nota_activa existe úsala, si no, array vacío
// Es más corto que: isset($nota_activa) ? $nota_activa : []
$nota = $nota_activa ?? [];

// Extraer valores o usar string vacío como valor por defecto
// Esto evita errores de "Undefined index" si la nota es nueva
$id          = (int) ($nota['id']          ?? 0);
$titulo      = htmlspecialchars($nota['titulo']      ?? '');
$descripcion = htmlspecialchars($nota['descripcion'] ?? '');
$fecha       = $nota['fecha'] ?? date('Y-m-d');  // date() sin parámetros = hoy
$hora        = isset($nota['hora']) ? substr($nota['hora'], 0, 5) : date('H:i');
$prioridad   = $nota['prioridad']  ?? 'media';
$categoria   = htmlspecialchars($nota['categoria']  ?? 'General');

// ¿Es una nota nueva o estamos editando?
$es_nueva = $id === 0;
$titulo_form = $es_nueva ? 'Nueva Nota' : 'Editar Nota';
?>

<!-- ============================================================
     PANEL PRINCIPAL — .xp-main-panel
     Toolbar de acciones + formulario de nota.
     ============================================================ -->
<section class="xp-main-panel">

    <!-- Toolbar de botones de acción -->
    <div class="xp-toolbar" role="toolbar" aria-label="Acciones">

        <!-- Botón Guardar -->
        <button class="xp-toolbar-btn"
                id="btn-guardar"
                data-id="<?php echo $id; ?>"
                aria-label="Guardar nota">
            💾 Guardar
        </button>

        <!-- Separador visual -->
        <div class="xp-toolbar-sep" role="separator"></div>

        <!-- Botón Eliminar (solo visible al editar) -->
        <?php if (!$es_nueva): ?>
            <!--
                !$es_nueva = si NO es nueva (o sea, estamos editando)
                El ! es el operador NOT en PHP y JavaScript.
            -->
            <button class="xp-toolbar-btn"
                    id="btn-eliminar"
                    data-id="<?php echo $id; ?>"
                    aria-label="Eliminar nota">
                🗑️ Eliminar
            </button>
            <div class="xp-toolbar-sep" role="separator"></div>
        <?php endif; ?>

        <!-- Botón Exportar -->
        <button class="xp-toolbar-btn"
                id="btn-exportar"
                aria-label="Exportar nota como texto">
            📄 Exportar .txt
        </button>

    </div><!-- fin .xp-toolbar -->


    <!-- Área del formulario con scroll -->
    <div class="xp-form-area">

        <!-- ================================================
             GROUPBOX 1: Información principal
             ================================================ -->
        <div class="xp-groupbox">
            <span class="xp-groupbox-title"><?php echo $titulo_form; ?></span>

            <div class="xp-form-grid">

                <!-- Campo: Título -->
                <label class="xp-label" for="campo-titulo">Título:</label>
                <input type="text"
                       id="campo-titulo"
                       name="titulo"
                       class="xp-input"
                       value="<?php echo $titulo; ?>"
                       placeholder="Escribe el título de la nota"
                       maxlength="150"
                       required
                       autocomplete="off">
                <!--
                    value="<?php echo $titulo; ?>" rellena el campo
                    con el dato existente si es una edición.
                    maxlength="150" coincide con VARCHAR(150) en la BD.
                -->

                <!-- Campo: Categoría -->
                <label class="xp-label" for="campo-categoria">Categoría:</label>
                <input type="text"
                       id="campo-categoria"
                       name="categoria"
                       class="xp-input"
                       value="<?php echo $categoria; ?>"
                       placeholder="Ej: Estudio, Trabajo, Personal"
                       maxlength="80">

            </div>
        </div><!-- fin groupbox 1 -->


        <!-- ================================================
             GROUPBOX 2: Fecha y hora del recordatorio
             ================================================ -->
        <div class="xp-groupbox">
            <span class="xp-groupbox-title">Recordatorio</span>

            <div class="xp-form-grid">

                <!-- Campo: Fecha -->
                <label class="xp-label" for="campo-fecha">Fecha:</label>
                <input type="date"
                       id="campo-fecha"
                       name="fecha"
                       class="xp-input"
                       value="<?php echo $fecha; ?>"
                       required>
                <!--
                    type="date" muestra un selector de fecha nativo
                    del navegador. El valor debe estar en formato Y-m-d
                    (2026-04-17) que es como lo guarda MySQL.
                -->

                <!-- Campo: Hora -->
                <label class="xp-label" for="campo-hora">Hora:</label>
                <input type="time"
                       id="campo-hora"
                       name="hora"
                       class="xp-input"
                       value="<?php echo $hora; ?>"
                       required>

                <!-- Campo: Prioridad -->
                <label class="xp-label" for="campo-prioridad">Prioridad:</label>
                <select id="campo-prioridad"
                        name="prioridad"
                        class="xp-select">
                    <!--
                        selected es el atributo HTML que marca la opción
                        activa en un <select>.
                        PHP imprime 'selected' solo si la prioridad coincide.
                        Esto es el patrón de "valor seleccionado dinámico".
                    -->
                    <option value="baja"  <?php echo $prioridad === 'baja'  ? 'selected' : ''; ?>>Baja</option>
                    <option value="media" <?php echo $prioridad === 'media' ? 'selected' : ''; ?>>Media</option>
                    <option value="alta"  <?php echo $prioridad === 'alta'  ? 'selected' : ''; ?>>Alta</option>
                </select>

            </div>
        </div><!-- fin groupbox 2 -->


        <!-- ================================================
             GROUPBOX 3: Descripción
             ================================================ -->
        <div class="xp-groupbox">
            <span class="xp-groupbox-title">Descripción</span>

            <textarea id="campo-descripcion"
                      name="descripcion"
                      class="xp-textarea"
                      placeholder="Describe la tarea o actividad..."
                      rows="5"
                      aria-label="Descripción de la nota"><?php echo $descripcion; ?></textarea>
            <!--
                En <textarea> el valor NO va en value="",
                va entre las etiquetas de apertura y cierre.
                <?php echo $descripcion; ?> rellena el texto existente.
            -->
        </div><!-- fin groupbox 3 -->


        <!-- Campo oculto con el ID de la nota (para edición) -->
        <input type="hidden" id="nota-id" value="<?php echo $id; ?>">
        <!--
            type="hidden" no es visible en pantalla pero su valor
            sí se puede leer con JavaScript: campo.value
            Lo usamos para saber qué nota estamos editando.
        -->

    </div><!-- fin .xp-form-area -->

</section><!-- fin .xp-main-panel -->

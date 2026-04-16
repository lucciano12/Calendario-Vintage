<?php

// ============================================================
// BLOQUE 1 — config/database.php
// ============================================================
// ¿Qué hace este archivo?
//   Establece la CONEXIÓN entre PHP y la base de datos.
//   Todos los demás archivos PHP van a "pedir prestada"
//   esta conexión para guardar y leer notas.
//
// ¿Qué es PDO?
//   PDO = PHP Data Objects
//   Es la forma MODERNA y SEGURA de hablar con bases de datos.
//   Funciona con MySQL, PostgreSQL, SQLite — mismo código.
//
// ¿Por qué no usamos mysqli_ o mysql_?
//   - mysql_*  → OBSOLETO desde PHP 5.5, eliminado en PHP 7
//   - mysqli_  → Solo funciona con MySQL
//   - PDO      → Universal, seguro, con consultas preparadas ✅
// ============================================================


// ------------------------------------------------------------
// 1. CONFIGURACIÓN DE LA BASE DE DATOS
// ------------------------------------------------------------
// En producción (sistemas reales de salud) estos valores
// se guardan en variables de entorno (.env), NUNCA en el código.
// Para aprender, los ponemos aquí directamente.
// ------------------------------------------------------------

define('DB_HOST',    'localhost');      // Servidor de base de datos
define('DB_NAME',    'calendario_xp'); // Nombre de la base de datos
define('DB_USER',    'root');           // Usuario (en XAMPP es 'root')
define('DB_PASS',    '');              // Contraseña (en XAMPP vacía)
define('DB_PORT',    '3306');           // Puerto MySQL por defecto
define('DB_CHARSET', 'utf8mb4');        // Charset: soporta emojis y tildes


// ------------------------------------------------------------
// 2. LA FUNCIÓN DE CONEXIÓN
// ------------------------------------------------------------
function conectarDB(): PDO
{
    $dsn = "mysql:host=" . DB_HOST
         . ";port=" . DB_PORT
         . ";dbname=" . DB_NAME
         . ";charset=" . DB_CHARSET;

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $opciones);
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode([
            'error'   => true,
            'mensaje' => 'Error de conexión a la base de datos',
            'detalle' => $e->getMessage()
        ]));
    }
}


// ============================================================
// 3. SCRIPT SQL — Crear la base de datos y la tabla
// ============================================================
// Ejecuta este SQL en phpMyAdmin (pestaña SQL) o en la consola
// de MySQL para crear toda la estructura desde cero.
// ============================================================

/*

-- ─────────────────────────────────────────────────────
-- INSTALACIÓN COMPLETA (base de datos nueva)
-- ─────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS calendario_xp
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE calendario_xp;

CREATE TABLE IF NOT EXISTS notas (
    id                    INT AUTO_INCREMENT PRIMARY KEY,

    -- Contenido
    titulo                VARCHAR(150)               NOT NULL,
    descripcion           TEXT,
    fecha                 DATE                       NOT NULL,
    hora                  TIME                       NOT NULL,
    prioridad             ENUM('baja','media','alta') DEFAULT 'media',
    categoria             VARCHAR(80)                DEFAULT 'General',

    -- Recordatorio
    -- recordatorio_activo  : 1 = el usuario quiere recibir aviso
    -- recordatorio_enviado : 1 = el aviso ya fue disparado (no repetir)
    -- notificado           : campo legado, mantenido por compatibilidad
    recordatorio_activo   TINYINT(1) DEFAULT 0,
    recordatorio_enviado  TINYINT(1) DEFAULT 0,
    notificado            TINYINT(1) DEFAULT 0,

    -- Auditoría
    creado_en             DATETIME   DEFAULT CURRENT_TIMESTAMP,
    actualizado           DATETIME   DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP
);


-- ─────────────────────────────────────────────────────
-- MIGRACIÓN (si ya tenías la tabla creada anteriormente)
-- Ejecuta SOLO si la tabla ya existe y le faltan las columnas.
-- Los IF NOT EXISTS evitan error si ya están agregadas.
-- ─────────────────────────────────────────────────────

USE calendario_xp;

-- Agregar columna: el usuario activó el recordatorio (checkbox)
ALTER TABLE notas
    ADD COLUMN IF NOT EXISTS recordatorio_activo  TINYINT(1) DEFAULT 0
    AFTER categoria;

-- Agregar columna: el aviso ya fue disparado (no volver a enviar)
ALTER TABLE notas
    ADD COLUMN IF NOT EXISTS recordatorio_enviado TINYINT(1) DEFAULT 0
    AFTER recordatorio_activo;

-- Si quieres inicializar los recordatorios activos a partir
-- del campo legado 'notificado' que ya existía:
-- UPDATE notas SET recordatorio_activo = 1 WHERE notificado = 0 AND fecha IS NOT NULL;

*/


// ------------------------------------------------------------
// 4. DIFERENCIA ENTRE LOS TRES CAMPOS DE RECORDATORIO
// ------------------------------------------------------------
// Campo                  | Significado
// -----------------------+----------------------------------------------
// recordatorio_activo    | El usuario marcó el checkbox "Activar aviso"
//                        | 0 = no quiere aviso, 1 = sí quiere aviso
// -----------------------+----------------------------------------------
// recordatorio_enviado   | El aviso YA fue disparado (JS + Notification API)
//                        | api/recordatorios.php lo marca con POST
//                        | Evita que se repita el aviso si se recarga la página
// -----------------------+----------------------------------------------
// notificado             | Campo legado del Bloque 1.
//                        | Mismo concepto que recordatorio_enviado.
//                        | Mantenido para compatibilidad hacia atrás.
// ------------------------------------------------------------


// ------------------------------------------------------------
// 5. EJEMPLO DE USO
// ------------------------------------------------------------
// En api/notas.php y api/recordatorios.php:
//
//   require_once '../config/database.php';
//   $pdo = conectarDB();
//
//   // Consulta preparada — evita SQL Injection
//   $stmt = $pdo->prepare(
//       "SELECT * FROM notas
//        WHERE recordatorio_activo = 1
//          AND recordatorio_enviado = 0
//          AND CONCAT(fecha,' ',hora) <= NOW()"
//   );
//   $stmt->execute();
//   $pendientes = $stmt->fetchAll();
//
// ¿Qué es SQL Injection? (importante para la entrevista)
//   Si un usuario escribe en un campo: ' OR '1'='1
//   Una consulta sin protección lo ejecutaría como SQL real.
//   Las consultas preparadas de PDO evitan eso automáticamente.
// ------------------------------------------------------------

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

define('DB_HOST', 'localhost');     // Servidor de base de datos
define('DB_NAME', 'calendario_xp'); // Nombre de la base de datos
define('DB_USER', 'root');          // Usuario (en XAMPP es 'root')
define('DB_PASS', '');              // Contraseña (en XAMPP vacía)
define('DB_PORT', '3306');          // Puerto MySQL por defecto
define('DB_CHARSET', 'utf8mb4');    // Charset: soporta emojis y tildes


// ------------------------------------------------------------
// 2. LA FUNCIÓN DE CONEXIÓN
// ------------------------------------------------------------
// Creamos una función que devuelve la conexión PDO.
// Así cualquier archivo puede llamar: $pdo = conectarDB();
// ------------------------------------------------------------

function conectarDB(): PDO
{
    // El DSN (Data Source Name) le dice a PDO:
    //   - qué motor usar (mysql)
    //   - dónde está el servidor (host)
    //   - qué base de datos abrir (dbname)
    //   - qué charset usar (charset)
    $dsn = "mysql:host=" . DB_HOST
         . ";port=" . DB_PORT
         . ";dbname=" . DB_NAME
         . ";charset=" . DB_CHARSET;

    // Opciones de comportamiento de PDO
    $opciones = [
        // Si hay error, lanza una excepción (try/catch puede capturarla)
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

        // Los resultados vienen como arrays asociativos: $fila['titulo']
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Desactiva emulación de consultas preparadas → más seguro
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // try/catch: intentamos conectar; si falla, capturamos el error
    try {
        // Crear la conexión PDO con los parámetros definidos
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);

        // Si llegamos aquí, la conexión fue exitosa ✅
        return $pdo;

    } catch (PDOException $e) {
        // ❌ Algo salió mal (BD apagada, credenciales incorrectas, etc.)
        // En producción NUNCA muestres el error real al usuario
        // (podría revelar información sensible del servidor)

        // Para desarrollo: mostramos el error para depurar
        http_response_code(500);
        die(json_encode([
            'error'   => true,
            'mensaje' => 'Error de conexión a la base de datos',
            'detalle' => $e->getMessage() // ← quitar en producción
        ]));
    }
}


// ------------------------------------------------------------
// 3. SCRIPT SQL — Crear la tabla de notas
// ------------------------------------------------------------
// Copia este SQL y ejecútalo en phpMyAdmin para crear la BD.
//
// CREATE DATABASE IF NOT EXISTS calendario_xp
//   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
//
// USE calendario_xp;
//
// CREATE TABLE IF NOT EXISTS notas (
//     id          INT AUTO_INCREMENT PRIMARY KEY,
//     titulo      VARCHAR(150)  NOT NULL,
//     descripcion TEXT,
//     fecha       DATE          NOT NULL,
//     hora        TIME          NOT NULL,
//     prioridad   ENUM('baja','media','alta') DEFAULT 'media',
//     categoria   VARCHAR(80)   DEFAULT 'General',
//     notificado  TINYINT(1)    DEFAULT 0,
//     creado_en   DATETIME      DEFAULT CURRENT_TIMESTAMP,
//     actualizado DATETIME      DEFAULT CURRENT_TIMESTAMP
//                               ON UPDATE CURRENT_TIMESTAMP
// );
// ------------------------------------------------------------


// ------------------------------------------------------------
// 4. EJEMPLO DE USO
// ------------------------------------------------------------
// En api/notas.php haremos esto:
//
//   require_once '../config/database.php';  // incluir este archivo
//   $pdo = conectarDB();                    // obtener la conexión
//
//   // Consulta preparada — así se evita SQL Injection
//   $stmt = $pdo->prepare("SELECT * FROM notas ORDER BY fecha ASC");
//   $stmt->execute();
//   $notas = $stmt->fetchAll(); // array con todas las notas
//
// ¿Qué es SQL Injection? (importante para la entrevista)
//   Si un usuario escribe en un campo: ' OR '1'='1
//   Una consulta sin protección lo ejecutaría como SQL real.
//   Las consultas preparadas de PDO evitan eso automáticamente.
// ------------------------------------------------------------

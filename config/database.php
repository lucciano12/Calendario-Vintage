<?php

/**
 * ============================================================
 * BLOQUE 1 — config/database.php
 * ============================================================
 * ¿QUÉ ES ESTE ARCHIVO?
 * Es el único lugar de toda la app donde se configura
 * la conexión a la base de datos. Todos los demás archivos
 * PHP lo incluyen con:
 *   require_once __DIR__ . '/../config/database.php';
 *
 * ¿POR QUÉ PDO Y NO mysqli?
 * PDO (PHP Data Objects) es una capa de abstracción que:
 *  1. Funciona con MySQL, PostgreSQL, SQLite — mismo código
 *  2. Usa "consultas preparadas" que evitan SQL Injection
 *  3. Es el estándar moderno en PHP 7+ y PHP 8+
 * ============================================================
 */

// ── 1. CONFIGURACIÓN ────────────────────────────────────────
// En producción real estos valores vendrían de variables
// de entorno (.env) — nunca se suben al repositorio.
// Por ahora los dejamos aquí para estudiar la estructura.

define('DB_HOST', 'localhost');          // Servidor de base de datos
define('DB_PORT', '5432');               // Puerto PostgreSQL (MySQL usa 3306)
define('DB_NAME', 'calendario_vintage'); // Nombre de la base de datos
define('DB_USER', 'postgres');           // Usuario de la BD
define('DB_PASS', 'tu_password');        // Contraseña (cambiar en tu entorno local)

// ── 2. FUNCIÓN DE CONEXIÓN ───────────────────────────────────
/**
 * getDB() — Retorna una conexión PDO lista para usar.
 *
 * PATRÓN SINGLETON:
 * Usamos una variable estática ($pdo) para que no se creen
 * múltiples conexiones en una misma petición HTTP.
 * La primera llamada crea la conexión, las siguientes
 * reutilizan la misma instancia.
 *
 * @return PDO  Objeto de conexión a la base de datos
 * @throws PDOException  Si la conexión falla
 */
function getDB(): PDO
{
    // 'static' recuerda el valor entre llamadas a esta función
    static $pdo = null;

    // Si ya existe una conexión activa, la reutilizamos
    if ($pdo !== null) {
        return $pdo;
    }

    // ── 3. DSN (Data Source Name) ────────────────────────────
    // Es la "dirección" de la base de datos.
    // Formato PostgreSQL: pgsql:host=...;port=...;dbname=...
    // Formato MySQL:      mysql:host=...;port=...;dbname=...;charset=utf8
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    // ── 4. OPCIONES DE PDO ───────────────────────────────────
    // Configuramos el comportamiento de PDO (Significa PHP Data Objects):
    $opciones = [
        // Lanza excepciones en lugar de errores silenciosos
        // SIEMPRE activar esto — sin esto los errores pasan desapercibidos
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

        // fetch() devuelve arrays asociativos por defecto
        // Ejemplo: $fila['titulo'] en vez de $fila[0]
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Desactiva emulación de consultas preparadas
        // Hace que las consultas preparadas sean REALMENTE seguras
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // ── 5. CREAR LA CONEXIÓN ─────────────────────────────────
    // try/catch atrapa errores (BD apagada, credenciales
    // incorrectas, servidor caído, etc.)
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        return $pdo;
    } catch (PDOException $e) {
        // ⚠️  IMPORTANTE: En producción NUNCA mostrar el mensaje
        // real del error — revela datos internos del servidor.
        // En desarrollo sí lo mostramos para depurar.
        $esDesarrollo = (DB_HOST === 'localhost');

        http_response_code(500);
        header('Content-Type: application/json');

        if ($esDesarrollo) {
            die(json_encode([
                'error'   => 'Error de conexión a la base de datos',
                'detalle' => $e->getMessage(), // Solo visible en localhost
            ]));
        } else {
            die(json_encode([
                'error' => 'Error interno del servidor'
            ]));
        }
    }
}

// ── 6. FUNCIÓN DE TEST ───────────────────────────────────────
/**
 * testConnection() — Verifica si la conexión funciona.
 * Útil para diagnosticar problemas en el servidor local.
 *
 * CÓMO USARLO:
 * Abre en el navegador: http://localhost/config/database.php?test=1
 * Deberías ver: { "estado": "✅ Conexión exitosa", ... }
 *
 * ⚠️  ELIMINAR o proteger en producción.
 */
function testConnection(): void
{
    try {
        $pdo       = getDB();
        $stmt      = $pdo->query('SELECT NOW() AS fecha_servidor');
        $resultado = $stmt->fetch();

        echo json_encode([
            'estado'         => '✅ Conexión exitosa',
            'motor'          => 'PostgreSQL',
            'base_de_datos'  => DB_NAME,
            'fecha_servidor' => $resultado['fecha_servidor'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode([
            'estado' => '❌ Conexión fallida',
            'error'  => $e->getMessage()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

// Ejecuta el test SOLO si:
// 1. Se llama este archivo directamente (no incluido)
// 2. Y se pasa el parámetro ?test=1 en la URL
if (
    basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])
    && isset($_GET['test'])
) {
    header('Content-Type: application/json');
    testConnection();
}

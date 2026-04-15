<?php

// ============================================================
// BLOQUE 2 — api/notas.php
// ============================================================
// ¿Qué hace este archivo?
//   Es el CEREBRO del backend. Recibe peticiones HTTP desde
//   el navegador (via fetch/AJAX) y responde con JSON.
//
// ¿Qué es una REST API?
//   Es un conjunto de "direcciones" (endpoints) que el frontend
//   puede llamar para hacer operaciones sobre los datos.
//   Usa los métodos HTTP como verbos:
//
//   GET    /api/notas.php        → Listar todas las notas
//   GET    /api/notas.php?id=5   → Ver una nota específica
//   POST   /api/notas.php        → Crear una nota nueva
//   PUT    /api/notas.php?id=5   → Editar una nota existente
//   DELETE /api/notas.php?id=5   → Eliminar una nota
//
// Esto se llama CRUD:
//   C = Create  (POST)
//   R = Read    (GET)
//   U = Update  (PUT)
//   D = Delete  (DELETE)
// ============================================================


// ------------------------------------------------------------
// 0. CABECERAS HTTP
// ------------------------------------------------------------
// Le decimos al navegador que siempre responderemos JSON
// y que aceptamos peticiones desde cualquier origen (CORS).
// En producción, Access-Control-Allow-Origin debe ser
// el dominio específico, no '*'.
// ------------------------------------------------------------

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Cuando el navegador hace una petición PUT o DELETE,
// primero envía un 'preflight' OPTIONS para preguntar si puede.
// Le respondemos OK y terminamos.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// ------------------------------------------------------------
// 1. INCLUIR LA CONEXIÓN (Bloque 1)
// ------------------------------------------------------------
// require_once = incluir el archivo UNA sola vez.
// Si ya fue incluido antes, PHP no lo vuelve a cargar.
// '../' sube un nivel de carpeta (de /api a /)
// ------------------------------------------------------------

require_once '../config/database.php';
$pdo = conectarDB();


// ------------------------------------------------------------
// 2. LEER EL MÉTODO HTTP Y EL CUERPO DE LA PETICIÓN
// ------------------------------------------------------------
// $_SERVER['REQUEST_METHOD'] contiene el verbo HTTP usado:
// 'GET', 'POST', 'PUT', 'DELETE'
//
// Para POST/PUT los datos vienen en el body como JSON.
// file_get_contents('php://input') los lee como texto.
// json_decode(..., true) los convierte a array PHP.
// ------------------------------------------------------------

$metodo = $_SERVER['REQUEST_METHOD'];        // ¿Qué verbo HTTP?
$id     = $_GET['id'] ?? null;               // ¿Viene ?id=5 en la URL?
$body   = json_decode(                       // Leer body JSON
    file_get_contents('php://input'),
    true  // true = devolver array, false = devolver objeto
);


// ------------------------------------------------------------
// 3. ENRUTADOR — decide qué función ejecutar
// ------------------------------------------------------------
// Según el método HTTP, llamamos a la función correcta.
// match() es como switch() pero más moderno (PHP 8+).
// ------------------------------------------------------------

match($metodo) {
    'GET'    => $id ? obtenerNota($pdo, $id) : listarNotas($pdo),
    'POST'   => crearNota($pdo, $body),
    'PUT'    => editarNota($pdo, $id, $body),
    'DELETE' => eliminarNota($pdo, $id),
    default  => responder(405, ['error' => 'Método no permitido'])
};


// ============================================================
// FUNCIONES — Una por cada operación CRUD
// ============================================================


// ------------------------------------------------------------
// READ — Listar TODAS las notas
// Llamada: GET /api/notas.php
// ------------------------------------------------------------
function listarNotas(PDO $pdo): void
{
    // prepare() crea una consulta segura (evita SQL Injection)
    // No necesita parámetros porque trae todo.
    $stmt = $pdo->prepare(
        "SELECT * FROM notas ORDER BY fecha ASC, hora ASC"
    );
    $stmt->execute();

    // fetchAll() devuelve un array con TODAS las filas
    $notas = $stmt->fetchAll();

    // Respondemos 200 OK con el array de notas en JSON
    responder(200, $notas);
}


// ------------------------------------------------------------
// READ — Obtener UNA nota por ID
// Llamada: GET /api/notas.php?id=5
// ------------------------------------------------------------
function obtenerNota(PDO $pdo, int $id): void
{
    // :id es un placeholder — PDO lo reemplaza de forma segura
    $stmt = $pdo->prepare("SELECT * FROM notas WHERE id = :id");

    // execute() reemplaza :id con el valor real
    $stmt->execute([':id' => $id]);

    // fetch() devuelve UNA sola fila (o false si no existe)
    $nota = $stmt->fetch();

    if (!$nota) {
        responder(404, ['error' => 'Nota no encontrada']);
        return;
    }

    responder(200, $nota);
}


// ------------------------------------------------------------
// CREATE — Crear una nota nueva
// Llamada: POST /api/notas.php
// Body JSON: { titulo, descripcion, fecha, hora, prioridad, categoria }
// ------------------------------------------------------------
function crearNota(PDO $pdo, ?array $body): void
{
    // Validar que vengan los campos obligatorios
    if (empty($body['titulo']) || empty($body['fecha']) || empty($body['hora'])) {
        responder(400, ['error' => 'titulo, fecha y hora son obligatorios']);
        return;
    }

    // Sanitizar: trim() quita espacios extra, htmlspecialchars() evita XSS
    // XSS = Cross-Site Scripting, otro ataque común en sistemas web
    $titulo      = htmlspecialchars(trim($body['titulo']));
    $descripcion = htmlspecialchars(trim($body['descripcion'] ?? ''));
    $fecha       = $body['fecha'];                          // formato: YYYY-MM-DD
    $hora        = $body['hora'];                           // formato: HH:MM
    $prioridad   = $body['prioridad']   ?? 'media';         // baja | media | alta
    $categoria   = $body['categoria']   ?? 'General';

    $stmt = $pdo->prepare("
        INSERT INTO notas (titulo, descripcion, fecha, hora, prioridad, categoria)
        VALUES (:titulo, :descripcion, :fecha, :hora, :prioridad, :categoria)
    ");

    $stmt->execute([
        ':titulo'      => $titulo,
        ':descripcion' => $descripcion,
        ':fecha'       => $fecha,
        ':hora'        => $hora,
        ':prioridad'   => $prioridad,
        ':categoria'   => $categoria,
    ]);

    // lastInsertId() devuelve el ID auto-generado por la BD
    $nuevoId = $pdo->lastInsertId();

    responder(201, [
        'ok'      => true,
        'id'      => $nuevoId,
        'mensaje' => 'Nota creada correctamente'
    ]);
}


// ------------------------------------------------------------
// UPDATE — Editar una nota existente
// Llamada: PUT /api/notas.php?id=5
// Body JSON: campos a actualizar
// ------------------------------------------------------------
function editarNota(PDO $pdo, ?int $id, ?array $body): void
{
    if (!$id) {
        responder(400, ['error' => 'Se requiere el parámetro id']);
        return;
    }

    // Verificar que la nota existe antes de editar
    $check = $pdo->prepare("SELECT id FROM notas WHERE id = :id");
    $check->execute([':id' => $id]);
    if (!$check->fetch()) {
        responder(404, ['error' => 'Nota no encontrada']);
        return;
    }

    $titulo      = htmlspecialchars(trim($body['titulo']      ?? ''));
    $descripcion = htmlspecialchars(trim($body['descripcion'] ?? ''));
    $fecha       = $body['fecha']      ?? null;
    $hora        = $body['hora']       ?? null;
    $prioridad   = $body['prioridad']  ?? 'media';
    $categoria   = $body['categoria']  ?? 'General';

    $stmt = $pdo->prepare("
        UPDATE notas
        SET titulo      = :titulo,
            descripcion = :descripcion,
            fecha       = :fecha,
            hora        = :hora,
            prioridad   = :prioridad,
            categoria   = :categoria
        WHERE id = :id
    ");

    $stmt->execute([
        ':titulo'      => $titulo,
        ':descripcion' => $descripcion,
        ':fecha'       => $fecha,
        ':hora'        => $hora,
        ':prioridad'   => $prioridad,
        ':categoria'   => $categoria,
        ':id'          => $id,
    ]);

    responder(200, ['ok' => true, 'mensaje' => 'Nota actualizada correctamente']);
}


// ------------------------------------------------------------
// DELETE — Eliminar una nota
// Llamada: DELETE /api/notas.php?id=5
// ------------------------------------------------------------
function eliminarNota(PDO $pdo, ?int $id): void
{
    if (!$id) {
        responder(400, ['error' => 'Se requiere el parámetro id']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM notas WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // rowCount() dice cuántas filas fueron afectadas
    if ($stmt->rowCount() === 0) {
        responder(404, ['error' => 'Nota no encontrada']);
        return;
    }

    responder(200, ['ok' => true, 'mensaje' => 'Nota eliminada correctamente']);
}


// ============================================================
// FUNCIÓN AUXILIAR — responder()
// ============================================================
// Centraliza todas las respuestas JSON del API.
// Así siempre tienen el mismo formato.
// $codigo = código HTTP (200, 201, 400, 404, 405...)
// $datos  = array PHP que se convierte a JSON
// ============================================================

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo); // Establecer código HTTP
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    // JSON_UNESCAPED_UNICODE → las tildes y ñ se muestran tal cual
    // JSON_PRETTY_PRINT     → JSON indentado (legible en desarrollo)
    exit;
}

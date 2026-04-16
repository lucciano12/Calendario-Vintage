<?php
// ============================================================
// api/recordatorios.php
// Gestión de recordatorios pendientes
//
// GET    /api/recordatorios.php          → lista recordatorios pendientes
// GET    /api/recordatorios.php?id=N     → un recordatorio específico
// POST   /api/recordatorios.php          → marcar recordatorio como enviado
// DELETE /api/recordatorios.php?id=N     → eliminar recordatorio
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(['error' => 'No se pudo conectar a la base de datos']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    // ──────────────────────────────────────────────────────────
    // GET — Listar recordatorios pendientes (fecha/hora <= ahora + 15 min)
    //       o traer uno específico por ID
    // ──────────────────────────────────────────────────────────
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare(
                "SELECT n.id, n.titulo, n.descripcion, n.fecha, n.hora,
                        n.prioridad, n.categoria, n.recordatorio_activo,
                        n.recordatorio_enviado
                 FROM notas n
                 WHERE n.id = :id"
            );
            $stmt->execute([':id' => $id]);
            $nota = $stmt->fetch();

            if (!$nota) {
                http_response_code(404);
                echo json_encode(['error' => 'Recordatorio no encontrado']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $nota]);
        } else {
            // Traer todos los recordatorios activos que aún no se enviaron
            // y cuya fecha+hora ya llegó (o está dentro de los próximos 15 min)
            $stmt = $pdo->prepare(
                "SELECT n.id, n.titulo, n.descripcion, n.fecha, n.hora,
                        n.prioridad, n.categoria
                 FROM notas n
                 WHERE n.recordatorio_activo  = 1
                   AND n.recordatorio_enviado = 0
                   AND CONCAT(n.fecha, ' ', COALESCE(n.hora, '00:00:00'))
                       <= DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                 ORDER BY n.fecha ASC, n.hora ASC"
            );
            $stmt->execute();
            $pendientes = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'total'   => count($pendientes),
                'data'    => $pendientes,
            ]);
        }
        break;

    // ──────────────────────────────────────────────────────────
    // POST — Marcar recordatorio como enviado
    // Body JSON: { "id": 5 }
    // ──────────────────────────────────────────────────────────
    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true);
        $notaId = isset($body['id']) ? (int)$body['id'] : null;

        if (!$notaId) {
            http_response_code(400);
            echo json_encode(['error' => 'Se requiere el campo "id"']);
            exit;
        }

        $stmt = $pdo->prepare(
            "UPDATE notas
             SET recordatorio_enviado = 1
             WHERE id = :id"
        );
        $stmt->execute([':id' => $notaId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Nota no encontrada o ya marcada']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Recordatorio marcado como enviado',
            'id'      => $notaId,
        ]);
        break;

    // ──────────────────────────────────────────────────────────
    // DELETE — Desactivar/eliminar el recordatorio de una nota
    // ?id=N requerido
    // ──────────────────────────────────────────────────────────
    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Se requiere el parámetro "id"']);
            exit;
        }

        $stmt = $pdo->prepare(
            "UPDATE notas
             SET recordatorio_activo  = 0,
                 recordatorio_enviado = 0
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Nota no encontrada']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Recordatorio desactivado',
            'id'      => $id,
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

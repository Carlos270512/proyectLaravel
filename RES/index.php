<?php
// ...existing code...
// Endpoint REST simple que usa ProductoDAO y devuelve JSON.
// Ajusta permisos/DB si es necesario.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Helper para devolver JSON y terminar.
 */
function send_json($data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ruta esperada del DAO
$daoFile = __DIR__ . '/../DAO/productosDAO.php';

// chequeos básicos para evitar el error "Class ... not found"
if (!file_exists($daoFile)) {
    send_json(['error' => 'Archivo DAO no encontrado', 'path' => $daoFile], 500);
}

// incluir archivo y verificar existencia de la clase
require_once $daoFile;

if (!class_exists('ProductoDAO')) {
    send_json(['error' => 'Clase ProductoDAO no existe en el archivo DAO'], 500);
}

$dao = new ProductoDAO();
// ...existing code...
try {
    $method = $_SERVER['REQUEST_METHOD'];
    // body (si aplica)
    $body = json_decode(file_get_contents('php://input'), true) ?: [];

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $row = $dao->getById($id);
            if ($row === null) {
                send_json(['error' => 'No encontrado'], 404);
            }
            send_json($row, 200);
        } else {
            $rows = $dao->getAll();
            send_json($rows, 200);
        }
    }

    if ($method === 'POST') {
        $nombre = $body['nombre_producto'] ?? $body['nombre'] ?? null;
        $precio = isset($body['precio']) ? (float)$body['precio'] : null;

        if (!$nombre || $precio === null) {
            send_json(['error' => 'Faltan campos: nombre_producto y precio'], 400);
        }

        $newId = $dao->create($nombre, $precio);
        $created = $dao->getById($newId);
        send_json(['message' => 'Creado', 'id' => $newId, 'data' => $created], 201);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        // id puede venir por query string o en el body
        $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($body['id']) ? (int)$body['id'] : null);
        if (!$id) {
            send_json(['error' => 'Falta id para actualizar'], 400);
        }
        $nombre = $body['nombre_producto'] ?? $body['nombre'] ?? null;
        $precio = isset($body['precio']) ? (float)$body['precio'] : null;
        if ($nombre === null && $precio === null) {
            send_json(['error' => 'Nada para actualizar (nombre_producto o precio)'], 400);
        }

        // obtener actual para completar campos no enviados
        $current = $dao->getById($id);
        if ($current === null) send_json(['error' => 'No encontrado'], 404);

        $nombre = $nombre ?? $current['nombre_producto'];
        $precio = $precio ?? (float)$current['precio'];

        $ok = $dao->update($id, $nombre, $precio);
        if ($ok) {
            $updated = $dao->getById($id);
            send_json(['message' => 'Actualizado', 'data' => $updated], 200);
        } else {
            send_json(['error' => 'No se pudo actualizar'], 500);
        }
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($body['id']) ? (int)$body['id'] : null);
        if (!$id) {
            send_json(['error' => 'Falta id para eliminar'], 400);
        }
        $exists = $dao->getById($id);
        if ($exists === null) send_json(['error' => 'No encontrado'], 404);

        $ok = $dao->delete($id);
        if ($ok) {
            send_json(['message' => 'Eliminado', 'id' => $id], 200);
        } else {
            send_json(['error' => 'No se pudo eliminar'], 500);
        }
    }

    // Método no soportado
    send_json(['error' => 'Método no soportado'], 405);
} catch (Exception $e) {
    send_json(['error' => 'Error del servidor', 'detalle' => $e->getMessage()], 500);
}
// ...existing code...
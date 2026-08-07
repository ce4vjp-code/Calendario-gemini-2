<?php
// api/delete_evaluacion.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener el ID enviado en la petición JSON
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de evaluación no proporcionado']);
    exit;
}

try {
    // Verificar propiedad (usando usuario_id) o rol admin
    $stmt = $pdo->prepare("SELECT usuario_id, profesor FROM evaluaciones WHERE id = ?");
    $stmt->execute([$id]);
    $ev = $stmt->fetch();

    if (!$ev) {
        http_response_code(404);
        echo json_encode(['error' => 'No se encontró la evaluación con ese ID']);
        exit;
    }

    $isOwner = false;
    if ($ev['usuario_id'] !== null && $ev['usuario_id'] == $_SESSION['user_id']) {
        $isOwner = true;
    } elseif ($ev['usuario_id'] === null && $ev['profesor'] === $_SESSION['user_nombre']) {
        $isOwner = true;
    }

    if ($_SESSION['user_rol'] !== 'admin' && !$isOwner) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para eliminar esta evaluación']);
        exit;
    }

    $stmtDel = $pdo->prepare("DELETE FROM evaluaciones WHERE id = ?");
    $stmtDel->execute([$id]);
    
    if ($stmtDel->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Evaluación eliminada correctamente']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Error al intentar eliminar la evaluación']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error delete_evaluacion: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>

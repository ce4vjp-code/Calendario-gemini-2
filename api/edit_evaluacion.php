<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$asignatura = trim(htmlspecialchars($data['asignatura'] ?? '', ENT_QUOTES, 'UTF-8'));
$curso = trim(htmlspecialchars($data['curso'] ?? '', ENT_QUOTES, 'UTF-8'));
$fecha = $data['fecha'] ?? '';
$hora = $data['hora'] ?? '';
$tipo = trim(htmlspecialchars($data['tipo'] ?? '', ENT_QUOTES, 'UTF-8'));
$observaciones = trim(htmlspecialchars($data['observaciones'] ?? '', ENT_QUOTES, 'UTF-8'));

if (!$id || empty($asignatura) || empty($curso) || empty($fecha) || empty($hora) || empty($tipo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos obligatorios']);
    exit;
}

// Validación estricta de formatos
$d = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$d || $d->format('Y-m-d') !== $fecha) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha inválido']);
    exit;
}

$t = DateTime::createFromFormat('H:i', $hora);
if (!$t || $t->format('H:i') !== $hora) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de hora inválido']);
    exit;
}

try {
    // Verificar propiedad (usando usuario_id) o rol admin
    $stmt = $pdo->prepare("SELECT usuario_id, profesor FROM evaluaciones WHERE id = ?");
    $stmt->execute([$id]);
    $ev = $stmt->fetch();

    if (!$ev) {
        http_response_code(404);
        echo json_encode(['error' => 'Evaluación no encontrada']);
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
        echo json_encode(['error' => 'No tienes permisos para editar esta evaluación']);
        exit;
    }

    $stmtUp = $pdo->prepare("UPDATE evaluaciones SET asignatura = ?, curso = ?, fecha = ?, hora = ?, tipo = ?, observaciones = ? WHERE id = ?");
    $stmtUp->execute([$asignatura, $curso, $fecha, $hora, $tipo, $observaciones, $id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error edit_evaluacion: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>

<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$nombre = trim($data['nombre'] ?? '');
$color = trim($data['color'] ?? '#4f46e5');

if (empty($nombre)) {
    echo json_encode(['success' => false, 'error' => 'El nombre de la asignatura es obligatorio']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO horario_asignaturas (nombre, color) VALUES (?, ?)");
    $stmt->execute([$nombre, $color]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al añadir asignatura: ' . $e->getMessage()]);
}
?>

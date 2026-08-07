<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Falta el ID de la encuesta']);
    exit;
}

try {
    // Obtener estado actual
    $stmt = $pdo->prepare("SELECT estado FROM encuestas WHERE id = ?");
    $stmt->execute([$data['id']]);
    $current = $stmt->fetchColumn();

    if (!$current) {
        echo json_encode(['success' => false, 'error' => 'Encuesta no encontrada']);
        exit;
    }

    $nuevoEstado = $current === 'abierta' ? 'cerrada' : 'abierta';

    $update = $pdo->prepare("UPDATE encuestas SET estado = ? WHERE id = ?");
    $update->execute([$nuevoEstado, $data['id']]);

    echo json_encode(['success' => true, 'nuevo_estado' => $nuevoEstado]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al actualizar estado']);
}

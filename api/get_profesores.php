<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT nombre FROM usuarios WHERE rol = 'profesor' ORDER BY nombre ASC");
    $profesores = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['success' => true, 'profesores' => $profesores]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error get_profesores: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>

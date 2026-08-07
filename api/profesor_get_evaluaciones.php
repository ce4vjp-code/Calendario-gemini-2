<?php
// api/profesor_get_evaluaciones.php
require_once '../config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'profesor') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    // Filtramos por el nombre del profesor, ya que las evaluaciones importadas antiguas tienen usuario_id = NULL
    $stmt = $pdo->prepare("SELECT * FROM evaluaciones WHERE profesor = ? ORDER BY fecha ASC");
    $stmt->execute([$_SESSION['user_nombre']]);
    $evaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'evaluaciones' => $evaluaciones]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error profesor_get_evaluaciones: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>

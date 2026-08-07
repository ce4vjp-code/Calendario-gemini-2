<?php
// api/get_evaluaciones.php
require_once '../config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('X-LiteSpeed-Cache-Control: no-cache');

try {
    $stmt = $pdo->query("SELECT * FROM evaluaciones ORDER BY fecha ASC");
    $evaluaciones = $stmt->fetchAll();
    echo json_encode($evaluaciones);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error get_evaluaciones: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>

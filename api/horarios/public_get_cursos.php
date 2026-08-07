<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, nombre FROM horario_cursos ORDER BY nombre ASC");
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $cursos]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener cursos']);
}
?>

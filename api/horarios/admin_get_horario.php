<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$curso_id = $_GET['curso_id'] ?? 0;

if (!$curso_id) {
    echo json_encode(['success' => false, 'error' => 'ID de curso no proporcionado']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT hc.id, hc.dia_semana, hc.bloque, hc.asignatura_id, ha.nombre as asignatura_nombre, ha.color 
        FROM horario_clases hc
        JOIN horario_asignaturas ha ON hc.asignatura_id = ha.id
        WHERE hc.curso_id = ?
    ");
    $stmt->execute([$curso_id]);
    $horario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $horario]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener horario: ' . $e->getMessage()]);
}
?>

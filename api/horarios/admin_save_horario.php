<?php
require_once '../../config.php';
require_once '../logger.php';
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
$curso_id = $data['curso_id'] ?? 0;
$dia_semana = $data['dia_semana'] ?? 0;
$bloque = $data['bloque'] ?? 0;
$asignatura_id = $data['asignatura_id'] ?? 0; // Si es 0 o null, es eliminar

if (!$curso_id || !$dia_semana || !$bloque) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    if (empty($asignatura_id)) {
        // Eliminar clase en ese bloque
        $stmt = $pdo->prepare("DELETE FROM horario_clases WHERE curso_id = ? AND dia_semana = ? AND bloque = ?");
        $stmt->execute([$curso_id, $dia_semana, $bloque]);
        registrar_actividad($pdo, 'Horarios', 'Borrar', "Bloque $bloque (Día $dia_semana) del Curso ID $curso_id vaciado");
    } else {
        // Insertar o actualizar clase en ese bloque
        $stmt = $pdo->prepare("
            INSERT INTO horario_clases (curso_id, asignatura_id, dia_semana, bloque) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE asignatura_id = VALUES(asignatura_id)
        ");
        $stmt->execute([$curso_id, $asignatura_id, $dia_semana, $bloque]);
        registrar_actividad($pdo, 'Horarios', 'Actualizar', "Bloque $bloque (Día $dia_semana) del Curso ID $curso_id actualizado (Asignatura ID $asignatura_id)");
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar clase: ' . $e->getMessage()]);
}
?>

<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

try {
    // Auto-aplicar parche de la base de datos para soportar menu_desplegable (ignorar fallos si ya está aplicado)
    try {
        $pdo->exec("ALTER TABLE encuesta_preguntas MODIFY tipo_pregunta VARCHAR(50) NOT NULL");
    } catch(Exception $e) {}
    // Obtener encuestas y la cantidad de respuestas recibidas
    
    $data = json_decode(file_get_contents('php://input'), true);
    $tipo = isset($data['tipo']) ? $data['tipo'] : 'anonima';
    
    $stmt = $pdo->prepare("
        SELECT e.*, 
               (SELECT COUNT(id) FROM encuesta_respuestas WHERE encuesta_id = e.id) as total_respuestas
        FROM encuestas e 
        WHERE e.tipo_encuesta = ?
        ORDER BY e.creado_en DESC
    ");
    $stmt->execute([$tipo]);
    $encuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $encuestas]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al obtener encuestas: ' . $e->getMessage()]);
}

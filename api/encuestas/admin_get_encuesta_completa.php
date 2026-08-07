<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El ID de la encuesta es obligatorio']);
    exit;
}

try {
    // 1. Obtener encuesta y contar respuestas
    $stmt = $pdo->prepare("SELECT e.*, (SELECT COUNT(*) FROM encuesta_respuestas r WHERE r.encuesta_id = e.id) as total_respuestas FROM encuestas e WHERE e.id = ?");
    $stmt->execute([$id]);
    $encuesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$encuesta) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Encuesta no encontrada']);
        exit;
    }

    // 2. Obtener preguntas
    $stmtPreg = $pdo->prepare("SELECT * FROM encuesta_preguntas WHERE encuesta_id = ? ORDER BY orden ASC");
    $stmtPreg->execute([$id]);
    $preguntasRaw = $stmtPreg->fetchAll(PDO::FETCH_ASSOC);

    $preguntas = [];
    foreach ($preguntasRaw as $p) {
        if ($p['tipo_pregunta'] === 'opcion_multiple' || $p['tipo_pregunta'] === 'menu_desplegable' || $p['tipo_pregunta'] === 'seleccion_multiple') {
            $parsed = json_decode($p['opciones'], true);
            if (is_array($parsed) && isset($parsed['items'])) {
                $p['opciones'] = $parsed['items'];
                $p['incluye_otro'] = isset($parsed['incluye_otro']) ? $parsed['incluye_otro'] : false;
                $p['incluye_justificacion'] = isset($parsed['incluye_justificacion']) ? $parsed['incluye_justificacion'] : false;
            } else {
                $p['opciones'] = $parsed; // Formato antiguo
                $p['incluye_otro'] = false;
                $p['incluye_justificacion'] = false;
            }
        } else {
            $p['opciones'] = [];
            $p['incluye_otro'] = false;
            $p['incluye_justificacion'] = false;
        }

        $preguntas[] = [
            'id' => $p['id'],
            'texto_pregunta' => $p['texto_pregunta'],
            'tipo_pregunta' => $p['tipo_pregunta'],
            'opciones' => $p['opciones'],
            'incluye_otro' => $p['incluye_otro'],
            'incluye_justificacion' => $p['incluye_justificacion']
        ];
    }
    
    echo json_encode(['success' => true, 'encuesta' => $encuesta, 'preguntas' => $preguntas]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error al obtener encuesta completa: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>

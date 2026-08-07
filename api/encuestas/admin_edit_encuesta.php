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
$titulo = trim($data['titulo'] ?? '');
$descripcion = trim($data['descripcion'] ?? '');
$preguntas = $data['preguntas'] ?? null;

if (!$id || empty($titulo)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El ID y el título son obligatorios']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Actualizar título y descripción
    $stmt = $pdo->prepare("UPDATE encuestas SET titulo = ?, descripcion = ? WHERE id = ?");
    $stmt->execute([$titulo, $descripcion, $id]);
    
    // 2. Si se enviaron preguntas, verificar que no haya respuestas antes de modificarlas
    if (is_array($preguntas)) {
        // Verificar cantidad de respuestas
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) as total FROM encuesta_respuestas WHERE encuesta_id = ?");
        $stmtCheck->execute([$id]);
        $row = $stmtCheck->fetch();
        
        if ($row['total'] > 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No se pueden editar las preguntas porque la encuesta ya tiene respuestas. Vacía la encuesta primero.']);
            exit;
        }

        // Si tiene 0 respuestas, eliminamos las preguntas antiguas
        $stmtDel = $pdo->prepare("DELETE FROM encuesta_preguntas WHERE encuesta_id = ?");
        $stmtDel->execute([$id]);

        // Insertamos las nuevas preguntas
        $stmtPregunta = $pdo->prepare("INSERT INTO encuesta_preguntas (encuesta_id, texto_pregunta, tipo_pregunta, opciones, orden) VALUES (?, ?, ?, ?, ?)");
        
        $orden = 1;
        foreach ($preguntas as $p) {
            $opciones = null;
            if (($p['tipo_pregunta'] === 'opcion_multiple' || $p['tipo_pregunta'] === 'menu_desplegable' || $p['tipo_pregunta'] === 'seleccion_multiple') && isset($p['opciones'])) {
                $opcionesData = [
                    'items' => $p['opciones'],
                    'incluye_otro' => isset($p['incluye_otro']) ? filter_var($p['incluye_otro'], FILTER_VALIDATE_BOOLEAN) : false,
                    'incluye_justificacion' => isset($p['incluye_justificacion']) ? filter_var($p['incluye_justificacion'], FILTER_VALIDATE_BOOLEAN) : false
                ];
                $opciones = json_encode($opcionesData);
            }
            
            $stmtPregunta->execute([
                $id,
                trim($p['texto_pregunta']),
                $p['tipo_pregunta'],
                $opciones,
                $orden
            ]);
            $orden++;
        }
    }
    
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Error al editar encuesta: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
}
?>

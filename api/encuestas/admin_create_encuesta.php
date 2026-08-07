<?php
require_once '../../config.php';
require_once '../logger.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['titulo']) || empty(trim($data['titulo']))) {
    echo json_encode(['success' => false, 'error' => 'El título es obligatorio']);
    exit;
}

if (!isset($data['preguntas']) || !is_array($data['preguntas']) || count($data['preguntas']) === 0) {
    echo json_encode(['success' => false, 'error' => 'Debes agregar al menos una pregunta']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Generar token único de 16 caracteres
    $token = bin2hex(random_bytes(8));
    
    $tipo_encuesta = isset($data['tipo_encuesta']) ? $data['tipo_encuesta'] : 'anonima';

    // Insertar encuesta
    $stmt = $pdo->prepare("INSERT INTO encuestas (titulo, descripcion, token_publico, tipo_encuesta, estado) VALUES (?, ?, ?, ?, 'abierta')");
    $stmt->execute([
        trim($data['titulo']),
        trim($data['descripcion'] ?? ''),
        $token,
        $tipo_encuesta
    ]);
    $encuestaId = $pdo->lastInsertId();

    // Insertar preguntas
    $stmtPregunta = $pdo->prepare("INSERT INTO encuesta_preguntas (encuesta_id, texto_pregunta, tipo_pregunta, opciones, orden) VALUES (?, ?, ?, ?, ?)");
    
    $orden = 1;
    foreach ($data['preguntas'] as $p) {
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
            $encuestaId,
            trim($p['texto_pregunta']),
            $p['tipo_pregunta'],
            $opciones,
            $orden
        ]);
        $orden++;
    }

    $pdo->commit();
    
    // Registrar auditoría
    $titulo_log = trim($data['titulo']);
    $num_preguntas = count($data['preguntas']);
    registrar_actividad($pdo, 'Encuestas', 'Crear', "Encuesta creada. Título: '$titulo_log' ($num_preguntas preguntas)");

    echo json_encode(['success' => true, 'token' => $token]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
}

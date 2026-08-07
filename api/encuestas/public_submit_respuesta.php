<?php
require_once '../../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['token']) || !isset($data['respuestas']) || !is_array($data['respuestas'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Validar token y estado
    $stmt = $pdo->prepare("SELECT id, estado FROM encuestas WHERE token_publico = ?");
    $stmt->execute([$data['token']]);
    $encuesta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$encuesta || $encuesta['estado'] !== 'abierta') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'La encuesta no está disponible']);
        exit;
    }

    // Crear ticket de respuesta
    $stmtResp = $pdo->prepare("INSERT INTO encuesta_respuestas (encuesta_id) VALUES (?)");
    $stmtResp->execute([$encuesta['id']]);
    $respuestaId = $pdo->lastInsertId();

    // Insertar cada detalle
    $stmtDetalle = $pdo->prepare("INSERT INTO encuesta_respuestas_detalle (respuesta_id, pregunta_id, valor_respuesta) VALUES (?, ?, ?)");
    foreach ($data['respuestas'] as $resp) {
        // Validación básica
        if (isset($resp['pregunta_id']) && isset($resp['valor'])) {
            $stmtDetalle->execute([
                $respuestaId,
                $resp['pregunta_id'],
                $resp['valor']
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Error al guardar respuesta: ' . $e->getMessage()]);
}

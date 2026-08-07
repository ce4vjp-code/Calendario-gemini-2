<?php
require_once '../../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['token'])) {
    echo json_encode(['success' => false, 'error' => 'Token no proporcionado']);
    exit;
}

$token = $data['token'];

try {
    $stmt = $pdo->prepare("SELECT id, titulo, descripcion, estado, tipo_encuesta FROM encuestas WHERE token_publico = ?");
    $stmt->execute([$token]);
    $encuesta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$encuesta) {
        echo json_encode(['success' => false, 'error' => 'Encuesta no encontrada o enlace inválido']);
        exit;
    }

    if ($encuesta['estado'] !== 'abierta') {
        echo json_encode(['success' => false, 'error' => 'Esta encuesta ya ha sido cerrada y no acepta más respuestas']);
        exit;
    }

    $stmtPreg = $pdo->prepare("SELECT id, texto_pregunta, tipo_pregunta, opciones FROM encuesta_preguntas WHERE encuesta_id = ? ORDER BY orden ASC");
    $stmtPreg->execute([$encuesta['id']]);
    $preguntas = $stmtPreg->fetchAll(PDO::FETCH_ASSOC);

    // Parse options if needed
    foreach ($preguntas as &$p) {
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
            $p['incluye_otro'] = false;
            $p['incluye_justificacion'] = false;
        }
    }

    echo json_encode(['success' => true, 'encuesta' => $encuesta, 'preguntas' => $preguntas]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error de servidor']);
}

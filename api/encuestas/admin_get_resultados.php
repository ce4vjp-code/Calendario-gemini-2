<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Falta el ID de la encuesta']);
    exit;
}

$encuestaId = $data['id'];

try {
    // 1. Obtener los detalles de la encuesta y sus preguntas
    $stmt = $pdo->prepare("SELECT * FROM encuestas WHERE id = ?");
    $stmt->execute([$encuestaId]);
    $encuesta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$encuesta) {
        echo json_encode(['success' => false, 'error' => 'Encuesta no encontrada']);
        exit;
    }

    $stmtPreg = $pdo->prepare("SELECT * FROM encuesta_preguntas WHERE encuesta_id = ? ORDER BY orden ASC");
    $stmtPreg->execute([$encuestaId]);
    $preguntas = $stmtPreg->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener los resultados tabulados
    $resultados = [];
    foreach ($preguntas as $p) {
        $pregData = [
            'id' => $p['id'],
            'texto' => $p['texto_pregunta'],
            'tipo' => $p['tipo_pregunta'],
            'respuestas' => [] // Para texto libre, será array de strings; para opcion_multiple, diccionario de conteos
        ];

        $incluye_otro = false;
        if ($p['tipo_pregunta'] === 'opcion_multiple' || $p['tipo_pregunta'] === 'menu_desplegable' || $p['tipo_pregunta'] === 'seleccion_multiple') {
            $parsed = json_decode($p['opciones'], true);
            $opciones_array = [];
            if (is_array($parsed) && isset($parsed['items'])) {
                $opciones_array = $parsed['items'];
                $incluye_otro = isset($parsed['incluye_otro']) ? $parsed['incluye_otro'] : false;
            } else {
                $opciones_array = is_array($parsed) ? $parsed : []; // Formato antiguo
            }
            foreach ($opciones_array as $op) {
                $pregData['respuestas'][$op] = 0; // Inicializar contadores a 0
            }
        }

        if ($p['tipo_pregunta'] !== 'seccion' && $p['tipo_pregunta'] !== 'descripcion_corta') {
            // Traer todas las respuestas dadas a esta pregunta
            $stmtResp = $pdo->prepare("SELECT valor_respuesta FROM encuesta_respuestas_detalle WHERE pregunta_id = ?");
            $stmtResp->execute([$p['id']]);
            while ($r = $stmtResp->fetch(PDO::FETCH_ASSOC)) {
                $val = $r['valor_respuesta'];
                if ($p['tipo_pregunta'] === 'texto') {
                    if (!empty(trim($val))) {
                        $pregData['respuestas'][] = $val;
                    }
                } else {
                    // Solo contabilizamos si el valor enviado coincide con una de las opciones válidas.
                    if (array_key_exists($val, $pregData['respuestas'])) {
                        $pregData['respuestas'][$val]++;
                    } else if ($incluye_otro) {
                        // Si era "Otro", lo contamos igual agregándole el prefijo "Otro: "
                        $claveOtro = "Otro: " . $val;
                        if (!isset($pregData['respuestas'][$claveOtro])) {
                            $pregData['respuestas'][$claveOtro] = 0;
                        }
                        $pregData['respuestas'][$claveOtro]++;
                    }
                }
            }
        }

        $resultados[] = $pregData;
    }

    echo json_encode(['success' => true, 'encuesta' => $encuesta, 'resultados' => $resultados]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al obtener resultados: ' . $e->getMessage()]);
}

<?php
require_once "../../config.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_rol"]) || $_SESSION["user_rol"] !== "admin") {
    echo json_encode(["success" => false, "error" => "Acceso denegado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data["id"]) || !isset($data["email"])) {
    echo json_encode(["success" => false, "error" => "Faltan datos requeridos (ID o Email)"]);
    exit;
}

$encuestaId = $data["id"];
$emailDestino = filter_var($data["email"], FILTER_SANITIZE_EMAIL);

if (!filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "error" => "Correo electronico invalido"]);
    exit;
}

try {
    // 1. Obtener datos de la encuesta
    $stmt = $pdo->prepare("SELECT * FROM encuestas WHERE id = ?");
    $stmt->execute([$encuestaId]);
    $encuesta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$encuesta) {
        echo json_encode(["success" => false, "error" => "Encuesta no encontrada"]);
        exit;
    }

    // 2. Obtener preguntas
    $stmtPreg = $pdo->prepare("SELECT * FROM encuesta_preguntas WHERE encuesta_id = ? ORDER BY orden ASC");
    $stmtPreg->execute([$encuestaId]);
    $preguntas = $stmtPreg->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener respuestas
    $stmtResp = $pdo->prepare("SELECT r.id as respuesta_id, r.fecha_respuesta, d.pregunta_id, d.valor_respuesta 
                                FROM encuesta_respuestas r 
                                LEFT JOIN encuesta_respuestas_detalle d ON r.id = d.respuesta_id 
                                WHERE r.encuesta_id = ?
                                ORDER BY r.fecha_respuesta DESC");
    $stmtResp->execute([$encuestaId]);
    
    $submissions = [];
    while ($row = $stmtResp->fetch(PDO::FETCH_ASSOC)) {
        $respId = $row["respuesta_id"];
        if (!isset($submissions[$respId])) {
            $submissions[$respId] = [
                "id" => $respId,
                "fecha" => $row["fecha_respuesta"],
                "respuestas" => []
            ];
        }
        if ($row["pregunta_id"]) {
            $submissions[$respId]["respuestas"][$row["pregunta_id"]] = $row["valor_respuesta"];
        }
    }
    
    $submissions = array_values($submissions);

    // 4. Construir el HTML del correo
    $html = "<html><head><title>Resultados de Encuesta</title>";
    $html .= "<style>
                body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                h2 { color: #0056b3; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
                .response-card { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
                .response-header { font-weight: bold; color: #555; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
                .question { font-weight: bold; margin-top: 10px; color: #444; }
                .answer { margin-left: 15px; color: #000; }
                .seccion { font-size: 1.1em; color: #0056b3; margin-top: 15px; border-bottom: 1px solid #0056b3; }
              </style>";
    $html .= "</head><body>";
    
    $html .= "<h2>Resultados de Encuesta: " . htmlspecialchars($encuesta['titulo']) . "</h2>";
    $html .= "<p><strong>Descripcion:</strong> " . htmlspecialchars($encuesta['descripcion']) . "</p>";
    $html .= "<p><strong>Total de respuestas:</strong> " . count($submissions) . "</p>";
    
    if (count($submissions) == 0) {
        $html .= "<p>No hay respuestas registradas para esta encuesta todavia.</p>";
    } else {
        foreach ($submissions as $index => $sub) {
            $html .= "<div class='response-card'>";
            $html .= "<div class='response-header'>Respuesta #" . (count($submissions) - $index) . " - " . $sub['fecha'] . "</div>";
            
            $i = 1;
            foreach ($preguntas as $p) {
                if ($p['tipo_pregunta'] === 'seccion') {
                    $html .= "<div class='seccion'>" . htmlspecialchars($p['texto_pregunta']) . "</div>";
                    continue;
                }
                if ($p['tipo_pregunta'] === 'descripcion_corta') {
                    $html .= "<p style='font-style:italic; color:#666;'>" . htmlspecialchars($p['texto_pregunta']) . "</p>";
                    continue;
                }

                $respuesta_texto = isset($sub['respuestas'][$p['id']]) ? $sub['respuestas'][$p['id']] : "<span style='color:#999; font-style:italic;'>Sin responder</span>";
                
                $html .= "<div class='question'>" . $i . ". " . htmlspecialchars($p['texto_pregunta']) . "</div>";
                $html .= "<div class='answer'>" . htmlspecialchars($respuesta_texto) . "</div>";
                $i++;
            }
            $html .= "</div>";
        }
    }
    
    $html .= "</body></html>";

    // 5. Enviar el correo
    $asunto = "Resultados de Encuesta: " . $encuesta['titulo'];
    
    // Cabeceras para enviar correo HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    
    // Cabeceras adicionales (remitente)
    $headers .= 'From: Liceo TPG Evaluaciones <no-reply@liceotpggm.cl>' . "\r\n";
    
    $envioExitoso = mail($emailDestino, $asunto, $html, $headers);
    
    if ($envioExitoso) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "La funcion mail() de PHP fallo al intentar enviar el correo. Verifique la configuracion de correo del servidor."]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Error: " . $e->getMessage()]);
}

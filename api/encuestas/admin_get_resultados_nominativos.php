<?php
require_once "../../config.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_rol"]) || $_SESSION["user_rol"] !== "admin") {
    echo json_encode(["success" => false, "error" => "Acceso denegado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data["id"])) {
    echo json_encode(["success" => false, "error" => "Falta el ID"]);
    exit;
}

$encuestaId = $data["id"];

try {
    $stmt = $pdo->prepare("SELECT * FROM encuestas WHERE id = ?");
    $stmt->execute([$encuestaId]);
    $encuesta = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtPreg = $pdo->prepare("SELECT * FROM encuesta_preguntas WHERE encuesta_id = ? ORDER BY orden ASC");
    $stmtPreg->execute([$encuestaId]);
    $preguntas = $stmtPreg->fetchAll(PDO::FETCH_ASSOC);

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

    echo json_encode([
        "success" => true, 
        "encuesta" => $encuesta, 
        "preguntas" => $preguntas, 
        "submissions" => array_values($submissions)
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Error: " . $e->getMessage()]);
}

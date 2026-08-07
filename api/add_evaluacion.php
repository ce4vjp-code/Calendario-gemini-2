<?php
// api/add_evaluacion.php
require_once '../config.php';
require_once 'logger.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener datos enviados en el cuerpo de la petición (JSON)
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibieron datos válidos']);
    exit;
}

$asignatura = trim(htmlspecialchars($data['asignatura'] ?? '', ENT_QUOTES, 'UTF-8'));
$curso = trim(htmlspecialchars($data['curso'] ?? '', ENT_QUOTES, 'UTF-8'));
$profesor = $_SESSION['user_nombre'] ?? '';
$usuario_id = $_SESSION['user_id'] ?? null;
$fecha = $data['fecha'] ?? '';
$hora = $data['hora'] ?? '';
$tipo = trim(htmlspecialchars($data['tipo'] ?? '', ENT_QUOTES, 'UTF-8'));
$observaciones = trim(htmlspecialchars($data['observaciones'] ?? '', ENT_QUOTES, 'UTF-8'));

if (empty($asignatura) || empty($curso) || empty($profesor) || empty($usuario_id) || empty($fecha) || empty($hora) || empty($tipo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

// Validación estricta de formatos
$d = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$d || $d->format('Y-m-d') !== $fecha) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha inválido. Use AAAA-MM-DD']);
    exit;
}

$t = DateTime::createFromFormat('H:i', $hora);
if (!$t || $t->format('H:i') !== $hora) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de hora inválido. Use HH:MM']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO evaluaciones (asignatura, curso, profesor, usuario_id, fecha, hora, tipo, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$asignatura, $curso, $profesor, $usuario_id, $fecha, $hora, $tipo, $observaciones]);
    
    $id = $pdo->lastInsertId();
    
    // Log de auditoría
    registrar_actividad($pdo, 'Evaluaciones', 'Crear', "Evaluación agregada. Curso: $curso, Asignatura: $asignatura, Fecha: $fecha");
    
    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Evaluación agregada correctamente']);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error add_evaluacion: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>

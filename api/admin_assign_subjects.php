<?php
require_once '../config.php';
require_once 'logger.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$usuario_id = $data['usuario_id'] ?? null;
$asignaturas = $data['asignaturas'] ?? []; // Array of subject names

if (!$usuario_id) {
    echo json_encode(['success' => false, 'error' => 'ID de usuario no proporcionado']);
    exit;
}

try {
    $asignaturas_json = json_encode($asignaturas);
    
    $stmt = $pdo->prepare("UPDATE usuarios SET asignaturas_asignadas = ? WHERE id = ?");
    $stmt->execute([$asignaturas_json, $usuario_id]);
    
    // Log
    $stmtUser = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmtUser->execute([$usuario_id]);
    $uName = $stmtUser->fetchColumn();
    
    $num = count($asignaturas);
    registrar_actividad($pdo, 'Usuarios', 'Editar', "Asignaturas asignadas a $uName ($num asignaturas)");

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar las asignaturas']);
}
?>

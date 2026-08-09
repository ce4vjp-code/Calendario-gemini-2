<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;
$rol = $data['rol'] ?? '';
$puede_pedir_equipos = isset($data['puede_pedir_equipos']) ? (int)$data['puede_pedir_equipos'] : 0;

if (!$id || empty($rol)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

$allowed_roles = ['admin', 'profesor', 'diplomas', 'auxiliar', 'asistente_educacion', 'externo', 'directivo', 'inventario'];
if (!in_array($rol, $allowed_roles)) {
    http_response_code(400);
    echo json_encode(['error' => 'Rol inválido']);
    exit;
}

try {
    // Si estamos editando al usuario actual, no permitir quitarse el rol de admin
    if ($id == $_SESSION['user_id'] && $rol !== 'admin') {
        http_response_code(400);
        echo json_encode(['error' => 'No puedes quitarte el rol de administrador a ti mismo.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET rol = ?, puede_pedir_equipos = ? WHERE id = ?");
    $stmt->execute([$rol, $puede_pedir_equipos, $id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error admin_edit_rol_permisos: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>

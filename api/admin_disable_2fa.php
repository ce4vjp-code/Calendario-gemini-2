<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Solo administradores.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? (int)$data['id'] : null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de usuario no proporcionado.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT nombre, rut FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado.']);
        exit;
    }

    $stmtUp = $pdo->prepare("UPDATE usuarios SET is_2fa_enabled = 0, secret_2fa = NULL WHERE id = ?");
    $stmtUp->execute([$id]);

    // Log de auditoría
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'USUARIOS', 'QUITAR_2FA', ?, ?)");
    $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], "Se desactivó 2FA para el usuario {$user['nombre']} ({$user['rut']})", $ip]);

    echo json_encode(['success' => true, 'message' => "Autenticación 2FA desactivada para {$user['nombre']}. Ahora solo requerirá contraseña."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al desactivar 2FA: ' . $e->getMessage()]);
}
?>

<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, rut, nombre, email, rol, asignaturas_asignadas FROM usuarios ORDER BY nombre ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'usuarios' => $usuarios, 'current_user_id' => $_SESSION['user_id']]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error admin_get_usuarios: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>

<?php
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], ['admin', 'inventario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, rut, nombre, email, rol, COALESCE(puede_pedir_equipos, 0) as puede_pedir_equipos FROM usuarios ORDER BY nombre ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'usuarios' => $usuarios]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error get_usuarios_prestamos: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno']);
}
?>

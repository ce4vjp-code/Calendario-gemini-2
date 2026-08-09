<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos']);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT p.*, e.nombre as equipo_nombre, e.marca, e.modelo, e.numero_serie, 
               u.nombre as usuario_nombre, u.rut, u.email as usuario_email,
               DATE_FORMAT(p.fecha_solicitud, '%d/%m/%Y %H:%i') as fecha_solicitud_fmt
        FROM inventario_prestamos p
        JOIN inventario_equipos e ON p.equipo_id = e.id
        JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.fecha_solicitud DESC
    ");
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'prestamos' => $prestamos]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error admin_get_prestamos: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno']);
}
?>

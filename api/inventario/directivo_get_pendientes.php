<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], ['admin', 'directivo'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT p.id, e.nombre as equipo_nombre, u.nombre as usuario_nombre,
               DATE_FORMAT(p.fecha_solicitud, '%d/%m/%Y %H:%i') as fecha_solicitud_fmt
        FROM inventario_prestamos p
        JOIN inventario_equipos e ON p.equipo_id = e.id
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.estado = 'pendiente_codigo'
        ORDER BY p.fecha_solicitud ASC
    ");
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'pendientes' => $pendientes]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}
?>

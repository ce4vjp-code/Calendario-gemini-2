<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, e.nombre as equipo_nombre,
               DATE_FORMAT(p.fecha_solicitud, '%d/%m/%Y %H:%i') as fecha_solicitud_fmt
        FROM inventario_prestamos p
        JOIN inventario_equipos e ON p.equipo_id = e.id
        WHERE p.usuario_id = ?
        ORDER BY p.fecha_solicitud DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'prestamos' => $prestamos]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}
?>

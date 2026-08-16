<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    // Todos los usuarios pueden ver el catálogo, pero si no son admin,
    // quizás queramos mostrar solo los 'disponibles' o todo para que sepan qué existe.
    // Vamos a mostrar todo, pero el admin tiene más control
    $stmt = $pdo->query("
        SELECT e.id, e.nombre, e.marca, e.modelo, e.numero_serie, 
               COALESCE(e.ubicacion, '') AS ubicacion,
               COALESCE(e.acceso_internet, 'Permanente') AS acceso_internet,
               COALESCE(e.sensibilidad, 'Publico') AS sensibilidad,
               e.descripcion, e.estado, e.fecha_registro, e.cantidad,
               (e.cantidad - COALESCE((SELECT SUM(p.cantidad) FROM inventario_prestamos p WHERE p.equipo_id = e.id AND p.estado IN ('prestado', 'pendiente_aprobacion', 'pendiente_codigo', 'atrasado')), 0)) AS cantidad_disponible
        FROM inventario_equipos e ORDER BY e.nombre ASC
    ");
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener los préstamos activos para saber quién tiene qué equipo si está prestado
    // Opcional, pero util para mostrar en el admin
    $prestamos_activos = [];
    if (in_array($_SESSION['user_rol'], ['admin', 'inventario'])) {
        $stmtPrest = $pdo->query("
            SELECT p.equipo_id, u.nombre as usuario_nombre, p.estado 
            FROM inventario_prestamos p 
            JOIN usuarios u ON p.usuario_id = u.id 
            WHERE p.estado IN ('prestado', 'atrasado', 'pendiente_aprobacion', 'pendiente_codigo')
        ");
        while ($row = $stmtPrest->fetch(PDO::FETCH_ASSOC)) {
            $prestamos_activos[$row['equipo_id']][] = $row;
        }
    }

    echo json_encode(['success' => true, 'equipos' => $equipos, 'prestamos_activos' => $prestamos_activos]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error get_equipos: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno']);
}
?>

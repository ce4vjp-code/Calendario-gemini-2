<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], ['admin', 'inventario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';
$id = $data['id'] ?? null;
$equipo_id = $data['equipo_id'] ?? null;
$fecha = $data['fecha'] ?? '';
$descripcion = trim($data['descripcion'] ?? '');
$costo = isset($data['costo']) ? (float)$data['costo'] : 0.00;

try {
    if ($action === 'get') {
        if (!$equipo_id) throw new Exception('El ID del equipo es obligatorio');
        
        $stmt = $pdo->prepare("SELECT * FROM inventario_mantenimiento WHERE equipo_id = ? ORDER BY fecha DESC");
        $stmt->execute([$equipo_id]);
        $mantenimientos = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'mantenimientos' => $mantenimientos]);
        
    } elseif ($action === 'add') {
        if (!$equipo_id || empty($fecha) || empty($descripcion)) {
            throw new Exception('Faltan campos obligatorios');
        }
        
        $stmt = $pdo->prepare("INSERT INTO inventario_mantenimiento (equipo_id, fecha, descripcion, costo, registrado_por) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$equipo_id, $fecha, $descripcion, $costo, $_SESSION['user_id']]);
        
        // Log auditoria
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Inventario', 'Agregar Mantenimiento', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], "Equipo ID: $equipo_id, Costo: $costo", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'delete') {
        if (!$id) throw new Exception('ID obligatorio');
        
        $stmt = $pdo->prepare("DELETE FROM inventario_mantenimiento WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log auditoria
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Inventario', 'Eliminar Mantenimiento', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], "Mantenimiento ID: $id", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

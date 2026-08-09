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
$nombre = trim($data['nombre'] ?? '');
$marca = trim($data['marca'] ?? '');
$modelo = trim($data['modelo'] ?? '');
$numero_serie = trim($data['numero_serie'] ?? '');
$descripcion = trim($data['descripcion'] ?? '');
$estado = $data['estado'] ?? 'disponible';
$cantidad = isset($data['cantidad']) ? (int)$data['cantidad'] : 1;

try {
    if ($action === 'add') {
        if (empty($nombre)) throw new Exception('El nombre es obligatorio');
        $stmt = $pdo->prepare("INSERT INTO inventario_equipos (nombre, marca, modelo, numero_serie, descripcion, estado, cantidad) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $marca, $modelo, $numero_serie, $descripcion, $estado, $cantidad]);
        
        // Log auditoria
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Inventario', 'Agregar Equipo', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], "Equipo: $nombre (Cant: $cantidad)", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'edit') {
        if (!$id || empty($nombre)) throw new Exception('ID y nombre obligatorios');
        $stmt = $pdo->prepare("UPDATE inventario_equipos SET nombre = ?, marca = ?, modelo = ?, numero_serie = ?, descripcion = ?, estado = ?, cantidad = ? WHERE id = ?");
        $stmt->execute([$nombre, $marca, $modelo, $numero_serie, $descripcion, $estado, $cantidad, $id]);
        
        // Log auditoria
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Inventario', 'Editar Equipo', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], "ID: $id, Nombre: $nombre", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'delete') {
        if (!$id) throw new Exception('ID obligatorio');
        // Validar si está prestado no se puede eliminar (a menos que cascade on delete esté ok, pero mejor proteger)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM inventario_prestamos WHERE equipo_id = ? AND estado IN ('prestado', 'pendiente_aprobacion', 'pendiente_codigo')");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception('No se puede eliminar porque tiene préstamos activos o pendientes.');
        }
        
        $stmt = $pdo->prepare("DELETE FROM inventario_equipos WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log auditoria
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Inventario', 'Eliminar Equipo', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], "ID eliminado: $id", $_SERVER['REMOTE_ADDR'] ?? '']);
        
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

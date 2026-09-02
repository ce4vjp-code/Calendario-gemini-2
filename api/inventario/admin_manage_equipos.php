<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], ['admin', 'inventario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos']);
    exit;
}

// Support both JSON (from legacy code) and POST/FormData
if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $data = json_decode(file_get_contents("php://input"), true);
} else {
    $data = $_POST;
}

$action = $data['action'] ?? '';
$id = $data['id'] ?? null;
$nombre = trim($data['nombre'] ?? '');
$marca = trim($data['marca'] ?? '');
$modelo = trim($data['modelo'] ?? '');
$numero_serie = trim($data['numero_serie'] ?? '');
$ubicacion = trim($data['ubicacion'] ?? '');
$acceso_internet = trim($data['acceso_internet'] ?? 'Permanente');
$sensibilidad = trim($data['sensibilidad'] ?? 'Publico');
$descripcion = trim($data['descripcion'] ?? '');
$estado = $data['estado'] ?? 'inventario';
$cantidad = isset($data['cantidad']) ? (int)$data['cantidad'] : 1;
$categoria = trim($data['categoria'] ?? 'General');
$codigo_qr = trim($data['codigo_qr'] ?? '');

// Validar opciones permitidas
$accesosPermitidos = ['Permanente', 'Ocasional', 'Ninguno'];
if (!in_array($acceso_internet, $accesosPermitidos)) {
    $acceso_internet = 'Permanente';
}

$sensibilidadesPermitidas = ['Confidencial', 'Restringido', 'Publico'];
if (!in_array($sensibilidad, $sensibilidadesPermitidas)) {
    $sensibilidad = 'Publico';
}

// Procesar subida de imagen
$imagen_url = $data['imagen_url_actual'] ?? null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/equipos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileInfo = pathinfo($_FILES['imagen']['name']);
    $extension = strtolower($fileInfo['extension']);
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($extension, $allowedExts)) {
        $filename = uniqid('equipo_') . '.' . $extension;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadDir . $filename)) {
            $imagen_url = 'uploads/equipos/' . $filename;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de imagen no permitido.']);
        exit;
    }
}

try {
    if ($action === 'add') {
        if (empty($nombre)) throw new Exception('El nombre es obligatorio');
        
        $sql = "INSERT INTO inventario_equipos (nombre, marca, modelo, numero_serie, ubicacion, acceso_internet, sensibilidad, descripcion, estado, cantidad, categoria, codigo_qr, imagen_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $marca, $modelo, $numero_serie, $ubicacion, $acceso_internet, $sensibilidad, $descripcion, $estado, $cantidad, $categoria, $codigo_qr, $imagen_url]);
        
        // Log auditoria
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Inventario', 'Agregar Equipo', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], "Equipo: $nombre (Cant: $cantidad, Ubic: $ubicacion)", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'edit') {
        if (!$id || empty($nombre)) throw new Exception('ID y nombre obligatorios');
        
        $sql = "UPDATE inventario_equipos SET nombre = ?, marca = ?, modelo = ?, numero_serie = ?, ubicacion = ?, acceso_internet = ?, sensibilidad = ?, descripcion = ?, estado = ?, cantidad = ?, categoria = ?, codigo_qr = ?, imagen_url = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $marca, $modelo, $numero_serie, $ubicacion, $acceso_internet, $sensibilidad, $descripcion, $estado, $cantidad, $categoria, $codigo_qr, $imagen_url, $id]);
        
        // Log auditoria
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Inventario', 'Editar Equipo', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], "ID: $id, Nombre: $nombre", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'delete') {
        if (!$id) throw new Exception('ID obligatorio');
        // Validar si está prestado no se puede eliminar
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM inventario_prestamos WHERE equipo_id = ? AND estado IN ('prestado', 'pendiente_aprobacion', 'pendiente_codigo')");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception('No se puede eliminar porque tiene préstamos activos o pendientes.');
        }
        
        // Opcional: borrar imagen
        $stmtImg = $pdo->prepare("SELECT imagen_url FROM inventario_equipos WHERE id = ?");
        $stmtImg->execute([$id]);
        $oldImg = $stmtImg->fetchColumn();
        if ($oldImg && file_exists(__DIR__ . '/../../' . $oldImg)) {
            unlink(__DIR__ . '/../../' . $oldImg);
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

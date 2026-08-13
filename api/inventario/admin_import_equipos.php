<?php
require_once '../../config.php';
header('Content-Type: application/json');

// Validar permisos
if (!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], ['admin', 'inventario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos']);
    exit;
}

// Recibir datos JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['equipos']) || !is_array($data['equipos'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos o vacíos']);
    exit;
}

$equipos = $data['equipos'];
$insertados = 0;
$errores = 0;
$logErrores = [];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO inventario_equipos 
        (nombre, marca, modelo, numero_serie, cantidad, estado, descripcion) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($equipos as $index => $eq) {
        $nombre = trim($eq['nombre'] ?? '');
        $marca = trim($eq['marca'] ?? '');
        $modelo = trim($eq['modelo'] ?? '');
        $serie = trim($eq['numero_serie'] ?? '');
        $cantidad = isset($eq['cantidad']) ? (int)$eq['cantidad'] : 1;
        $estado = trim($eq['estado'] ?? 'inventario');
        $descripcion = trim($eq['descripcion'] ?? '');

        // Validaciones básicas
        if (empty($nombre)) {
            $errores++;
            $logErrores[] = "Fila " . ($index + 2) . ": El nombre está vacío.";
            continue;
        }

        if ($cantidad < 1) $cantidad = 1;

        // Validar que el estado sea uno de los permitidos por el ENUM
        $estadosPermitidos = ['inventario', 'disponible', 'en_prestamo', 'en_mantenimiento', 'no_disponible', 'prestado', 'mantenimiento'];
        if (!in_array($estado, $estadosPermitidos)) {
            $estado = 'inventario';
        }

        try {
            $stmt->execute([$nombre, $marca, $modelo, $serie, $cantidad, $estado, $descripcion]);
            $insertados++;
        } catch (Exception $ex) {
            $errores++;
            $logErrores[] = "Fila " . ($index + 2) . ": Error al insertar en BD (" . $ex->getMessage() . ")";
        }
    }

    $pdo->commit();

    if ($insertados > 0) {
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Inventario', 'Importar Equipos Excel', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], "Equipos importados: $insertados", $_SERVER['REMOTE_ADDR'] ?? '']);
    }

    echo json_encode([
        'success' => true,
        'insertados' => $insertados,
        'errores' => $errores,
        'logErrores' => $logErrores
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error crítico al importar: ' . $e->getMessage()]);
}
?>

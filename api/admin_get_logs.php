<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$fecha = $_GET['fecha'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['success' => false, 'error' => 'Formato de fecha inválido']);
    exit;
}

try {
    // 1. Obtener ingresos
    $stmtIngresos = $pdo->prepare("SELECT * FROM registro_ingresos WHERE DATE(fecha_hora) = ? ORDER BY fecha_hora DESC");
    $stmtIngresos->execute([$fecha]);
    $ingresos = $stmtIngresos->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener actividades
    $stmtActividades = $pdo->prepare("SELECT * FROM registro_actividades WHERE DATE(fecha_hora) = ? ORDER BY fecha_hora DESC");
    $stmtActividades->execute([$fecha]);
    $actividades = $stmtActividades->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'fecha' => $fecha,
        'ingresos' => $ingresos,
        'actividades' => $actividades
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
}
?>

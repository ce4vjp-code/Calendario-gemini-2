<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$equipo_id = $data['equipo_id'] ?? null;
$dias = (int)($data['dias'] ?? 1);
if ($dias < 1) $dias = 1;
if ($dias > 365) $dias = 365;
$cantidad = (int)($data['cantidad'] ?? 1);

if ($cantidad < 1) $cantidad = 1;

if (!$equipo_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Equipo no especificado']);
    exit;
}

try {
    // Validar si el usuario tiene permiso
    $stmtUser = $pdo->prepare("SELECT rol, puede_pedir_equipos FROM usuarios WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user = $stmtUser->fetch();
    
    if (!$user || ($user['puede_pedir_equipos'] == 0 && $user['rol'] !== 'admin')) {
        throw new Exception('No tienes permisos para solicitar equipos.');
    }

    $pdo->beginTransaction();

    // Validar estado del equipo y stock
    $stmtEq = $pdo->prepare("
        SELECT e.estado, e.cantidad, 
               (e.cantidad - COALESCE((SELECT SUM(p.cantidad) FROM inventario_prestamos p WHERE p.equipo_id = e.id AND p.estado IN ('prestado', 'pendiente_aprobacion', 'pendiente_codigo', 'atrasado')), 0)) AS cantidad_disponible
        FROM inventario_equipos e WHERE e.id = ? FOR UPDATE
    ");
    $stmtEq->execute([$equipo_id]);
    $equipo = $stmtEq->fetch();

    if (!$equipo) {
        throw new Exception('Equipo no encontrado.');
    }
    
    if (in_array($equipo['estado'], ['mantenimiento', 'no_disponible'])) {
        throw new Exception('El equipo no se encuentra disponible actualmente (' . $equipo['estado'] . ').');
    }
    
    if ($equipo['cantidad_disponible'] < $cantidad) {
        throw new Exception('Stock insuficiente. Quedan ' . $equipo['cantidad_disponible'] . ' disponibles.');
    }

    // Insertar solicitud (Estado inicial: pendiente_codigo)
    $stmtReq = $pdo->prepare("
        INSERT INTO inventario_prestamos 
        (equipo_id, usuario_id, cantidad, fecha_devolucion_esperada, estado) 
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), 'pendiente_codigo')
    ");
    $stmtReq->execute([$equipo_id, $_SESSION['user_id'], $cantidad, $dias]);

    $pdo->commit();

    // Obtener el nombre del equipo para el correo
    $stmtNomEq = $pdo->prepare("SELECT nombre FROM inventario_equipos WHERE id = ?");
    $stmtNomEq->execute([$equipo_id]);
    $nombreEq = $stmtNomEq->fetchColumn();

    // Enviar correo al administrador
    $to = 'ce4vjp@gmail.com';
    $subject = "Nueva Solicitud de Préstamo - " . $_SESSION['user_nombre'];
    $message = "Hola Administrador,\n\n"
             . "El usuario " . $_SESSION['user_nombre'] . " ha solicitado un préstamo.\n\n"
             . "Equipo solicitado: " . ($nombreEq ?: "ID $equipo_id") . "\n"
             . "Días requeridos: $dias\n\n"
             . "Por favor revisa el panel de administración o espera a que el directivo genere el código de autorización.";
             
    $headers = "From: no-reply@liceotpggm.cl\r\n" .
               "Reply-To: no-reply@liceotpggm.cl\r\n" .
               "X-Mailer: PHP/" . phpversion();

    $mailEnviado = @mail($to, $subject, $message, $headers);

    echo json_encode(['success' => true, 'mail_enviado' => $mailEnviado]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

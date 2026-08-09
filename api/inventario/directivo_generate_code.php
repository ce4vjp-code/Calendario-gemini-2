<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], ['admin', 'directivo'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$prestamo_id = $data['prestamo_id'] ?? null;

if (!$prestamo_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de préstamo obligatorio']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtCheck = $pdo->prepare("SELECT estado FROM inventario_prestamos WHERE id = ? FOR UPDATE");
    $stmtCheck->execute([$prestamo_id]);
    $estado = $stmtCheck->fetchColumn();

    if ($estado !== 'pendiente_codigo') {
        throw new Exception('El préstamo ya no está pendiente de código.');
    }

    // Generar código de 6 caracteres alfanuméricos
    $codigo = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    $stmtUp = $pdo->prepare("
        UPDATE inventario_prestamos 
        SET codigo_aprobacion = ?, estado = 'pendiente_aprobacion', directivo_id = ?
        WHERE id = ?
    ");
    $stmtUp->execute([$codigo, $_SESSION['user_id'], $prestamo_id]);

    $pdo->commit();

    // Obtener detalles para el correo
    $stmtDet = $pdo->prepare("
        SELECT u.nombre as solicitante, e.nombre as equipo 
        FROM inventario_prestamos p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN inventario_equipos e ON p.equipo_id = e.id
        WHERE p.id = ?
    ");
    $stmtDet->execute([$prestamo_id]);
    $detalles = $stmtDet->fetch(PDO::FETCH_ASSOC);

    $solicitante = $detalles ? $detalles['solicitante'] : 'Usuario';
    $equipo = $detalles ? $detalles['equipo'] : 'Equipo';
    $directivo = $_SESSION['user_nombre'] ?? 'Directivo';

    // Enviar correo al administrador
    $to = 'ce4vjp@gmail.com';
    $subject = "Nuevo Código de Autorización - Préstamo de Equipo";
    $message = "Hola Administrador,\n\n"
             . "El directivo $directivo ha autorizado un préstamo y generado el siguiente código.\n\n"
             . "Solicitante: $solicitante\n"
             . "Equipo solicitado: $equipo\n"
             . "CÓDIGO DE APROBACIÓN: $codigo\n\n"
             . "Ingresa este código en el panel de administración de inventario para hacer la entrega del equipo.";
             
    $headers = "From: no-reply@liceotpggm.cl\r\n" .
               "Reply-To: no-reply@liceotpggm.cl\r\n" .
               "X-Mailer: PHP/" . phpversion();

    $mailEnviado = @mail($to, $subject, $message, $headers);

    echo json_encode(['success' => true, 'codigo' => $codigo, 'mail_enviado' => $mailEnviado]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

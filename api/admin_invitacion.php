<?php
require_once '../config.php';
header('Content-Type: application/json');

// Solo administradores pueden enviar invitaciones
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos para realizar esta acción']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Correo electrónico inválido']);
    exit;
}

// Generar código único aleatorio
$codigo = 'PROFE-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

try {
    $stmt = $pdo->prepare("INSERT INTO invitaciones (codigo_unico, email_destino) VALUES (?, ?)");
    $stmt->execute([$codigo, $email]);

    // Registrar en auditoría
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], 'INVITACIONES', 'GENERAR_INVITACION', "Se generó el código $codigo para $email", $ip]);

    // Intentar enviar el correo (depende de configuración del servidor)
    $asunto = "Invitación al Calendario de Evaluaciones";
    // Obtener la URL base dinámicamente
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $uri = dirname($_SERVER['REQUEST_URI']);
    // Quitar '/api' del final si está presente
    $uri = preg_replace('/\/api$/', '', $uri);
    $base_url = $protocol . "://" . $host . $uri;
    
    $link_registro = $base_url . "/registro.html";

    $mensaje = "Hola,\n\nHas sido invitado para registrarte como profesor en el Calendario de Evaluaciones.\n";
    $mensaje .= "Tu código de invitación es: " . $codigo . "\n\n";
    $mensaje .= "Puedes registrarte ingresando al siguiente enlace:\n";
    $mensaje .= $link_registro . "\n";
    $cabeceras = "From: no-reply@liceotpggm.cl" . "\r\n" .
                 "X-Mailer: PHP/" . phpversion();

    $mailEnviado = @mail($email, $asunto, $mensaje, $cabeceras);

    echo json_encode([
        'success' => true, 
        'codigo' => $codigo, 
        'mailEnviado' => $mailEnviado,
        'message' => 'Invitación generada. ' . ($mailEnviado ? 'Correo enviado.' : 'Asegúrate de configurar el servidor de correos (SMTP) para enviar automáticamente. El código generado es: '.$codigo)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al generar la invitación']);
}
?>

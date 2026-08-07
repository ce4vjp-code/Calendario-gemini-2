<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID no proporcionado']);
    exit;
}

try {
    // Obtener email
    $stmtUser = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE id = ?");
    $stmtUser->execute([$id]);
    $user = $stmtUser->fetch();

    if (!$user) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }

    // Generar clave aleatoria (8 caracteres)
    $nueva_clave = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $hash = password_hash($nueva_clave, PASSWORD_BCRYPT);

    // Actualizar
    $stmtUp = $pdo->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
    $stmtUp->execute([$hash, $id]);

    // Registrar en auditoría
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], 'USUARIOS', 'RESET_CLAVE', 'Se reseteó la contraseña de: ' . $user['nombre'], $ip]);

    // Enviar correo
    $mailEnviado = false;
    if (!empty($user['email'])) {
        $asunto = "Restablecimiento de Contraseña - Calendario de Evaluaciones";
        $mensaje = "Hola " . $user['nombre'] . ",\n\n";
        $mensaje .= "Tu contraseña ha sido restablecida por el administrador.\n";
        $mensaje .= "Tu nueva contraseña es: " . $nueva_clave . "\n\n";
        $mensaje .= "Te recomendamos iniciar sesión y guardarla en un lugar seguro.\n";
        $cabeceras = "From: no-reply@liceotpggm.cl\r\n" . "X-Mailer: PHP/" . phpversion();

        $mailEnviado = @mail($user['email'], $asunto, $mensaje, $cabeceras);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Contraseña restablecida exitosamente.',
        'nueva_clave' => $nueva_clave, // Se devuelve para mostrarla en pantalla por si falla el correo
        'mailEnviado' => $mailEnviado
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al restablecer la contraseña']);
}
?>

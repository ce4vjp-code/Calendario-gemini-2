<?php
require_once '../config.php';
header('Content-Type: application/json');

// Verificar que el usuario sea administrador
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Solo administradores.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;
$email = $data['email'] ?? '';

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de usuario no proporcionado.']);
    exit;
}

// Validar formato de correo (permitir vacío para borrar)
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El formato del correo electrónico no es válido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET email = ? WHERE id = ?");
    // Si el email está vacío, lo guardamos como NULL para mantener consistencia
    $stmt->execute([empty($email) ? null : $email, $id]);
    
    if ($stmt->rowCount() > 0 || $stmt->errorCode() === '00000') {
        // Obtener nombre para el log
        $stmtName = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
        $stmtName->execute([$id]);
        $nombreProfe = $stmtName->fetchColumn() ?: 'Desconocido';

        // Registrar en auditoría
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], 'USUARIOS', 'EDITAR_EMAIL', "Se modificó el correo de $nombreProfe", $ip]);

        echo json_encode(['success' => true, 'message' => 'Correo actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo actualizar el correo. (Puede que no haya cambios)']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor al actualizar el correo.']);
}
?>

<?php
require_once '../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$rut = trim(htmlspecialchars($data['rut'] ?? '', ENT_QUOTES, 'UTF-8'));
$nombre = trim(htmlspecialchars($data['nombre'] ?? '', ENT_QUOTES, 'UTF-8'));
$clave = $data['clave'] ?? '';
$codigo = trim($data['codigo'] ?? '');

if (empty($rut) || empty($nombre) || empty($clave) || empty($codigo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

if (strlen($clave) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

function validaRut($rut) {
    if (strtolower($rut) === 'admin') return true;
    $rut = preg_replace('/[^kK0-9]/i', '', $rut);
    if (strlen($rut) < 2) return false;
    $dv = substr($rut, -1);
    $numero = substr($rut, 0, strlen($rut) - 1);
    $i = 2;
    $suma = 0;
    foreach(array_reverse(str_split($numero)) as $v) {
        if($i == 8) $i = 2;
        $suma += $v * $i;
        ++$i;
    }
    $dvr = 11 - ($suma % 11);
    if ($dvr == 11) $dvr = 0;
    if ($dvr == 10) $dvr = 'K';
    return strtoupper($dv) == strtoupper($dvr);
}

if (!validaRut($rut)) {
    http_response_code(400);
    echo json_encode(['error' => 'El RUT ingresado no es válido (Rechazado por el servidor)']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Actualización atómica para evitar Race Condition
    $stmtInv = $pdo->prepare("UPDATE invitaciones SET usado = 1 WHERE codigo_unico = ? AND usado = 0");
    $stmtInv->execute([$codigo]);

    if ($stmtInv->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Código de invitación inválido o ya usado']);
        exit;
    }

    // Obtener el email para el registro
    $stmtGetEmail = $pdo->prepare("SELECT email_destino FROM invitaciones WHERE codigo_unico = ?");
    $stmtGetEmail->execute([$codigo]);
    $email_destino = $stmtGetEmail->fetchColumn();

    // Encriptar clave
    $hash = password_hash($clave, PASSWORD_BCRYPT);

    // Crear usuario
    $stmtUser = $pdo->prepare("INSERT INTO usuarios (rut, nombre, email, clave, rol) VALUES (?, ?, ?, ?, 'profesor')");
    $stmtUser->execute([$rut, $nombre, $email_destino, $hash]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Usuario registrado exitosamente']);

} catch (PDOException $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    // Error 1062 es entrada duplicada en MySQL
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        echo json_encode(['error' => 'El RUT ya está registrado']);
    } else {
        error_log("Error en registro: " . $e->getMessage());
        echo json_encode(['error' => 'Error interno del servidor al registrar usuario.']);
    }
}
?>

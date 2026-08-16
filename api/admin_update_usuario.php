<?php
require_once '../config.php';
header('Content-Type: application/json');

// Verificar permisos de administrador
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Solo administradores.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? (int)$data['id'] : null;
$nombre = trim($data['nombre'] ?? '');
$rut = trim($data['rut'] ?? '');
$email = trim($data['email'] ?? '');
$rol = trim($data['rol'] ?? 'profesor');
$puede_pedir_equipos = isset($data['puede_pedir_equipos']) ? (int)$data['puede_pedir_equipos'] : 0;
$nueva_clave = trim($data['nueva_clave'] ?? '');
$quitar_2fa = !empty($data['quitar_2fa']) || !empty($data['desactivar_2fa']);

if (!$id || empty($nombre) || empty($rut)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nombre, RUT e ID son campos obligatorios.']);
    exit;
}

// Función de validación de RUT
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
    echo json_encode(['success' => false, 'error' => 'El RUT ingresado no es válido.']);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El formato del correo electrónico no es válido.']);
    exit;
}

$allowed_roles = ['admin', 'profesor', 'auxiliar', 'asistente_educacion', 'externo', 'directivo', 'inventario'];
if (!in_array($rol, $allowed_roles)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El rol seleccionado no es válido.']);
    exit;
}

// Prevenir que el admin actual se quite su propio rol de admin
if ($id === (int)$_SESSION['user_id'] && $rol !== 'admin') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No puedes quitarte el rol de administrador a ti mismo.']);
    exit;
}

try {
    // 1. Verificar si el RUT ya está en uso por otro usuario
    $stmtCheckRut = $pdo->prepare("SELECT id FROM usuarios WHERE rut = ? AND id != ?");
    $stmtCheckRut->execute([$rut, $id]);
    if ($stmtCheckRut->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El RUT ingresado ya está asignado a otro usuario.']);
        exit;
    }

    // 2. Obtener datos actuales del usuario
    $stmtUser = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtUser->execute([$id]);
    $currentUser = $stmtUser->fetch();

    if (!$currentUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado.']);
        exit;
    }

    $antiguoNombre = $currentUser['nombre'];

    // 3. Preparar campos a actualizar
    $emailVal = empty($email) ? null : $email;

    if (!empty($nueva_clave)) {
        if (strlen($nueva_clave) < 4) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'La nueva contraseña debe tener al menos 4 caracteres.']);
            exit;
        }
        $claveHash = password_hash($nueva_clave, PASSWORD_BCRYPT);
        
        if ($quitar_2fa) {
            $stmtUp = $pdo->prepare("UPDATE usuarios SET nombre = ?, rut = ?, email = ?, rol = ?, puede_pedir_equipos = ?, clave = ?, is_2fa_enabled = 0, secret_2fa = NULL WHERE id = ?");
            $stmtUp->execute([$nombre, $rut, $emailVal, $rol, $puede_pedir_equipos, $claveHash, $id]);
        } else {
            $stmtUp = $pdo->prepare("UPDATE usuarios SET nombre = ?, rut = ?, email = ?, rol = ?, puede_pedir_equipos = ?, clave = ? WHERE id = ?");
            $stmtUp->execute([$nombre, $rut, $emailVal, $rol, $puede_pedir_equipos, $claveHash, $id]);
        }
    } else {
        if ($quitar_2fa) {
            $stmtUp = $pdo->prepare("UPDATE usuarios SET nombre = ?, rut = ?, email = ?, rol = ?, puede_pedir_equipos = ?, is_2fa_enabled = 0, secret_2fa = NULL WHERE id = ?");
            $stmtUp->execute([$nombre, $rut, $emailVal, $rol, $puede_pedir_equipos, $id]);
        } else {
            $stmtUp = $pdo->prepare("UPDATE usuarios SET nombre = ?, rut = ?, email = ?, rol = ?, puede_pedir_equipos = ? WHERE id = ?");
            $stmtUp->execute([$nombre, $rut, $emailVal, $rol, $puede_pedir_equipos, $id]);
        }
    }

    // Si cambió el nombre del profesor, actualizar evaluaciones asociadas para mantener consistencia
    if ($antiguoNombre !== $nombre) {
        $stmtUpdateEv = $pdo->prepare("UPDATE evaluaciones SET profesor = ? WHERE profesor = ?");
        $stmtUpdateEv->execute([$nombre, $antiguoNombre]);
    }

    // Registrar en auditoría
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $detalles = "Usuario ID $id ($nombre): ";
    $detalles .= ($antiguoNombre !== $nombre ? "Nombre: '$antiguoNombre' -> '$nombre'. " : "");
    $detalles .= "RUT: $rut, Rol: $rol. ";
    if (!empty($nueva_clave)) $detalles .= "Contraseña modificada manualmente. ";
    if ($quitar_2fa) $detalles .= "2FA desactivado. ";

    $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'USUARIOS', 'EDITAR_USUARIO', ?, ?)");
    $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], $detalles, $ip]);

    echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente.']);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error admin_update_usuario: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno al actualizar usuario: ' . $e->getMessage()]);
}
?>

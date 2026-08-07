<?php
require_once '../config.php';
require_once 'totp.class.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token_2fa'] ?? '';
$code = $data['code'] ?? '';

if (empty($token) || empty($code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Token y código son obligatorios']);
    exit;
}

try {
    // Limpiar tokens expirados
    $pdo->exec("DELETE FROM login_2fa_tokens WHERE expires_at < NOW()");

    // Buscar el token
    $stmt = $pdo->prepare("SELECT user_id FROM login_2fa_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow) {
        http_response_code(401);
        echo json_encode(['error' => 'Sesión expirada o inválida. Por favor inicia sesión nuevamente.']);
        exit;
    }

    $user_id = $tokenRow['user_id'];

    // Obtener usuario y su secreto 2FA
    $stmtUser = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch();

    if (!$user || empty($user['secret_2fa'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Error de validación 2FA']);
        exit;
    }

    $ga = new GoogleAuthenticator();
    $checkResult = $ga->verifyCode($user['secret_2fa'], $code, 2); // Permite cierta discrepancia de tiempo (2 * 30 seg = 1 min)

    if ($checkResult) {
        // Código válido
        
        // Eliminar el token de un solo uso
        $stmtDel = $pdo->prepare("DELETE FROM login_2fa_tokens WHERE token = ?");
        $stmtDel->execute([$token]);

        // Registrar login exitoso (Lógica copiada de login.php)
        session_regenerate_id(true);
        $new_session_id = session_id();

        try {
            $stmtUp = $pdo->prepare("UPDATE usuarios SET current_session_id = ? WHERE id = ?");
            $stmtUp->execute([$new_session_id, $user['id']]);
        } catch (Exception $e) {}

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_rut'] = $user['rut'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_rol'] = $user['rol'];
        $_SESSION['user_asignaturas'] = json_decode($user['asignaturas_asignadas'] ?? '[]', true);
        $_SESSION['last_activity'] = time();
        
        session_write_close();
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmtClean = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmtClean->execute([$ip]);
        
        // Registrar el ingreso exitoso en el log
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $browser = "Desconocido"; $device = "Desktop";
        if (preg_match('/mobile/i', $ua)) $device = 'Móvil';
        elseif (preg_match('/tablet/i', $ua)) $device = 'Tablet';
        if (preg_match('/windows nt/i', $ua)) $device .= ' (Windows)';
        elseif (preg_match('/mac os x/i', $ua)) $device .= ' (Mac)';
        elseif (preg_match('/linux/i', $ua)) $device .= ' (Linux)';
        elseif (preg_match('/android/i', $ua)) $device .= ' (Android)';
        elseif (preg_match('/iphone|ipad/i', $ua)) $device .= ' (iOS)';
        if (preg_match('/edg/i', $ua)) $browser = 'Edge';
        elseif (preg_match('/chrome|crios/i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/firefox|fxios/i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/safari/i', $ua)) $browser = 'Safari';
        elseif (preg_match('/msie|trident/i', $ua)) $browser = 'IE';

        try {
            $stmtLog = $pdo->prepare("INSERT INTO registro_ingresos (rut_ingresado, nombre_usuario, ip_address, estado, navegador, dispositivo) VALUES (?, ?, ?, 'Exitoso', ?, ?)");
            $stmtLog->execute([$user['rut'], $user['nombre'], $ip, $browser, $device]);
        } catch (Exception $e) {}
        
        echo json_encode(['success' => true, 'user' => [
            'nombre' => $user['nombre'],
            'rol' => $user['rol']
        ]]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $browser = "Desconocido"; $device = "Desktop";
        if (preg_match('/mobile/i', $ua)) $device = 'Móvil';
        elseif (preg_match('/tablet/i', $ua)) $device = 'Tablet';
        if (preg_match('/windows nt/i', $ua)) $device .= ' (Windows)';
        elseif (preg_match('/mac os x/i', $ua)) $device .= ' (Mac)';
        elseif (preg_match('/linux/i', $ua)) $device .= ' (Linux)';
        elseif (preg_match('/android/i', $ua)) $device .= ' (Android)';
        elseif (preg_match('/iphone|ipad/i', $ua)) $device .= ' (iOS)';
        if (preg_match('/edg/i', $ua)) $browser = 'Edge';
        elseif (preg_match('/chrome|crios/i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/firefox|fxios/i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/safari/i', $ua)) $browser = 'Safari';
        elseif (preg_match('/msie|trident/i', $ua)) $browser = 'IE';

        try {
            $stmtLog = $pdo->prepare("INSERT INTO registro_ingresos (rut_ingresado, ip_address, estado, navegador, dispositivo) VALUES (?, ?, 'Fallido 2FA', ?, ?)");
            $stmtLog->execute([$user['rut'], $ip, $browser, $device]);
        } catch (Exception $e) {}

        http_response_code(401);
        echo json_encode(['error' => 'El código ingresado es incorrecto']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en verify_2fa: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno en el servidor']);
}
?>

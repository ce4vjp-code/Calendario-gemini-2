<?php
require_once '../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$rut = $data['rut'] ?? '';
$clave = $data['clave'] ?? '';

if (empty($rut) || empty($clave)) {
    http_response_code(400);
    echo json_encode(['error' => 'RUT y clave son obligatorios']);
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
    // 1. Prevención de Fuerza Bruta
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    function parseUA($ua) {
        $browser = "Desconocido";
        $device = "Desktop";
        
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
        
        return [$browser, $device];
    }
    
    list($nav, $disp) = parseUA($ua);

    // Limpiar intentos viejos (más de 15 minutos)
    $pdo->exec("DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 15 MINUTE");
    
    // Contar intentos fallidos actuales de la IP
    $stmtLimit = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ?");
    $stmtLimit->execute([$ip]);
    $failedAttempts = $stmtLimit->fetchColumn();

    if ($failedAttempts >= 5) {
        http_response_code(429); // Too Many Requests
        echo json_encode(['error' => 'Demasiados intentos fallidos. Por seguridad, espera 15 minutos e intenta nuevamente.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rut = ?");
    $stmt->execute([$rut]);
    $user = $stmt->fetch();

    if ($user && password_verify($clave, $user['clave'])) {
        $is_2fa_enabled = isset($user['is_2fa_enabled']) && $user['is_2fa_enabled'] == 1;

        if ($is_2fa_enabled) {
            // Generar token temporal para el paso 2
            $token_2fa = md5(uniqid(mt_rand(), true));
            // Intentar crear la tabla si no existe de forma silenciosa para evitar fallos si no corrieron el parche
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS login_2fa_tokens (token VARCHAR(128) PRIMARY KEY, user_id INT(11) NOT NULL, expires_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                // Limpiar tokens viejos
                $pdo->exec("DELETE FROM login_2fa_tokens WHERE expires_at < NOW()");
                $stmtToken = $pdo->prepare("INSERT INTO login_2fa_tokens (token, user_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
                $stmtToken->execute([$token_2fa, $user['id']]);
            } catch (Exception $e) {}

            echo json_encode(['success' => true, 'require_2fa' => true, 'token_2fa' => $token_2fa]);
            exit;
        }

        // Regenerar ID para prevenir fijación de sesión
        session_regenerate_id(true);
        $new_session_id = session_id();

        // Guardar el nuevo ID de sesión en la BD para invalidar sesiones antiguas
        try {
            $stmtUp = $pdo->prepare("UPDATE usuarios SET current_session_id = ? WHERE id = ?");
            $stmtUp->execute([$new_session_id, $user['id']]);
        } catch (Exception $e) {
            // Ignorar si la columna no existe aún
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_rut'] = $user['rut'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_rol'] = $user['rol'];
        $_SESSION['user_asignaturas'] = json_decode($user['asignaturas_asignadas'] ?? '[]', true);
        $_SESSION['last_activity'] = time(); // Registrar hora de inicio
        
        session_write_close();
        
        // Limpiar intentos fallidos de esta IP al lograr éxito
        $stmtClean = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmtClean->execute([$ip]);
        
        // Registrar el ingreso exitoso en el log
        try {
            $stmtLog = $pdo->prepare("INSERT INTO registro_ingresos (rut_ingresado, nombre_usuario, ip_address, estado, navegador, dispositivo) VALUES (?, ?, ?, 'Exitoso', ?, ?)");
            $stmtLog->execute([$user['rut'], $user['nombre'], $ip, $nav, $disp]);
        } catch (Exception $e) {}
        
        echo json_encode(['success' => true, 'user' => [
            'nombre' => $user['nombre'],
            'rol' => $user['rol']
        ]]);
    } else {
        // Registrar intento fallido
        $stmtFail = $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
        $stmtFail->execute([$ip]);

        // Registrar el ingreso fallido en el log
        try {
            $stmtLog = $pdo->prepare("INSERT INTO registro_ingresos (rut_ingresado, ip_address, estado, navegador, dispositivo) VALUES (?, ?, 'Fallido', ?, ?)");
            $stmtLog->execute([$rut, $ip, $nav, $disp]);
        } catch (Exception $e) {}

        http_response_code(401);
        echo json_encode(['error' => 'RUT o contraseña no válidas']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en login: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno en el servidor']);
}
?>

<?php
require_once '../config.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
require_once 'totp.class.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$code = $data['code'] ?? '';

if (empty($code)) {
    http_response_code(400);
    echo json_encode(['error' => 'El código es obligatorio']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Obtener el secreto del usuario
    $stmt = $pdo->prepare("SELECT secret_2fa FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || empty($user['secret_2fa'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No se ha configurado un secreto 2FA.']);
        exit;
    }

    $ga = new GoogleAuthenticator();
    $checkResult = $ga->verifyCode($user['secret_2fa'], $code, 2);

    if ($checkResult) {
        // Habilitar 2FA
        $stmtEnable = $pdo->prepare("UPDATE usuarios SET is_2fa_enabled = 1 WHERE id = ?");
        $stmtEnable->execute([$user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Autenticación de Dos Factores activada exitosamente.']);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'El código ingresado es incorrecto.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al habilitar 2FA']);
}
?>

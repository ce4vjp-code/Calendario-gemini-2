<?php
require_once '../config.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
require_once 'totp.class.php';
header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    // Generar un nuevo secreto
    $ga = new GoogleAuthenticator();
    $secret = $ga->createSecret();
    
    // Guardarlo en la BD temporalmente o permanentemente (pero aún no habilitado)
    $stmt = $pdo->prepare("UPDATE usuarios SET secret_2fa = ? WHERE id = ?");
    $stmt->execute([$secret, $user_id]);
    
    $user_rut = $_SESSION['user_rut'] ?? 'Usuario';
    $issuer = 'CalendarioLiceo';
    $qrCodeUrl = "otpauth://totp/" . urlencode($issuer . ":" . $user_rut) . "?secret=" . $secret . "&issuer=" . urlencode($issuer);
    
    // Usar un servicio externo simple para generar la imagen del QR
    $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrCodeUrl);

    echo json_encode([
        'success' => true, 
        'secret' => $secret, 
        'qr_url' => $qrImageUrl
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al configurar 2FA']);
}
?>

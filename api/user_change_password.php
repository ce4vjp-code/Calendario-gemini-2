<?php
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "No autorizado."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$current_password = isset($input['current_password']) ? $input['current_password'] : '';
$new_password = isset($input['new_password']) ? $input['new_password'] : '';

if (empty($current_password) || empty($new_password)) {
    echo json_encode(["success" => false, "error" => "Todos los campos son obligatorios."]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, clave FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "error" => "Usuario no encontrado."]);
        exit;
    }

    if (!password_verify($current_password, $user['clave'])) {
        echo json_encode(["success" => false, "error" => "La contraseña actual es incorrecta."]);
        exit;
    }

    // Hash new password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $update_stmt = $pdo->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
    $update_stmt->execute([$new_hash, $user_id]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Error en la base de datos."]);
}
?>

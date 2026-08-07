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
    $pdo->beginTransaction();
    
    // Obtener nombre del usuario para borrar sus evaluaciones
    $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ? AND rol != 'admin'");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user) {
        // Borrar evaluaciones del profesor
        $stmtEv = $pdo->prepare("DELETE FROM evaluaciones WHERE profesor = ?");
        $stmtEv->execute([$user['nombre']]);

        // Borrar usuario
        $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmtDel->execute([$id]);

        // Registrar en auditoría
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'], $_SESSION['user_nombre'], 'USUARIOS', 'ELIMINAR_USUARIO', 'Se eliminó al profesor: ' . $user['nombre'], $ip]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Usuario y sus evaluaciones eliminados correctamente']);
    } else {
        $pdo->rollBack();
        echo json_encode(['error' => 'No se pudo eliminar el usuario (no existe o es administrador)']);
    }
} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar usuario']);
}
?>

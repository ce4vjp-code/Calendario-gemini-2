<?php
require_once '../config.php';
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    // 1. Control de Inactividad (Timeout = 30 minutos = 1800 segundos)
    $timeout_duration = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        echo json_encode(['authenticated' => false, 'reason' => 'timeout']);
        exit;
    }

    // 2. Control de Sesiones Concurrentes
    try {
        $stmt = $pdo->prepare("SELECT current_session_id FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $db_session_id = $stmt->fetchColumn();
        
        // Si hay un ID en la BD y no coincide con el navegador actual, expulsar
        if ($db_session_id && $db_session_id !== session_id()) {
            session_unset();
            session_destroy();
            echo json_encode(['authenticated' => false, 'reason' => 'concurrent']);
            exit;
        }
    } catch (Exception $e) {
        // Ignorar si el parche de la BD aún no se ha aplicado
    }

    // Actualizar última actividad
    $_SESSION['last_activity'] = time();

    // Refrescar asignaturas asignadas y permisos en tiempo real
    $puede_pedir = 0;
    try {
        $stmtAsig = $pdo->prepare("SELECT asignaturas_asignadas, puede_pedir_equipos FROM usuarios WHERE id = ?");
        $stmtAsig->execute([$_SESSION['user_id']]);
        $row = $stmtAsig->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['user_asignaturas'] = json_decode($row['asignaturas_asignadas'] ?? '[]', true) ?: [];
            $puede_pedir = (int)($row['puede_pedir_equipos'] ?? 0);
        } else {
            if (!isset($_SESSION['user_asignaturas'])) $_SESSION['user_asignaturas'] = [];
        }
    } catch (Exception $e) {
        // Fallback a lo que haya en la sesión
        if (!isset($_SESSION['user_asignaturas'])) $_SESSION['user_asignaturas'] = [];
    }

    echo json_encode([
        'authenticated' => true,
        'user' => [
            'nombre' => $_SESSION['user_nombre'],
            'rol' => $_SESSION['user_rol'],
            'asignaturas_asignadas' => $_SESSION['user_asignaturas'],
            'puede_pedir_equipos' => $puede_pedir
        ]
    ]);
} else {
    echo json_encode(['authenticated' => false]);
}
?>

<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';
$id = $data['id'] ?? null;
$codigo = trim($data['codigo'] ?? '');

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de préstamo obligatorio']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Obtener info actual del préstamo
    $stmtGet = $pdo->prepare("SELECT * FROM inventario_prestamos WHERE id = ? FOR UPDATE");
    $stmtGet->execute([$id]);
    $prestamo = $stmtGet->fetch();
    
    if (!$prestamo) throw new Exception('Préstamo no encontrado');

    if ($action === 'approve') {
        if ($prestamo['estado'] !== 'pendiente_aprobacion') throw new Exception('El préstamo no está pendiente de aprobación.');
        if (empty($codigo)) throw new Exception('El código de aprobación es obligatorio.');
        
        // Verificar código
        if ($prestamo['codigo_aprobacion'] !== $codigo) {
            throw new Exception('Código de aprobación incorrecto.');
        }

        // Aprobar préstamo
        $stmtUp = $pdo->prepare("UPDATE inventario_prestamos SET estado = 'prestado' WHERE id = ?");
        $stmtUp->execute([$id]);
        
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Préstamos', 'Aprobar Préstamo', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'] ?? '', $_SESSION['user_nombre'] ?? '', "Aprobado con código. ID Préstamo: $id", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'direct_approve') {
        if (!in_array($prestamo['estado'], ['pendiente_aprobacion', 'pendiente_codigo'])) {
            throw new Exception('El préstamo no está pendiente.');
        }

        // Aprobar directamente sin requerir código
        $stmtUp = $pdo->prepare("UPDATE inventario_prestamos SET estado = 'prestado', codigo_aprobacion = 'ADMIN', directivo_id = ? WHERE id = ?");
        $stmtUp->execute([$_SESSION['user_id'], $id]);
        
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Préstamos', 'Aprobar Préstamo', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'] ?? '', $_SESSION['user_nombre'] ?? '', "Aprobado directamente (Admin). ID Préstamo: $id", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'reject') {
        $stmtUp = $pdo->prepare("UPDATE inventario_prestamos SET estado = 'rechazado' WHERE id = ?");
        $stmtUp->execute([$id]);
        
        // Fetch user email and equipment name to send notification
        $stmtInfo = $pdo->prepare("
            SELECT u.email, u.nombre as usuario_nombre, e.nombre as equipo_nombre 
            FROM inventario_prestamos p
            JOIN usuarios u ON p.usuario_id = u.id
            JOIN inventario_equipos e ON p.equipo_id = e.id
            WHERE p.id = ?
        ");
        $stmtInfo->execute([$id]);
        $info = $stmtInfo->fetch();
        
        if ($info && !empty($info['email'])) {
            $to = $info['email'];
            $subject = "Solicitud de Préstamo Rechazada - Liceo TPGGM";
            $message = "Hola " . $info['usuario_nombre'] . ",\n\n"
                     . "Te informamos que tu solicitud de préstamo para el equipo '" . $info['equipo_nombre'] . "' ha sido rechazada por el administrador.\n\n"
                     . "Si tienes dudas, por favor comunícate con el Departamento de Informática.\n\n"
                     . "Saludos,\nSistema de Inventario.";
                     
            $headers = "From: no-reply@liceotpggm.cl\r\n" .
                       "Reply-To: no-reply@liceotpggm.cl\r\n" .
                       "Bcc: ce4vjp@gmail.com\r\n" .
                       "X-Mailer: PHP/" . phpversion();
                       
            @mail($to, $subject, $message, $headers);
        }
        
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Préstamos', 'Rechazar Préstamo', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'] ?? '', $_SESSION['user_nombre'] ?? '', "Rechazado. ID Préstamo: $id", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'return') {
        if (!in_array($prestamo['estado'], ['prestado', 'atrasado'])) {
            throw new Exception('El préstamo no está activo.');
        }
        
        $stmtUp = $pdo->prepare("UPDATE inventario_prestamos SET estado = 'devuelto', fecha_devolucion_real = NOW() WHERE id = ?");
        $stmtUp->execute([$id]);
        
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Préstamos', 'Devolución Equipo', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'] ?? '', $_SESSION['user_nombre'] ?? '', "Marcado como devuelto. ID Préstamo: $id", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'delete') {
        // Opción de borrado físico del registro (ej. para limpiar historial o errores)
        $stmtDel = $pdo->prepare("DELETE FROM inventario_prestamos WHERE id = ?");
        $stmtDel->execute([$id]);
        
        $stmtLog = $pdo->prepare("INSERT INTO registro_actividades (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) VALUES (?, ?, 'Préstamos', 'Eliminar Préstamo', ?, ?)");
        $stmtLog->execute([$_SESSION['user_rut'] ?? '', $_SESSION['user_nombre'] ?? '', "Eliminado físicamente. ID Préstamo: $id", $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true]);
        
    } else {
        throw new Exception('Acción no válida');
    }
    
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

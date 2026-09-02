<?php
require_once '../../config.php';

// Este script está diseñado para ser ejecutado por un Cron Job diario (ej: cada día a las 8 AM).
// No requiere autenticación porque será llamado internamente por el servidor (cron).
// Sin embargo, si lo llamas por web, podría ejecutarse, lo cual no es peligroso ya que solo actualiza estados y envía correos si hay atrasos.

try {
    $pdo->beginTransaction();

    // 1. Buscar todos los préstamos que estén en estado 'prestado' y cuya fecha límite ya pasó.
    $stmt = $pdo->prepare("
        SELECT p.id, e.nombre AS equipo_nombre, u.nombre AS usuario_nombre, u.email AS usuario_email, p.fecha_devolucion_esperada 
        FROM inventario_prestamos p
        JOIN inventario_equipos e ON p.equipo_id = e.id
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.estado = 'prestado' AND p.fecha_devolucion_esperada < NOW()
    ");
    $stmt->execute();
    $atrasados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($atrasados) > 0) {
        $ids = array_column($atrasados, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // 2. Actualizar el estado a 'atrasado'
        $updateStmt = $pdo->prepare("UPDATE inventario_prestamos SET estado = 'atrasado' WHERE id IN ($placeholders)");
        $updateStmt->execute($ids);

        $pdo->commit();

        // 3. Enviar correo al administrador y usuarios
        $to_admin = 'ce4vjp@gmail.com';
        $subject_admin = "ALERTA: Préstamos Atrasados - Liceo TPGGM";
        $message_admin = "Hola Administrador,\n\n"
                 . "El sistema ha detectado " . count($atrasados) . " préstamo(s) que no ha(n) sido devuelto(s) en la fecha acordada.\n\n"
                 . "Detalle de los equipos atrasados:\n";

        $headers = "From: no-reply@liceotpggm.cl\r\n" .
                   "Reply-To: no-reply@liceotpggm.cl\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        foreach ($atrasados as $p) {
            $message_admin .= "- Equipo: " . $p['equipo_nombre'] . " | Usuario: " . $p['usuario_nombre'] . " | Debió entregarse: " . $p['fecha_devolucion_esperada'] . "\n";
            
            // Enviar correo al usuario si tiene email
            if (!empty($p['usuario_email'])) {
                $subject_user = "AVISO: Préstamo Atrasado - " . $p['equipo_nombre'];
                $message_user = "Hola " . $p['usuario_nombre'] . ",\n\n"
                              . "Te recordamos que el plazo para devolver el equipo '" . $p['equipo_nombre'] . "' venció el " . $p['fecha_devolucion_esperada'] . ".\n\n"
                              . "Por favor, acércate a la administración para realizar la devolución lo antes posible.\n\n"
                              . "Saludos,\nAdministración de Inventario.";
                @mail($p['usuario_email'], $subject_user, $message_user, $headers);
            }
        }

        $message_admin .= "\nPor favor revisa el panel de inventario para gestionar las devoluciones.\n\nSaludos,\nSistema de Inventario.";
        @mail($to_admin, $subject_admin, $message_admin, $headers);
        
        echo "Cron ejecutado exitosamente. Se encontraron y notificaron " . count($atrasados) . " préstamos atrasados.\n";
    } else {
        $pdo->commit();
        echo "Cron ejecutado exitosamente. No hay préstamos atrasados nuevos.\n";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en cron_check_prestamos.php: " . $e->getMessage());
    echo "Error ejecutando el cron.\n";
}
?>

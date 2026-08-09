<?php
require_once '../../config.php';

// Este script está diseñado para ser ejecutado por un Cron Job diario (ej: cada día a las 8 AM).
// No requiere autenticación porque será llamado internamente por el servidor (cron).
// Sin embargo, si lo llamas por web, podría ejecutarse, lo cual no es peligroso ya que solo actualiza estados y envía correos si hay atrasos.

try {
    $pdo->beginTransaction();

    // 1. Buscar todos los préstamos que estén en estado 'prestado' y cuya fecha límite ya pasó.
    $stmt = $pdo->prepare("
        SELECT p.id, e.nombre AS equipo_nombre, u.nombre AS usuario_nombre, p.fecha_devolucion_esperada 
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

        // 3. Enviar correo al administrador
        $to = 'ce4vjp@gmail.com';
        $subject = "ALERTA: Préstamos Atrasados - Liceo TPGGM";
        $message = "Hola Administrador,\n\n"
                 . "El sistema ha detectado " . count($atrasados) . " préstamo(s) que no ha(n) sido devuelto(s) en la fecha acordada.\n\n"
                 . "Detalle de los equipos atrasados:\n";

        foreach ($atrasados as $p) {
            $message .= "- Equipo: " . $p['equipo_nombre'] . " | Usuario: " . $p['usuario_nombre'] . " | Debió entregarse: " . $p['fecha_devolucion_esperada'] . "\n";
        }

        $message .= "\nPor favor revisa el panel de inventario para gestionar las devoluciones.\n\nSaludos,\nSistema de Inventario.";

        $headers = "From: no-reply@liceotpggm.cl\r\n" .
                   "Reply-To: no-reply@liceotpggm.cl\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        @mail($to, $subject, $message, $headers);
        
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

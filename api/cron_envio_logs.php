<?php
require_once __DIR__ . '/../config.php';

// Solo permitir ejecución si es desde consola (cron) o si el admin está logueado
$isCron = (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR']));
$isAdmin = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin');
$manualToken = isset($_GET['token']) && $_GET['token'] === 'log_manual'; // Para peticiones fetch locales

if (!$isCron && !$isAdmin && !$manualToken) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

try {
    // 1. Determinar la fecha a consultar (Si está vacía, mostrar todo)
    $fechaConsulta = null; 
    
    if (isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha'])) {
        $fechaConsulta = $_GET['fecha'];
    } elseif ($isCron) {
        $fechaConsulta = date('Y-m-d'); // El Cron diario sí o sí muestra lo de hoy
    }
    // Si viene por $_GET['fecha'] pero está vacío, $fechaConsulta queda en null (Registro Completo)

    // Obtener registros (Ingresos)
    if ($fechaConsulta) {
        $stmt = $pdo->prepare("SELECT * FROM registro_ingresos WHERE DATE(fecha_hora) = ? ORDER BY fecha_hora DESC");
        $stmt->execute([$fechaConsulta]);
    } else {
        $stmt = $pdo->query("SELECT * FROM registro_ingresos ORDER BY fecha_hora DESC");
    }
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener actividades
    try {
        if ($fechaConsulta) {
            $stmtAct = $pdo->prepare("SELECT * FROM registro_actividades WHERE DATE(fecha_hora) = ? ORDER BY fecha_hora DESC");
            $stmtAct->execute([$fechaConsulta]);
        } else {
            $stmtAct = $pdo->query("SELECT * FROM registro_actividades ORDER BY fecha_hora DESC");
        }
        $actividades = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        $actividades = []; // Por si la tabla no existe aún
    }

    $to = "ce4vjp@gmail.com";
    $strFecha = $fechaConsulta ? date('d/m/Y', strtotime($fechaConsulta)) : 'HISTÓRICO COMPLETO';
    $subject = "Reporte de Auditoría e Ingresos - " . $strFecha;
    
    $message = "<html><head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        table { border-collapse: collapse; width: 100%; max-width: 900px; margin-top: 20px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #0f172a; color: white; }
        .exitoso { color: green; font-weight: bold; }
        .fallido { color: red; font-weight: bold; }
        h2, h3 { color: #0f172a; }
        .badge { display:inline-block; padding: 3px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; background:#e2e8f0; color:#334155; }
    </style>
    </head><body>";
    
    $message .= "<h2>Reporte de Auditoría del Sistema</h2>";
    $message .= "<p>Período del reporte: <strong>" . $strFecha . "</strong></p>";
    $message .= "<p>Generado el: " . date('d/m/Y H:i:s') . "</p>";
    
    // TABLA ACTIVIDADES
    $message .= "<hr><h3>1. Registro de Actividades en Módulos</h3>";
    if (count($actividades) > 0) {
        $message .= "<table>
            <tr>
                <th>Fecha/Hora</th>
                <th>Usuario</th>
                <th>Módulo</th>
                <th>Acción</th>
                <th>Detalles</th>
                <th>IP</th>
            </tr>";
            
        foreach ($actividades as $a) {
            $message .= "<tr>
                <td>" . date('d/m/Y H:i', strtotime($a['fecha_hora'])) . "</td>
                <td><strong>" . htmlspecialchars($a['usuario_nombre']) . "</strong><br><small>" . htmlspecialchars($a['usuario_rut']) . "</small></td>
                <td><span class='badge'>" . htmlspecialchars($a['modulo']) . "</span></td>
                <td>" . htmlspecialchars($a['accion']) . "</td>
                <td>" . htmlspecialchars($a['detalles']) . "</td>
                <td>" . htmlspecialchars($a['ip_address']) . "</td>
            </tr>";
        }
        $message .= "</table>";
    } else {
        $message .= "<p>No se registraron actividades en el período seleccionado.</p>";
    }

    // TABLA INGRESOS
    $message .= "<hr><h3>2. Registro de Inicios de Sesión (Logins)</h3>";
    if (count($logs) > 0) {
        $message .= "<table>
            <tr>
                <th>Fecha/Hora</th>
                <th>RUT</th>
                <th>Nombre</th>
                <th>Estado</th>
                <th>Navegador / Dispositivo</th>
                <th>IP</th>
            </tr>";
            
        foreach ($logs as $log) {
            $estadoClass = strtolower($log['estado']);
            $nombre = $log['nombre_usuario'] ? htmlspecialchars($log['nombre_usuario']) : '<em>No encontrado</em>';
            $navDisp = (!empty($log['navegador']) ? htmlspecialchars($log['navegador']) : 'N/A') . ' / ' . (!empty($log['dispositivo']) ? htmlspecialchars($log['dispositivo']) : 'N/A');

            $message .= "<tr>
                <td>" . date('d/m/Y H:i:s', strtotime($log['fecha_hora'])) . "</td>
                <td>" . htmlspecialchars($log['rut_ingresado']) . "</td>
                <td>" . $nombre . "</td>
                <td class='{$estadoClass}'>" . htmlspecialchars($log['estado']) . "</td>
                <td><small>" . $navDisp . "</small></td>
                <td>" . htmlspecialchars($log['ip_address']) . "</td>
            </tr>";
        }
        $message .= "</table>";
    } else {
        $message .= "<p>No se registraron intentos de ingreso en el período seleccionado.</p>";
    }
    
    $message .= "</body></html>";

    // Cabeceras para enviar HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Plataforma Liceo <noreply@" . $_SERVER['SERVER_NAME'] . ">" . "\r\n";

    if (mail($to, $subject, $message, $headers)) {
        if (!$isCron) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Reporte enviado a ce4vjp@gmail.com exitosamente.']);
        } else {
            echo "Log enviado correctamente.\n";
        }
    } else {
        throw new Exception("La función mail() falló. Verifica la configuración de correo de tu servidor.");
    }

} catch (Exception $e) {
    if (!$isCron) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error al enviar log: ' . $e->getMessage()]);
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>

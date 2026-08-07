<?php
// api/logger.php

/**
 * Función centralizada para registrar actividades de auditoría.
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param string $modulo El módulo afectado (Ej: 'Evaluaciones', 'Encuestas', 'Horarios', 'Diplomas')
 * @param string $accion La acción realizada (Ej: 'Crear', 'Editar', 'Borrar')
 * @param string $detalles Detalles adicionales de la acción (Ej: 'Título de encuesta: Satisfacción')
 */
function registrar_actividad($pdo, $modulo, $accion, $detalles) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $usuario_rut = $_SESSION['user_rut'] ?? 'Desconocido';
    $usuario_nombre = $_SESSION['user_nombre'] ?? 'Sistema/Anónimo';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO registro_actividades 
            (usuario_rut, usuario_nombre, modulo, accion, detalles, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $usuario_rut, 
            $usuario_nombre, 
            $modulo, 
            $accion, 
            $detalles, 
            $ip_address
        ]);
    } catch (PDOException $e) {
        // En caso de fallo silencioso (no detener el flujo de la aplicación)
        error_log("Error al registrar actividad de auditoría: " . $e->getMessage());
    }
}
?>

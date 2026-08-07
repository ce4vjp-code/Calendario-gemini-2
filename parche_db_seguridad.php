<?php
require_once 'config.php';
echo "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>";
echo "<h1 style='color: #4f46e5;'>Parche de Base de Datos (Auditoría de Seguridad)</h1>";

try {
    // 1. Añadir usuario_id a evaluaciones
    $stmt = $pdo->query("SHOW COLUMNS FROM evaluaciones LIKE 'usuario_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE evaluaciones ADD COLUMN usuario_id INT DEFAULT NULL AFTER id");
        echo "<p>✅ Columna 'usuario_id' agregada exitosamente a la tabla 'evaluaciones'.</p>";

        // Migrar datos existentes basados en el nombre del profesor
        $pdo->exec("UPDATE evaluaciones e JOIN usuarios u ON e.profesor = u.nombre SET e.usuario_id = u.id");
        echo "<p>✅ Evaluaciones existentes enlazadas al ID de usuario correspondiente.</p>";
    } else {
        echo "<p>ℹ️ La tabla 'evaluaciones' ya posee la columna 'usuario_id'.</p>";
    }

    // 2. Crear tabla login_attempts para fuerza bruta
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(ip_address),
        INDEX(attempt_time)
    )");
    echo "<p>✅ Tabla 'login_attempts' creada para prevención de fuerza bruta.</p>";

    echo "<p style='color: #166534; background: #dcfce7; padding: 10px; border-radius: 4px;'><strong>¡Parche aplicado con éxito!</strong> El sistema ahora está protegido a nivel de base de datos. Recuerda borrar este archivo por seguridad.</p>";
} catch (Exception $e) {
    echo "<p style='color: #991b1b; background: #fee2e2; padding: 10px; border-radius: 4px;'>❌ Error al aplicar parche: " . $e->getMessage() . "</p>";
}
echo "</div>";
?>

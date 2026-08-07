<?php
require_once 'config.php';
echo "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>";
echo "<h1 style='color: #4f46e5;'>Parche de Seguridad</h1>";

try {
    // Verificar si la columna ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'current_session_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN current_session_id VARCHAR(128) DEFAULT NULL");
        echo "<p style='color: #166534; background: #dcfce7; padding: 10px; border-radius: 4px;'>✅ Columna 'current_session_id' agregada exitosamente.</p>";
    } else {
        echo "<p style='color: #1e40af; background: #dbeafe; padding: 10px; border-radius: 4px;'>ℹ️ La base de datos ya estaba actualizada. No se requiere acción.</p>";
    }
    echo "<p><strong>¡Parche aplicado con éxito!</strong> El sistema de bloqueo de sesiones múltiples ya está activo. Ya puedes cerrar esta ventana.</p>";
} catch (Exception $e) {
    echo "<p style='color: #991b1b; background: #fee2e2; padding: 10px; border-radius: 4px;'>❌ Error al aplicar parche: " . $e->getMessage() . "</p>";
}
echo "</div>";
?>

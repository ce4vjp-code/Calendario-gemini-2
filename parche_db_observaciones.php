<?php
require_once 'config.php';
echo "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>";
echo "<h1 style='color: #4f46e5;'>Parche de Base de Datos (Observaciones)</h1>";

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM evaluaciones LIKE 'observaciones'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE evaluaciones ADD COLUMN observaciones TEXT DEFAULT NULL");
        echo "<p style='color: #166534; background: #dcfce7; padding: 10px; border-radius: 4px;'>✅ Columna 'observaciones' agregada exitosamente a la tabla 'evaluaciones'.</p>";
    } else {
        echo "<p style='color: #1e40af; background: #dbeafe; padding: 10px; border-radius: 4px;'>ℹ️ La base de datos ya estaba actualizada. No se requiere acción.</p>";
    }
    echo "<p><strong>¡Parche aplicado con éxito!</strong> Ya puedes cerrar esta ventana y usar el nuevo campo en la plataforma.</p>";
} catch (Exception $e) {
    echo "<p style='color: #991b1b; background: #fee2e2; padding: 10px; border-radius: 4px;'>❌ Error al aplicar parche: " . $e->getMessage() . "</p>";
}
echo "</div>";
?>

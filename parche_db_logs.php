<?php
require_once 'config.php';

try {
    echo "<h2>Instalando Parche de Logs de Ingreso...</h2>";
    
    // Crear tabla de registro de ingresos
    $sql = "CREATE TABLE IF NOT EXISTS registro_ingresos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rut_ingresado VARCHAR(20) NOT NULL,
        nombre_usuario VARCHAR(100),
        ip_address VARCHAR(45) NOT NULL,
        estado ENUM('Exitoso', 'Fallido') NOT NULL,
        fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "<p style='color:green;'>✅ Tabla 'registro_ingresos' verificada/creada con éxito.</p>";
    
    echo "<br><p><strong>¡El parche se ha instalado correctamente!</strong> Ya puedes cerrar esta ventana.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error en la base de datos: " . $e->getMessage() . "</p>";
}
?>

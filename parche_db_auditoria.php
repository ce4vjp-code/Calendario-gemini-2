<?php
require_once 'config.php';

try {
    echo "<h2>Instalando Parche de Auditoría...</h2>";
    
    // 1. Añadir columnas de navegador y dispositivo a registro_ingresos si no existen
    $sql_check = "SHOW COLUMNS FROM registro_ingresos LIKE 'navegador'";
    $stmt = $pdo->query($sql_check);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE registro_ingresos ADD COLUMN navegador VARCHAR(100) DEFAULT NULL AFTER ip_address");
        $pdo->exec("ALTER TABLE registro_ingresos ADD COLUMN dispositivo VARCHAR(100) DEFAULT NULL AFTER navegador");
        echo "<p style='color:green;'>✔️ Columnas 'navegador' y 'dispositivo' añadidas a registro_ingresos.</p>";
    } else {
        echo "<p style='color:blue;'>ℹ️ Las columnas 'navegador' y 'dispositivo' ya existen.</p>";
    }
    
    // 2. Crear tabla de registro de actividades
    $sql_actividades = "CREATE TABLE IF NOT EXISTS registro_actividades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_rut VARCHAR(20) NOT NULL,
        usuario_nombre VARCHAR(100) NOT NULL,
        modulo VARCHAR(50) NOT NULL,
        accion VARCHAR(50) NOT NULL,
        detalles TEXT,
        ip_address VARCHAR(45) NOT NULL,
        fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql_actividades);
    echo "<p style='color:green;'>✔️ Tabla 'registro_actividades' verificada/creada con éxito.</p>";
    
    echo "<br><p><strong>¡El parche se ha instalado correctamente!</strong></p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error en la base de datos: " . $e->getMessage() . "</p>";
}
?>

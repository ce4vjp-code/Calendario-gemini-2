<?php
require_once 'config.php';

try {
    echo "<h2>Instalando Parche de Asignaturas...</h2>";
    
    $sql_check = "SHOW COLUMNS FROM usuarios LIKE 'asignaturas_asignadas'";
    $stmt = $pdo->query($sql_check);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN asignaturas_asignadas TEXT DEFAULT NULL AFTER rol");
        echo "<p style='color:green;'>✔️ Columna 'asignaturas_asignadas' añadida a usuarios.</p>";
    } else {
        echo "<p style='color:blue;'>ℹ️ La columna 'asignaturas_asignadas' ya existe.</p>";
    }
    
    echo "<br><p><strong>¡El parche se ha instalado correctamente!</strong></p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error en la base de datos: " . $e->getMessage() . "</p>";
}
?>

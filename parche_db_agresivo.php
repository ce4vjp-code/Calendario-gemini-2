<?php
require_once 'config.php';

echo "<h2>Verificación y Parche de Base de Datos</h2>";

try {
    // 1. Mostrar estructura actual
    echo "<h3>Estructura actual de 'encuesta_preguntas':</h3><pre>";
    $stmt = $pdo->query("DESCRIBE encuesta_preguntas");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($cols);
    echo "</pre>";

    // 2. Intentar parchear
    echo "<h3>Aplicando Parche...</h3>";
    $pdo->exec("ALTER TABLE encuesta_preguntas MODIFY COLUMN tipo_pregunta VARCHAR(50) NOT NULL");
    
    echo "<p style='color:green;'>✔️ Comando ALTER ejecutado.</p>";
    
    // 3. Mostrar estructura nueva
    echo "<h3>Estructura NUEVA de 'encuesta_preguntas':</h3><pre>";
    $stmt2 = $pdo->query("DESCRIBE encuesta_preguntas");
    $cols2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    print_r($cols2);
    echo "</pre>";

    echo "<p><strong>¡Completado!</strong> Revisa arriba si 'tipo_pregunta' ahora dice varchar(50).</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error crítico en la base de datos: " . $e->getMessage() . "</p>";
}
?>

<?php
require_once 'config.php';

try {
    echo "<h2>Instalando Parche de Encuestas (Menú Desplegable)...</h2>";
    
    // Cambiar tipo de columna a VARCHAR para asegurar que soporta 'menu_desplegable'
    $pdo->exec("ALTER TABLE encuesta_preguntas MODIFY tipo_pregunta VARCHAR(50) NOT NULL");
    echo "<p style='color:green;'>✔️ Columna 'tipo_pregunta' actualizada a VARCHAR(50) exitosamente.</p>";
    
    echo "<br><p><strong>¡El parche se ha instalado correctamente!</strong> Ya puedes borrar este archivo.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error en la base de datos: " . $e->getMessage() . "</p>";
}
?>

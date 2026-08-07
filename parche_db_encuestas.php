<?php
require_once 'config.php';

echo "<h2>Iniciando instalación del Módulo de Encuestas...</h2>";

try {
    // 1. Tabla de Encuestas
    $sql1 = "CREATE TABLE IF NOT EXISTS encuestas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        descripcion TEXT,
        token_publico VARCHAR(100) NOT NULL UNIQUE,
        estado ENUM('abierta', 'cerrada') DEFAULT 'abierta',
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql1);
    echo "<p>Tabla `encuestas` lista.</p>";

    // 2. Tabla de Preguntas
    $sql2 = "CREATE TABLE IF NOT EXISTS encuesta_preguntas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        encuesta_id INT NOT NULL,
        texto_pregunta TEXT NOT NULL,
        tipo_pregunta ENUM('texto', 'opcion_multiple') NOT NULL,
        opciones JSON DEFAULT NULL,
        orden INT DEFAULT 0,
        FOREIGN KEY (encuesta_id) REFERENCES encuestas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql2);
    echo "<p>Tabla `encuesta_preguntas` lista.</p>";

    // 3. Tabla de Respuestas (Tickets de envío)
    $sql3 = "CREATE TABLE IF NOT EXISTS encuesta_respuestas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        encuesta_id INT NOT NULL,
        fecha_respuesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (encuesta_id) REFERENCES encuestas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql3);
    echo "<p>Tabla `encuesta_respuestas` lista.</p>";

    // 4. Tabla de Detalle de Respuestas
    $sql4 = "CREATE TABLE IF NOT EXISTS encuesta_respuestas_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        respuesta_id INT NOT NULL,
        pregunta_id INT NOT NULL,
        valor_respuesta TEXT,
        FOREIGN KEY (respuesta_id) REFERENCES encuesta_respuestas(id) ON DELETE CASCADE,
        FOREIGN KEY (pregunta_id) REFERENCES encuesta_preguntas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql4);
    echo "<p>Tabla `encuesta_respuestas_detalle` lista.</p>";

    echo "<h3 style='color:green;'>¡Estructura de Encuestas creada exitosamente!</h3>";
    echo "<p>Por motivos de seguridad, borra este archivo (parche_db_encuestas.php) del servidor.</p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>Error al crear tablas de Encuestas:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

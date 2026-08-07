<?php
require_once 'config.php';

echo "<h2>Actualización de Base de Datos: Módulo de Horarios</h2>";

try {
    // 1. Tabla horario_cursos
    $sql1 = "CREATE TABLE IF NOT EXISTS `horario_cursos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nombre` varchar(100) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql1);
    echo "<p>Tabla 'horario_cursos' verificada/creada exitosamente.</p>";

    // 2. Tabla horario_asignaturas
    $sql2 = "CREATE TABLE IF NOT EXISTS `horario_asignaturas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nombre` varchar(100) NOT NULL,
        `color` varchar(20) NOT NULL DEFAULT '#4f46e5',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql2);
    echo "<p>Tabla 'horario_asignaturas' verificada/creada exitosamente.</p>";

    // 3. Tabla horario_clases
    $sql3 = "CREATE TABLE IF NOT EXISTS `horario_clases` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `curso_id` int(11) NOT NULL,
        `asignatura_id` int(11) NOT NULL,
        `dia_semana` tinyint(1) NOT NULL COMMENT '1=Lunes, 2=Martes, 3=Miercoles, 4=Jueves, 5=Viernes',
        `bloque` tinyint(1) NOT NULL COMMENT '1 al 8',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_clase` (`curso_id`, `dia_semana`, `bloque`),
        CONSTRAINT `fk_horario_curso` FOREIGN KEY (`curso_id`) REFERENCES `horario_cursos` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_horario_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `horario_asignaturas` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql3);
    echo "<p>Tabla 'horario_clases' verificada/creada exitosamente.</p>";

    echo "<h3 style='color: green;'>¡Parche aplicado correctamente! Las tablas para los horarios están listas.</h3>";
    echo "<p>Por razones de seguridad, se recomienda eliminar este archivo ('parche_db_horarios.php') del servidor tras su ejecución.</p>";
    echo "<br><a href='index.html'>Volver al inicio</a>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Error al aplicar el parche:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

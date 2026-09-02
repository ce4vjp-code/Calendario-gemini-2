<?php
require_once '../../config.php';

// Verificar permisos, idealmente solo admin.
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso denegado. Solo administradores pueden ejecutar migraciones.");
}

echo "<h2>Migración de Base de Datos: Módulo de Inventario V2</h2>";

try {
    $pdo->beginTransaction();

    // 1. Agregar nuevas columnas a inventario_equipos si no existen
    $columns = [
        'categoria' => "VARCHAR(100) DEFAULT 'General'",
        'imagen_url' => "VARCHAR(255) NULL",
        'ubicacion' => "VARCHAR(100) NULL",
        'codigo_qr' => "VARCHAR(255) NULL"
    ];

    foreach ($columns as $col => $definition) {
        try {
            $pdo->exec("ALTER TABLE inventario_equipos ADD COLUMN $col $definition");
            echo "<p style='color:green;'>Columna <b>$col</b> añadida correctamente a inventario_equipos.</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false || strpos($e->getMessage(), '1060') !== false) {
                echo "<p style='color:orange;'>La columna <b>$col</b> ya existe en inventario_equipos. Saltando...</p>";
            } else {
                throw $e;
            }
        }
    }

    // 2. Crear tabla inventario_mantenimiento
    $sqlMantenimiento = "
        CREATE TABLE IF NOT EXISTS inventario_mantenimiento (
            id INT AUTO_INCREMENT PRIMARY KEY,
            equipo_id INT NOT NULL,
            fecha DATE NOT NULL,
            descripcion TEXT NOT NULL,
            costo DECIMAL(10,2) DEFAULT 0.00,
            registrado_por INT NULL,
            fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (equipo_id) REFERENCES inventario_equipos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sqlMantenimiento);
    echo "<p style='color:green;'>Tabla <b>inventario_mantenimiento</b> comprobada/creada correctamente.</p>";

    // Crear directorio para las imágenes si no existe
    $dir = __DIR__ . '/../../uploads/equipos';
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p style='color:green;'>Directorio de imágenes creado en uploads/equipos.</p>";
        } else {
            echo "<p style='color:red;'>No se pudo crear el directorio de imágenes uploads/equipos. Por favor, créalo manualmente con permisos de escritura.</p>";
        }
    }

    $pdo->commit();
    echo "<h3 style='color:green;'>Migración completada con éxito.</h3>";
    echo "<a href='../../admin_inventario.html'>Volver al Admin de Inventario</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h3 style='color:red;'>Error durante la migración:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<?php
// parche_db_inventario_v3.php
require_once __DIR__ . '/config.php';

try {
    echo "Iniciando actualización de esquema de base de datos...\n";

    // 1. Verificar columnas en inventario_equipos
    $colsInventario = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM inventario_equipos");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $colsInventario[] = strtolower($row['Field']);
    }

    if (!in_array('ubicacion', $colsInventario)) {
        $pdo->exec("ALTER TABLE inventario_equipos ADD COLUMN ubicacion VARCHAR(255) NOT NULL DEFAULT '' AFTER numero_serie");
        echo "- Columna 'ubicacion' agregada a inventario_equipos.\n";
    } else {
        echo "- Columna 'ubicacion' ya existe.\n";
    }

    if (!in_array('acceso_internet', $colsInventario)) {
        $pdo->exec("ALTER TABLE inventario_equipos ADD COLUMN acceso_internet ENUM('Permanente', 'Ocasional', 'Ninguno') NOT NULL DEFAULT 'Permanente' AFTER ubicacion");
        echo "- Columna 'acceso_internet' agregada a inventario_equipos.\n";
    } else {
        echo "- Columna 'acceso_internet' ya existe.\n";
    }

    if (!in_array('sensibilidad', $colsInventario)) {
        $pdo->exec("ALTER TABLE inventario_equipos ADD COLUMN sensibilidad ENUM('Confidencial', 'Restringido', 'Publico') NOT NULL DEFAULT 'Publico' AFTER acceso_internet");
        echo "- Columna 'sensibilidad' agregada a inventario_equipos.\n";
    } else {
        echo "- Columna 'sensibilidad' ya existe.\n";
    }

    // 2. Verificar columnas en usuarios
    $colsUsuarios = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $colsUsuarios[] = strtolower($row['Field']);
    }

    if (!in_array('is_2fa_enabled', $colsUsuarios)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN is_2fa_enabled TINYINT(1) NOT NULL DEFAULT 0");
        echo "- Columna 'is_2fa_enabled' agregada a usuarios.\n";
    }

    if (!in_array('secret_2fa', $colsUsuarios)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN secret_2fa VARCHAR(64) DEFAULT NULL");
        echo "- Columna 'secret_2fa' agregada a usuarios.\n";
    }

    if (!in_array('puede_pedir_equipos', $colsUsuarios)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN puede_pedir_equipos TINYINT(1) NOT NULL DEFAULT 0");
        echo "- Columna 'puede_pedir_equipos' agregada a usuarios.\n";
    }

    echo "Actualización completada exitosamente.\n";
} catch (Exception $e) {
    echo "Error al aplicar parche: " . $e->getMessage() . "\n";
}
?>

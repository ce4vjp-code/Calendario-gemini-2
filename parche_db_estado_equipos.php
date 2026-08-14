<?php
require_once __DIR__ . '/config.php';

try {
    $pdo->exec("
        ALTER TABLE inventario_equipos 
        MODIFY COLUMN estado VARCHAR(50) NOT NULL DEFAULT 'inventario';
    ");
    
    $pdo->exec("
        UPDATE inventario_equipos SET estado = 'en_prestamo' WHERE estado = 'prestado';
        UPDATE inventario_equipos SET estado = 'en_mantenimiento' WHERE estado = 'mantenimiento';
        UPDATE inventario_equipos SET estado = 'no_disponible' WHERE estado = 'no_disponible';
        UPDATE inventario_equipos SET estado = 'disponible' WHERE estado = 'disponible';
    ");
    
    $pdo->exec("
        ALTER TABLE inventario_equipos 
        MODIFY COLUMN estado ENUM('inventario', 'disponible', 'no_disponible', 'en_mantenimiento', 'en_prestamo') NOT NULL DEFAULT 'inventario';
    ");
    
    echo "Parche aplicado exitosamente.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

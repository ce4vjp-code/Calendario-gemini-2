<?php
require_once __DIR__ . '/config.php';

try {
    // Sincronizar estado de equipos que actualmente están prestados
    $pdo->exec("
        UPDATE inventario_equipos 
        SET estado = 'en_prestamo' 
        WHERE id IN (
            SELECT equipo_id 
            FROM inventario_prestamos 
            WHERE estado IN ('prestado', 'atrasado')
        )
    ");
    
    // Y para asegurar, los que no están prestados y su estado es en_prestamo, regresarlos a inventario (por si acaso)
    $pdo->exec("
        UPDATE inventario_equipos 
        SET estado = 'inventario' 
        WHERE estado = 'en_prestamo' AND id NOT IN (
            SELECT equipo_id 
            FROM inventario_prestamos 
            WHERE estado IN ('prestado', 'atrasado')
        )
    ");
    
    echo "Equipos sincronizados exitosamente con sus prestamos activos.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

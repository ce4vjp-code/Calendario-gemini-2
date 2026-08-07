<?php
require_once 'config.php';

try {
    echo "Iniciando actualización de la base de datos para el módulo de diplomas...<br><br>";

    // 1. Modificar la columna 'rol' para añadir 'diplomas'
    echo "Actualizando esquema de roles...<br>";
    $pdo->exec("ALTER TABLE `usuarios` MODIFY COLUMN `rol` enum('admin', 'profesor', 'diplomas') NOT NULL DEFAULT 'profesor'");
    echo "<span style='color:green;'>✓ Esquema de roles actualizado correctamente.</span><br><br>";

    // 2. Crear usuario por defecto 'diplomas' / 'diplomas123'
    echo "Creando usuario exclusivo de diplomas...<br>";
    $rut = 'diplomas';
    $nombre = 'Encargado de Diplomas';
    $clave = password_hash('diplomas123', PASSWORD_BCRYPT);
    $rol = 'diplomas';

    // Verificar si ya existe para no duplicar
    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE rut = ?");
    $stmtCheck->execute([$rut]);
    if ($stmtCheck->fetch()) {
        // Actualizar contraseña si ya existe
        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET clave = ?, rol = ? WHERE rut = ?");
        $stmtUpdate->execute([$clave, $rol, $rut]);
        echo "<span style='color:blue;'>ℹ El usuario 'diplomas' ya existía, su contraseña ha sido restablecida a 'diplomas123'.</span><br><br>";
    } else {
        $stmtInsert = $pdo->prepare("INSERT INTO usuarios (rut, nombre, clave, rol) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$rut, $nombre, $clave, $rol]);
        echo "<span style='color:green;'>✓ Usuario 'diplomas' creado exitosamente. (RUT: diplomas, Clave: diplomas123)</span><br><br>";
    }

    echo "<strong>¡Proceso completado con éxito!</strong><br>";
    echo "<a href='login.html'>Ir al Login</a>";

} catch (Exception $e) {
    echo "<span style='color:red;'><strong>Error crítico:</strong> " . $e->getMessage() . "</span>";
}
?>

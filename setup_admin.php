<?php
require_once 'config.php';

echo "<h2>Instalación de Administrador</h2>";

try {
    // Verificar si la tabla usuarios existe
    $pdo->query("SELECT 1 FROM usuarios LIMIT 1");
} catch (Exception $e) {
    die("<p style='color:red;'>Error: La tabla 'usuarios' no existe en la base de datos. Asegúrate de haber importado el nuevo archivo database.sql en phpMyAdmin.</p>");
}

$rut_admin = 'cirdam';
$clave_admin = 'Dark19$$78';
$hash = password_hash($clave_admin, PASSWORD_BCRYPT);

try {
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rut = ?");
    $stmt->execute([$rut_admin]);
    $existe = $stmt->fetch();

    if ($existe) {
        // Actualizar la clave por si estaba mal
        $update = $pdo->prepare("UPDATE usuarios SET clave = ? WHERE rut = ?");
        $update->execute([$hash, $rut_admin]);
        echo "<p style='color:green;'>El usuario 'admin' ya existía. Su contraseña ha sido restablecida a: <strong>admin123</strong></p>";
    } else {
        // Insertar nuevo
        $insert = $pdo->prepare("INSERT INTO usuarios (rut, nombre, clave, rol) VALUES (?, 'Administrador Principal', ?, 'admin')");
        $insert->execute([$rut_admin, $hash]);
        echo "<p style='color:green;'>Usuario Administrador creado exitosamente.<br>RUT: <strong>admin</strong><br>Clave: <strong>admin123</strong></p>";
    }
    
    echo "<p><a href='login.html'>Ir a iniciar sesión</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error al crear el administrador: " . $e->getMessage() . "</p>";
}
?>

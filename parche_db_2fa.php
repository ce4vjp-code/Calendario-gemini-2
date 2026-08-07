<?php
require_once 'config.php';

try {
    echo "<h2>Aplicando parche para 2FA...</h2>";

    // Agregar secret_2fa a usuarios
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS secret_2fa VARCHAR(255) NULL");
    echo "<p>Columna 'secret_2fa' verificada/creada en 'usuarios'.</p>";

    // Agregar 2fa_enabled a usuarios
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS is_2fa_enabled TINYINT(1) NOT NULL DEFAULT 0");
    echo "<p>Columna 'is_2fa_enabled' verificada/creada en 'usuarios'.</p>";

    // Crear tabla para tokens temporales de login 2FA
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_2fa_tokens (
        token VARCHAR(128) PRIMARY KEY,
        user_id INT(11) NOT NULL,
        expires_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p>Tabla 'login_2fa_tokens' verificada/creada.</p>";

    echo "<p style='color:green;'>Parche aplicado exitosamente.</p>";
    echo "<a href='index.html'>Volver al inicio</a>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Error al aplicar parche: " . $e->getMessage() . "</p>";
}
?>

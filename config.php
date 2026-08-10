<?php
// config.php
// ─────────────────────────────────────────────────────────
// Carga de credenciales desde .env (nunca hardcodeadas aquí)
// ─────────────────────────────────────────────────────────
$_envFile = __DIR__ . '/.env';
if (file_exists($_envFile)) {
    $envVars = parse_ini_file($_envFile, false, INI_SCANNER_RAW);
    if (is_array($envVars)) {
        foreach ($envVars as $key => $value) {
            if (!isset($_ENV[$key])) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

session_set_cookie_params(['path' => '/']);
session_start();

$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_NAME')     ?: 'liceotpg_cal';
$username = getenv('DB_USER')     ?: 'liceotpg_cirdam';
$password = getenv('DB_PASSWORD') ?: '';  // vacío a propósito — requiere .env

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Parche silencioso para asegurar que tipo_pregunta soporte los nuevos tipos sin fallar por ENUM
    try {
        $pdo->exec("ALTER TABLE encuesta_preguntas MODIFY tipo_pregunta VARCHAR(50) NOT NULL;");
    } catch (PDOException $e) {
        // Ignorar si falla (ej. falta de permisos o tabla no existe)
    }
} catch (PDOException $e) {
    die(json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]));
}
?>

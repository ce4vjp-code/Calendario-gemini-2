<?php
// config.php
// ─────────────────────────────────────────────────────────
// Carga de credenciales desde .env (nunca hardcodeadas aquí)
// ─────────────────────────────────────────────────────────
$_envFile = __DIR__ . '/.env';
if (file_exists($_envFile)) {
    $lines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || empty($line)) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!isset($_ENV[$name])) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
            }
        }
    }
}

session_set_cookie_params(['path' => '/']);
session_start();

$host     = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$dbname   = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'liceotpg_cal';
$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'liceotpg_cirdam';
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

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

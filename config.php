<?php
// config.php
session_set_cookie_params(['path' => '/']);
session_start();
// Se eliminaron las cabeceras CORS (Access-Control-Allow-Origin) por seguridad.

$host = 'localhost';
$dbname = 'liceotpg_cal';
$username = 'liceotpg_cirdam';
$password = 'Dark19$$78';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Configurar PDO para que lance excepciones en caso de error
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

<?php
/**
 * Script de Auto-Despliegue (Webhook) para GitHub
 * Sube este archivo a la carpeta pública de tu servidor (ej: public_html)
 */

// =========================================================================
// 1. CONFIGURACIÓN
// =========================================================================
// Inventa una contraseña larga y difícil (Letras y números sin espacios)
// Deberás poner esta misma clave secreta en GitHub.
$secret = 'LiceoTPG_AutoDeploy_2026_Secreto!'; 

// La rama que quieres desplegar (usualmente 'main' o 'master')
$branch = 'main';

// =========================================================================
// 2. SEGURIDAD: VERIFICACIÓN DE GITHUB (Criptografía HMAC)
// =========================================================================
$headers = getallheaders();
$hub_signature = isset($headers['X-Hub-Signature-256']) ? $headers['X-Hub-Signature-256'] : '';

if (empty($hub_signature)) {
    // Modo alternativo (Menos seguro, por URL): Si pasas ?token=TuClave
    if (!isset($_GET['token']) || $_GET['token'] !== $secret) {
        http_response_code(403);
        die("Acceso denegado. No se proporcionó firma ni token válido.");
    }
} else {
    // Modo Seguro (Recomendado): GitHub envía una firma encriptada
    $payload = file_get_contents('php://input');
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret, false);
    
    if (!hash_equals($hash, $hub_signature)) {
        http_response_code(403);
        die("Acceso denegado. La firma criptográfica no coincide.");
    }
}

// =========================================================================
// 3. EJECUCIÓN DEL DESPLIEGUE (GIT PULL)
// =========================================================================
// Asegurarse de estar en el directorio correcto (la raíz donde está este script)
$dir = __DIR__;
chdir($dir);

// Comandos a ejecutar. (2>&1 redirige errores para poder leerlos)
$commands = [
    'git fetch --all 2>&1',
    'git reset --hard origin/' . $branch . ' 2>&1',
    'git pull origin ' . $branch . ' 2>&1'
];

$output_text = "=== Iniciando Despliegue en $dir ===\n";

foreach ($commands as $command) {
    $output = shell_exec($command);
    $output_text .= "\n$ $command\n";
    $output_text .= htmlentities(trim($output)) . "\n";
}

$output_text .= "\n=== Despliegue Finalizado ===\n";

// Guardar un log localmente por si quieres revisar qué pasó
file_put_contents('deploy_log.txt', date('Y-m-d H:i:s') . "\n" . $output_text . "\n\n", FILE_APPEND);

// Enviar respuesta a GitHub
echo "<pre>$output_text</pre>";
?>

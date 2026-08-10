<?php
// deploy.php — Webhook de despliegue seguro
// ─────────────────────────────────────────────────────────
// Valida la firma HMAC-SHA256 del payload de GitHub/GitLab.
// El token secreto se configura en .env como DEPLOY_SECRET.
// NUNCA poner el token en la URL como querystring.
// ─────────────────────────────────────────────────────────

require_once __DIR__ . '/config.php';  // carga .env y sesión

// ── 1. Solo aceptar POST ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method Not Allowed']));
}

// ── 2. Validar firma HMAC del payload ────────────────────
$secret = getenv('DEPLOY_SECRET');
if (empty($secret)) {
    http_response_code(500);
    die(json_encode(['error' => 'DEPLOY_SECRET no configurado en el servidor']));
}

$rawPayload = file_get_contents('php://input');
$signature  = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';  // GitHub
if (empty($signature)) {
    $signature = $_SERVER['HTTP_X_GITLAB_TOKEN'] ?? '';     // GitLab (token directo)
}

// Validar HMAC para GitHub-style (X-Hub-Signature-256)
if (str_starts_with($signature, 'sha256=')) {
    $expected = 'sha256=' . hash_hmac('sha256', $rawPayload, $secret);
    if (!hash_equals($expected, $signature)) {
        http_response_code(403);
        die(json_encode(['error' => 'Firma inválida']));
    }
} else {
    // GitLab-style: token directo comparado con tiempo constante
    if (!hash_equals($secret, $signature)) {
        http_response_code(403);
        die(json_encode(['error' => 'Token inválido']));
    }
}

// ── 3. Ejecutar el despliegue de forma segura ────────────
$projectDir = escapeshellarg(__DIR__);
$logFile    = __DIR__ . '/deploy_audit.log'; // Guardar en la misma carpeta para poder verlo

// Eliminar el archivo problemático que Git dice que no quiere sobreescribir
if (file_exists($projectDir . '/.env.example')) {
    unlink($projectDir . '/.env.example');
}

// Capturar toda la salida de git (errores incluidos)
// Se usa fetch + reset --hard para forzar la actualización y evitar el error "Your local changes would be overwritten"
$cmd = "cd $projectDir && git fetch origin main && git reset --hard FETCH_HEAD 2>&1";
$output = shell_exec($cmd);

// Registrar en log de auditoría
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$timestamp = date('Y-m-d H:i:s');
file_put_contents(
    $logFile,
    "[$timestamp] IP: $ip\nResultado de Git:\n$output\n------------------\n",
    FILE_APPEND
);

http_response_code(200);
echo json_encode([
    'status' => 'ok', 
    'message' => 'Comando ejecutado', 
    'git_output' => trim($output)
]);
?>

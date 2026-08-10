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
// NUNCA usar shell_exec con entrada de usuario.
// Definir el comando de deploy de forma estática y acotada.
$projectDir = escapeshellarg(__DIR__);
$logFile    = sys_get_temp_dir() . '/deploy_' . date('Ymd_His') . '.log';

// Comando fijo sin interpolación de datos del usuario
$cmd = "cd $projectDir && git pull origin main >> " . escapeshellarg($logFile) . " 2>&1";
$output = shell_exec($cmd);

// Registrar en log de auditoría
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$timestamp = date('Y-m-d H:i:s');
file_put_contents(
    sys_get_temp_dir() . '/deploy_audit.log',
    "[$timestamp] Deploy ejecutado desde IP: $ip\n",
    FILE_APPEND
);

http_response_code(200);
echo json_encode(['status' => 'ok', 'message' => 'Deploy ejecutado correctamente']);
?>

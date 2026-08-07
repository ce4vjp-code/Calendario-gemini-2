<?php
require_once '../../config.php';
header('Content-Type: application/json');

// Check admin auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['admin', 'diplomas'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
    exit;
}

$uploadFileDir = '../../uploads/diplomas/';
$templates = [];

if (is_dir($uploadFileDir)) {
    $files = scandir($uploadFileDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $templates[] = [
                    'name' => $file,
                    'url' => 'uploads/diplomas/' . $file
                ];
            }
        }
    }
}

echo json_encode(['success' => true, 'templates' => $templates]);
?>

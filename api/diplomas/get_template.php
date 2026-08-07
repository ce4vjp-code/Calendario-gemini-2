<?php
require_once '../../config.php';
header('Content-Type: application/json');

// Check admin auth
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Solo administradores.']);
    exit;
}

$templatePath = '../../uploads/diploma_template.jpg';
if (file_exists($templatePath)) {
    // Devuelve la URL relativa para que el frontend pueda cargarla
    echo json_encode(['success' => true, 'url' => 'uploads/diploma_template.jpg']);
} else {
    echo json_encode(['success' => false, 'error' => 'No hay plantilla disponible.']);
}
?>

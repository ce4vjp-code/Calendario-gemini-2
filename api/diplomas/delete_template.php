<?php
require_once '../../config.php';
header('Content-Type: application/json');

// Check admin auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['admin', 'diplomas'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['filename'])) {
    $filename = basename($data['filename']); // Prevent directory traversal
    $uploadFileDir = '../../uploads/diplomas/';
    $filePath = $uploadFileDir . $filename;
    
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo json_encode(['success' => true, 'message' => 'Plantilla eliminada con éxito.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el archivo.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'El archivo no existe.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Petición inválida.']);
}
?>

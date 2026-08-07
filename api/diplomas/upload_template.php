<?php
require_once '../../config.php';
require_once '../logger.php';
header('Content-Type: application/json');

// Check admin auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['admin', 'diplomas'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['template']) && $_FILES['template']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['template']['tmp_name'];
        $fileName = $_FILES['template']['name'];
        $fileSize = $_FILES['template']['size'];
        $fileType = $_FILES['template']['type'];
        
        // Validate it's a JPEG
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileTmpPath);
        finfo_close($finfo);
        
        if ($mime === 'image/jpeg' || $mime === 'image/jpg' || $mime === 'image/png') {
            $uploadFileDir = '../../uploads/diplomas/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $safeName = preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($fileName));
            $dest_path = $uploadFileDir . $safeName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                registrar_actividad($pdo, 'Diplomas', 'Subir', "Plantilla de diploma subida: $safeName");
                echo json_encode(['success' => true, 'message' => 'Plantilla subida con éxito.', 'file' => $safeName]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al mover el archivo subido.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'El archivo debe ser una imagen JPG o PNG. Tipo detectado: ' . $mime]);
        }
    } else {
        $error = isset($_FILES['template']) ? $_FILES['template']['error'] : 'No se recibió ningún archivo.';
        echo json_encode(['success' => false, 'error' => 'Error en la subida del archivo. Código: ' . $error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
}
?>

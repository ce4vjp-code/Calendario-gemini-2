<?php
require_once '../config.php';
header('Content-Type: application/json');

try {
    $pdo->beginTransaction();

    // 1. Agregar la columna cursos_asignados si no existe
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN cursos_asignados TEXT DEFAULT NULL");
        $msg1 = "Columna cursos_asignados agregada con éxito.";
    } catch (PDOException $e) {
        $msg1 = "La columna cursos_asignados ya existía o hubo un aviso.";
    }

    // 2. Actualizar el ENUM de roles para permitir los nuevos roles
    try {
        $pdo->exec("ALTER TABLE usuarios MODIFY COLUMN rol ENUM('admin', 'profesor', 'diplomas', 'auxiliar', 'asistente_educacion', 'externo', 'directivo', 'inventario') NOT NULL DEFAULT 'profesor'");
        $msg2 = "Permisos de roles (ENUM) actualizados con éxito.";
    } catch (PDOException $e) {
        $msg2 = "Error al actualizar los roles: " . $e->getMessage();
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Base de datos de producción actualizada correctamente.',
        'details' => [$msg1, $msg2]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Fallo crítico al actualizar base de datos: ' . $e->getMessage()]);
}
?>

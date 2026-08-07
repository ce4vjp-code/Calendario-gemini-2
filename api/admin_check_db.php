<?php
require_once '../config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, asignatura, curso, profesor, usuario_id, fecha, hora, tipo FROM evaluaciones");
    $evals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt2 = $pdo->query("SELECT id, asignatura_nombre, curso_nombre, usuario_nombre, fecha_evaluacion FROM liceotpg_calendario.registros_evaluaciones");
    $oldEvals = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'new_evals' => $evals,
        'old_evals' => $oldEvals
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

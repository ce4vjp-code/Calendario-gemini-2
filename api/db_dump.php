<?php
require_once '../config.php';
try {
    $stmt = $pdo->query("SELECT id, asignatura_nombre, curso_nombre, usuario_nombre, fecha_evaluacion, hora_inicio, descripcion, tipo FROM liceotpg_calendario.registros_evaluaciones LIMIT 5");
    $oldEvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('../old_evals_dump.json', json_encode($oldEvals, JSON_PRETTY_PRINT));
    
    $stmt2 = $pdo->query("SELECT id, asignatura, curso, profesor, fecha, hora, tipo FROM evaluaciones LIMIT 10");
    $newEvals = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('../new_evals_dump.json', json_encode($newEvals, JSON_PRETTY_PRINT));
    
    // Buscar duplicados
    $stmt3 = $pdo->query("SELECT asignatura, curso, fecha, COUNT(*) as c FROM evaluaciones GROUP BY asignatura, curso, fecha HAVING c > 1");
    $dups = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('../dups_dump.json', json_encode($dups, JSON_PRETTY_PRINT));
    
    echo "OK";
} catch (Exception $e) {
    file_put_contents('../db_error.txt', $e->getMessage());
    echo "Error";
}

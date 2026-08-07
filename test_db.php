<?php
require_once 'config.php';
try {
    $stmt = $pdo->query("DESCRIBE encuesta_preguntas");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>

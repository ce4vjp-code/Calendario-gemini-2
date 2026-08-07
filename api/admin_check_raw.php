<?php
require_once '../config.php';
try {
    $stmt = $pdo->query("SELECT * FROM liceotpg_calendario.evaluaciones LIMIT 5");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('../evaluaciones_original.json', json_encode($data, JSON_PRETTY_PRINT));
    echo "OK";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

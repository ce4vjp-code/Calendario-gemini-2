<?php
require_once '../config.php';
try {
    $stmt = $pdo->query("DESCRIBE liceotpg_calendario.registros_evaluaciones");
    $desc = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dump to JSON so I can read it
    file_put_contents('../old_table_desc.json', json_encode($desc, JSON_PRETTY_PRINT));
    
    // Let's also dump the actual conflicting row if possible, searching for '14:20'
    $stmt2 = $pdo->query("SELECT * FROM liceotpg_calendario.registros_evaluaciones WHERE hora_inicio LIKE '%14:20%' OR hora_inicio LIKE '%12:00%' LIMIT 20");
    $data = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('../old_table_data.json', json_encode($data, JSON_PRETTY_PRINT));

    echo "OK";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

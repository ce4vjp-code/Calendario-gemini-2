<?php
require_once '../config.php';
try {
    $stmt = $pdo->query("SHOW CREATE VIEW liceotpg_calendario.registros_evaluaciones");
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('../view_desc.json', json_encode($res, JSON_PRETTY_PRINT));
} catch (Exception $e) {
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE liceotpg_calendario.registros_evaluaciones");
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents('../view_desc.json', json_encode($res, JSON_PRETTY_PRINT));
    } catch (Exception $e2) {
        file_put_contents('../view_desc.json', "Error: " . $e2->getMessage());
    }
}
echo "OK";

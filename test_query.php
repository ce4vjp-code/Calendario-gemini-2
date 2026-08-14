<?php
require_once __DIR__ . '/config.php';
try {
    $stmt = $pdo->query("SELECT id, nombre, estado FROM inventario_equipos");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

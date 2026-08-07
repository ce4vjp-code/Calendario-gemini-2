<?php
require_once '../config.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Diagnóstico de Evaluaciones Duplicadas</h2>";
echo "<p>Mostrando las evaluaciones que caen el mismo día para la misma asignatura y curso en la base antigua:</p>";

try {
    // Buscar posibles duplicados en la base antigua agrupando por fecha, curso y asignatura
    $stmt = $pdo->query("
        SELECT e1.* 
        FROM liceotpg_calendario.registros_evaluaciones e1
        INNER JOIN (
            SELECT fecha_evaluacion, curso_nombre, asignatura_nombre 
            FROM liceotpg_calendario.registros_evaluaciones 
            GROUP BY fecha_evaluacion, curso_nombre, asignatura_nombre 
            HAVING COUNT(*) > 1
        ) e2 ON e1.fecha_evaluacion = e2.fecha_evaluacion 
             AND e1.curso_nombre = e2.curso_nombre 
             AND e1.asignatura_nombre = e2.asignatura_nombre
        ORDER BY e1.fecha_evaluacion, e1.curso_nombre, e1.asignatura_nombre
    ");
    
    $allEvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 14px; width: 100%;'>";
    if (count($allEvals) > 0) {
        echo "<tr style='background-color: #f1f5f9;'>";
        foreach (array_keys($allEvals[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        foreach ($allEvals as $ev) {
            echo "<tr>";
            foreach ($ev as $val) {
                echo "<td>" . htmlspecialchars($val) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td>No se encontraron evaluaciones duplicadas en el mismo día/curso/asignatura en la base antigua.</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

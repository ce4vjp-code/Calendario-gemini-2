<?php
require_once '../config.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Herramienta de Reparación de Evaluaciones Importadas</h2>";

if (isset($_GET['ejecutar']) && $_GET['ejecutar'] == '1') {
    try {
        echo "<h3>1. Limpiando evaluaciones defectuosas...</h3>";
        // Vaciamos la tabla para empezar de cero limpio y sin duplicados
        $pdo->exec("TRUNCATE TABLE evaluaciones");
        echo "<p>Tabla limpiada.</p>";

        // Usamos la misma consulta JOIN que usaba el sistema antiguo para evitar datos erróneos de vistas pre-fabricadas
        $stmtEv = $pdo->query("
            SELECT 
                a.nombre AS asignatura_nombre, 
                c.nombre AS curso_nombre, 
                u.nombre AS usuario_nombre, 
                e.fecha AS fecha_evaluacion, 
                e.hora_inicio, 
                e.descripcion, 
                e.tipo 
            FROM liceotpg_calendario.evaluaciones e
            INNER JOIN liceotpg_calendario.curso_asignatura ca ON ca.id = e.curso_asignatura_id
            INNER JOIN liceotpg_calendario.cursos c ON c.id = ca.curso_id
            INNER JOIN liceotpg_calendario.asignaturas a ON a.id = ca.asignatura_id
            LEFT JOIN liceotpg_calendario.usuarios u ON u.id = e.creado_por
        ");
        
        $evaluacionesAntiguas = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

        // Ya NO usamos el ID antiguo, dejamos que la nueva base de datos genere uno nuevo (evita colisiones y mezclas raras)
        $insertEvaluacion = $pdo->prepare("INSERT INTO evaluaciones (asignatura, curso, profesor, usuario_id, fecha, hora, tipo, observaciones) VALUES (?, ?, ?, NULL, ?, ?, ?, ?)");
        
        $evaluacionesImportadas = 0;

        foreach ($evaluacionesAntiguas as $ev) {
            $asignatura = $ev['asignatura_nombre'];
            $curso = $ev['curso_nombre'];
            $profesor = $ev['usuario_nombre'];
            $hora = $ev['hora_inicio'];
            $observaciones = $ev['descripcion'];
            $tipo = $ev['tipo'];
            
            // CORRECCIÓN DE FORMATO DE FECHA
            $fechaOriginal = trim($ev['fecha_evaluacion']);
            $fechaFormateada = $fechaOriginal;
            
            // Intentar detectar si viene en formato DD/MM/YYYY o DD-MM-YYYY y convertir a YYYY-MM-DD
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $fechaOriginal, $matches)) {
                $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $anio = $matches[3];
                $fechaFormateada = "$anio-$mes-$dia";
            }

            try {
                $insertEvaluacion->execute([$asignatura, $curso, $profesor, $fechaFormateada, $hora, $tipo, $observaciones]);
                $evaluacionesImportadas++;
            } catch (PDOException $e) {
                echo "Error importando evaluación: " . $e->getMessage() . "<br>";
            }
        }
        
        echo "<p>Se importaron $evaluacionesImportadas evaluaciones exitosamente sin duplicados y con las fechas correctas.</p>";
        echo "<h2 style='color: green;'>¡Proceso Finalizado con Éxito!</h2>";
        echo "<p><a href='../admin_encuestas.html'>Volver al panel</a></p>";
        
    } catch (Exception $e) {
        echo "<h3 style='color: red;'>Error Fatal: " . $e->getMessage() . "</h3>";
    }
} else {
    echo "<p>Parece que algunas evaluaciones se mezclaron al importar porque tenían el mismo ID numérico, o las fechas estaban en un formato diferente (ej. 31/12/2024 en vez de 2024-12-31).</p>";
    echo "<p>Para solucionar los duplicados y las fechas, este proceso <b>eliminará las evaluaciones actuales</b> en el sistema nuevo y volverá a copiarlas todas desde la base de datos antigua asegurándose de convertir bien las fechas y no mezclar IDs.</p>";
    echo "<p style='color: red;'><b>Nota:</b> Si habías agregado alguna evaluación nueva a mano el día de hoy en el sistema nuevo, se borrará y tendrás que agregarla de nuevo.</p>";
    echo "<br><br>";
    echo "<a href='?ejecutar=1' style='background-color: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Limpiar y Re-importar Correctamente</a>";
}
?>

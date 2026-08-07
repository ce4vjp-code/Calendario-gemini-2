<?php
require_once '../config.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Herramienta de Recuperación Segura</h2>";

try {
    // 1. Re-importar usuarios faltantes
    $stmtAntiguos = $pdo->query("SELECT id, rut, nombre FROM liceotpg_calendario.usuarios");
    $profesoresAntiguos = $stmtAntiguos->fetchAll(PDO::FETCH_ASSOC);

    // Usamos IGNORE para no afectar a los usuarios que ya existen y no modificar sus datos actuales
    $insertUsuario = $pdo->prepare("INSERT IGNORE INTO liceotpg_cal.usuarios (id, rut, clave, nombre, email, rol) VALUES (?, ?, ?, ?, '', 'profesor')");
    $usuariosRecuperados = 0;

    foreach ($profesoresAntiguos as $prof) {
        $rutNumerico = preg_replace('/[^0-9]/', '', $prof['rut']);
        $i = 2; $suma = 0;
        foreach(array_reverse(str_split($rutNumerico)) as $v) {
            if($i == 8) $i = 2;
            $suma += $v * $i;
            ++$i;
        }
        $dvr = 11 - ($suma % 11);
        if ($dvr == 11) $dvr = 0;
        if ($dvr == 10) $dvr = 'K';
        $rutCompleto = $rutNumerico . $dvr;
        $claveHash = password_hash($rutCompleto, PASSWORD_DEFAULT);
        
        try {
            $insertUsuario->execute([$prof['id'], $rutCompleto, $claveHash, $prof['nombre']]);
            if ($insertUsuario->rowCount() > 0) $usuariosRecuperados++;
        } catch(Exception $e) {}
    }

    echo "<p>Usuarios recuperados/restaurados: $usuariosRecuperados</p>";

    // 2. Re-importar evaluaciones faltantes (SIN BORRAR NADA, usando la consulta correcta)
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

    // Utilizamos IGNORE para que solo inserte evaluaciones que no existan, manteniendo intactas las demás
    $insertEvaluacion = $pdo->prepare("INSERT IGNORE INTO liceotpg_cal.evaluaciones (asignatura, curso, profesor, fecha, hora, tipo, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $evRecuperadas = 0;

    foreach ($evaluacionesAntiguas as $ev) {
        try {
            $insertEvaluacion->execute([
                $ev['asignatura_nombre'],
                $ev['curso_nombre'],
                $ev['usuario_nombre'],
                $ev['fecha_evaluacion'],
                $ev['hora_inicio'],
                $ev['tipo'],
                $ev['descripcion']
            ]);
            if ($insertEvaluacion->rowCount() > 0) $evRecuperadas++;
        } catch(Exception $e) {}
    }

    echo "<p>Evaluaciones recuperadas/restauradas: $evRecuperadas</p>";
    echo "<h3 style='color: green;'>¡Recuperación finalizada con éxito!</h3>";
    echo "<p>Puedes cerrar esta pestaña y volver al panel de administración.</p>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error Fatal: " . $e->getMessage() . "</h3>";
}
?>

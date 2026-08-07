<?php
require_once '../config.php';
header('Content-Type: text/html; charset=utf-8');

// Verificamos sesión (opcional, pero recomendado para seguridad. Lo dejamos comentado por si necesitan ejecutarlo directo).
// session_start();
// if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') { die("Acceso denegado"); }

echo "<h2>Iniciando Importación de Datos...</h2>";

try {
    // === 1. IMPORTAR USUARIOS (Profesores) ===
    echo "<h3>1. Importando Profesores...</h3>";
    
    // Obtenemos los usuarios de la base antigua
    $stmtAntiguos = $pdo->query("SELECT id, rut, nombre FROM liceotpg_calendario.usuarios");
    $profesoresAntiguos = $stmtAntiguos->fetchAll(PDO::FETCH_ASSOC);
    
    $insertUsuario = $pdo->prepare("INSERT INTO usuarios (id, rut, clave, nombre, email, rol) VALUES (?, ?, ?, ?, ?, 'profesor') ON DUPLICATE KEY UPDATE rut = VALUES(rut), nombre = VALUES(nombre)");
    $usuariosImportados = 0;

    foreach ($profesoresAntiguos as $prof) {
        $id = $prof['id'];
        $rutOriginal = trim($prof['rut'] ?? '');
        $nombre = $prof['nombre'];
        $email = ''; // Dejamos el email vacío por ahora para que lo editen después
        
        if (empty($rutOriginal)) continue; // Evitar rut vacíos

        // Calcular el Dígito Verificador (DV) usando el algoritmo Módulo 11
        $rutNumerico = preg_replace('/[^0-9]/', '', $rutOriginal);
        $i = 2;
        $suma = 0;
        foreach(array_reverse(str_split($rutNumerico)) as $v) {
            if($i == 8) $i = 2;
            $suma += $v * $i;
            ++$i;
        }
        $dvr = 11 - ($suma % 11);
        if ($dvr == 11) $dvr = 0;
        if ($dvr == 10) $dvr = 'K';
        
        $rutCompleto = $rutNumerico . $dvr; // Sin guión, como lo requiere el nuevo sistema

        // La clave por defecto será el RUT completo (sin guión) para facilitar el ingreso
        $claveHash = password_hash($rutCompleto, PASSWORD_DEFAULT);

        try {
            $insertUsuario->execute([$id, $rutCompleto, $claveHash, $nombre, $email]);
            $usuariosImportados++;
        } catch (PDOException $e) {
            echo "Error importando usuario $nombre: " . $e->getMessage() . "<br>";
        }
    }
    echo "<p>Se importaron $usuariosImportados profesores exitosamente. (Se calculó su dígito verificador y su contraseña inicial es su mismo RUT sin guión ni puntos).</p>";


    // === 2. IMPORTAR EVALUACIONES ===
    echo "<h3>2. Importando Evaluaciones...</h3>";
    
    // Obtenemos las evaluaciones antiguas
    // Mapeo:
    // id -> id
    // asignatura_nombre -> asignatura
    // curso_nombre -> curso
    // usuario_nombre -> profesor
    // fecha_evaluacion -> fecha
    // hora_inicio -> hora
    // descripcion -> observaciones
    // tipo -> tipo
    $stmtEv = $pdo->query("SELECT id, asignatura_nombre, curso_nombre, usuario_nombre, fecha_evaluacion, hora_inicio, descripcion, tipo FROM liceotpg_calendario.registros_evaluaciones");
    $evaluacionesAntiguas = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

    // En la tabla nueva existe "usuario_id". Asignaremos NULL (o 1) ya que solo tenemos el nombre del profesor en texto, 
    // a menos que intentemos buscar su ID. Dado que insertamos a los profesores preservando el ID antiguo, 
    // podríamos buscar el ID pero la tabla antigua solo tiene "usuario_nombre". Lo guardaremos simplemente en "profesor".
    $insertEvaluacion = $pdo->prepare("INSERT INTO evaluaciones (id, asignatura, curso, profesor, usuario_id, fecha, hora, tipo, observaciones) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE asignatura=VALUES(asignatura), fecha=VALUES(fecha)");
    
    $evaluacionesImportadas = 0;

    foreach ($evaluacionesAntiguas as $ev) {
        $id = $ev['id'];
        $asignatura = $ev['asignatura_nombre'];
        $curso = $ev['curso_nombre'];
        $profesor = $ev['usuario_nombre'];
        $fecha = $ev['fecha_evaluacion'];
        $hora = $ev['hora_inicio'];
        $observaciones = $ev['descripcion'];
        $tipo = $ev['tipo'];

        try {
            $insertEvaluacion->execute([$id, $asignatura, $curso, $profesor, $fecha, $hora, $tipo, $observaciones]);
            $evaluacionesImportadas++;
        } catch (PDOException $e) {
            echo "Error importando evaluación ID $id: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<p>Se importaron $evaluacionesImportadas evaluaciones exitosamente.</p>";
    echo "<h2 style='color: green;'>¡Proceso Finalizado con Éxito!</h2>";
    echo "<p>Por seguridad, te recomiendo eliminar este archivo <b>admin_importar_datos.php</b> de tu servidor una vez que hayas comprobado que los datos están listos.</p>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error Fatal: " . $e->getMessage() . "</h3>";
}
?>

<?php
require_once 'config.php';

echo "<h2>Importando Cursos y Asignaturas base...</h2>";

$cursos = [
    "1° Básico", "2° Básico", "3° Básico", "4° Básico", "5° Básico", "6° Básico", "7° Básico", "8° Básico",
    "1° Medio A", "1° Medio B", "2° Medio A", "2° Medio B", 
    "3° Medio A - Telecomunicaciones", "3° Medio B - Muebles y Terminaciones en Madera", "3° Medio C - Atención de Enfermería", 
    "4° Medio A - Telecomunicaciones", "4° Medio B - Muebles y Terminaciones en Madera", "4° Medio C - Atención de Enfermería"
];

$asignaturas = [
    "Lenguaje y Comunicación", "Inglés", "Matemática", "Historia Geografía y Ciencias Sociales",
    "Ciencias Naturales", "Tecnología", "Educación Física y Salud", "Artes Visuales", "Música",
    "Orientación", "Religión", "Taller de Recuperación de Habilidades de Lenguaje",
    "Taller de Recuperación de Habilidades de Matemática", "DIA", "Velocidad Lectora", "SIMCE",
    "Lengua y Literatura", "Educación Ciudadana", "Filosofía", "Ciencias para la Ciudadanía",
    "Taller de Educación Física y Salud", "Taller de Orientación", "Taller de Inglés",
    "Operación y Fundamentos de las Telecomunicaciones",
    "Instalación y Mantenimiento Básico de un Terminal Informático", "Instalación y Configuración de Redes",
    "Mantenimiento de Circuitos Electrónicos Básicos", "Instalación de Servicios Básicos de Telecomunicaciones",
    "Abastecimiento y Despacho",
    "Fabricación de Componentes de Carpintería y Muebles", "Cubicaciones",
    "Aseguramiento de la Calidad, Seguridad y Cuidado del Medio Ambiente",
    "Representación Gráfica de Muebles y Elementos de Carpintería",
    "Aplicación de Cuidados Básicos",
    "Medición y Control de Parámetros Básicos en Salud",
    "Promoción de la Salud y Prevención de la Enfermedad",
    "Higiene y Bioseguridad del Ambiente", "Sistema de Registro e Información en Salud"
];

// Colores aleatorios atractivos para las asignaturas
$colores = ['#ef4444', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];

try {
    // Insertar Cursos
    $stmtCursos = $pdo->prepare("INSERT INTO horario_cursos (nombre) VALUES (?)");
    $cursosInsertados = 0;
    foreach ($cursos as $curso) {
        // Verificar si ya existe
        $check = $pdo->prepare("SELECT id FROM horario_cursos WHERE nombre = ?");
        $check->execute([$curso]);
        if ($check->rowCount() == 0) {
            $stmtCursos->execute([$curso]);
            $cursosInsertados++;
        }
    }
    echo "<p>Cursos insertados: $cursosInsertados</p>";

    // Insertar Asignaturas
    $stmtAsignaturas = $pdo->prepare("INSERT INTO horario_asignaturas (nombre, color) VALUES (?, ?)");
    $asignaturasInsertadas = 0;
    foreach ($asignaturas as $index => $asignatura) {
        $check = $pdo->prepare("SELECT id FROM horario_asignaturas WHERE nombre = ?");
        $check->execute([$asignatura]);
        if ($check->rowCount() == 0) {
            $color = $colores[$index % count($colores)]; // Asignar color secuencial del array
            $stmtAsignaturas->execute([$asignatura, $color]);
            $asignaturasInsertadas++;
        }
    }
    echo "<p>Asignaturas insertadas: $asignaturasInsertadas</p>";

    echo "<h3 style='color:green;'>Importación finalizada con éxito.</h3>";
    echo "<a href='admin_horarios.html'>Ir a la Gestión de Horarios</a>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>Error durante la importación:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

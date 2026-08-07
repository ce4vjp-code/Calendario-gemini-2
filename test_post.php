<?php
// Mute notices and warnings to see pure output
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_rol'] = 'admin';

$data = json_encode([
    'titulo' => 'Test',
    'descripcion' => 'Test',
    'preguntas' => [
        ['texto_pregunta' => 'P1', 'tipo_pregunta' => 'texto']
    ]
]);

// Simulate the POST payload
file_put_contents('php://memory', $data); // We can't mock php://input easily from another script.

// Let's just include the file but mock php://input... not possible.

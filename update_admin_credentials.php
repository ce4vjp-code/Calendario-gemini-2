<?php
// update_admin_credentials.php
require_once 'config.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoNombre = trim($_POST['nombre']);
    $nuevoRut = trim($_POST['rut']);
    $nuevaClave = trim($_POST['clave']);
    $adminSecret = trim($_POST['secret']); // Para evitar que cualquiera cambie la clave

    // Código secreto estático para autorizar el cambio
    if ($adminSecret === 'AdminUpdate2026') {
        if (!empty($nuevoNombre) && !empty($nuevaClave) && !empty($nuevoRut)) {
            $hashed = password_hash($nuevaClave, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, rut = ?, clave = ? WHERE rol = 'admin'");
                $stmt->execute([$nuevoNombre, $nuevoRut, $hashed]);
                
                // Si la consulta afectó 0 filas, quizás no existe el admin aún
                if ($stmt->rowCount() > 0) {
                    $message = "<div class='success'>¡Credenciales del administrador actualizadas! <br><b>Nuevo RUT de acceso:</b> $nuevoRut <br><b>Nuevo Nombre:</b> $nuevoNombre</div>";
                } else {
                    $message = "<div class='error'>No se encontró ningún usuario con rol de administrador en la base de datos. Asegúrate de haber ejecutado setup_admin.php primero.</div>";
                }
            } catch (Exception $e) {
                $message = "<div class='error'>Error en la base de datos: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        $message = "<div class='error'>Código secreto incorrecto.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Credenciales de Administrador</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px 20px; background: #f1f5f9; color: #333; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #4f46e5; font-size: 1.5rem; text-align: center; }
        label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; }
        input { display: block; width: 100%; margin-bottom: 20px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 1rem; }
        input:focus { border-color: #4f46e5; outline: none; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        button { background: #4f46e5; color: white; border: none; padding: 12px 15px; border-radius: 6px; cursor: pointer; width: 100%; font-size: 1rem; font-weight: bold; }
        button:hover { background: #4338ca; }
        .success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #bbf7d0; font-size: 0.9rem; }
        .error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fecaca; font-size: 0.9rem; }
        .note { font-size: 0.8rem; color: #64748b; margin-top: -15px; margin-bottom: 20px; display: block; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Recuperar / Cambiar Admin</h2>
        
        <?php if($message) echo $message; ?>
        
        <form method="POST">
            <label>Nuevo Nombre de Administrador:</label>
            <input type="text" name="nombre" required placeholder="Ej. Director General">
            
            <label>Nuevo RUT de acceso (Usuario):</label>
            <input type="text" name="rut" required placeholder="Ej. 12345678-9">
            
            <label>Nueva Contraseña:</label>
            <input type="password" name="clave" required placeholder="••••••••">

            <label>Código Secreto de Seguridad:</label>
            <input type="password" name="secret" required placeholder="Código para autorizar el cambio">
            <span class="note">Nota: Por seguridad, se requiere el código secreto. Por defecto es <b>AdminUpdate2026</b></span>

            <button type="submit">Actualizar Administrador</button>
        </form>
    </div>
</body>
</html>

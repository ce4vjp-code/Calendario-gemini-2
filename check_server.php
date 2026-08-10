<?php
echo "<h2>Diagnóstico del Servidor para Auto-Despliegue</h2>";

// 1. Revisar si shell_exec está habilitado
$disabled_functions = explode(',', ini_get('disable_functions'));
$is_shell_exec_disabled = in_array('shell_exec', array_map('trim', $disabled_functions));

if ($is_shell_exec_disabled || !function_exists('shell_exec')) {
    echo "<p style='color:red;'>❌ <strong>shell_exec:</strong> Está deshabilitado en tu servidor. No podrás usar el Método 2 a menos que lo actives en tu cPanel (en la sección 'Seleccionar versión de PHP' o editando el php.ini).</p>";
} else {
    echo "<p style='color:green;'>✅ <strong>shell_exec:</strong> Está habilitado.</p>";
    
    // 2. Revisar si Git está instalado
    $git_version = shell_exec('git --version 2>&1');
    
    if (strpos(strtolower($git_version), 'git version') !== false) {
        echo "<p style='color:green;'>✅ <strong>Git:</strong> Está instalado correctamente. (" . htmlspecialchars(trim($git_version)) . ")</p>";
        echo "<p style='color:blue;'>🎉 <strong>Conclusión:</strong> ¡Tu servidor cumple con todos los requisitos para usar el script de Webhook (Método 2)!</p>";
    } else {
        echo "<p style='color:red;'>❌ <strong>Git:</strong> No parece estar instalado o no es accesible por PHP. Resultado del comando: <em>" . htmlspecialchars(trim($git_version)) . "</em></p>";
        echo "<p style='color:orange;'>⚠️ <strong>Conclusión:</strong> Tendrás que usar el 'Git Version Control' nativo de cPanel o contactar a tu proveedor de hosting para instalar Git.</p>";
    }
}
?>

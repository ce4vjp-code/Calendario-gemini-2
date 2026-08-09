<?php
require_once "config.php";
try {
    // 1. Modificar tabla usuarios
    // Expandir el ENUM de rol
    $pdo->exec("ALTER TABLE usuarios MODIFY COLUMN rol ENUM('admin', 'profesor', 'diplomas', 'auxiliar', 'asistente_educacion', 'externo', 'directivo') NOT NULL DEFAULT 'profesor'");
    
    // Agregar columna puede_pedir_equipos si no existe
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS puede_pedir_equipos BOOLEAN NOT NULL DEFAULT 0");
    
    // Por defecto, hacer que los profesores sí puedan pedir equipos
    $pdo->exec("UPDATE usuarios SET puede_pedir_equipos = 1 WHERE rol = 'profesor'");
    
    // 2. Crear tabla inventario_equipos
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS inventario_equipos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        estado ENUM('disponible', 'prestado', 'mantenimiento') NOT NULL DEFAULT 'disponible',
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    // 3. Crear tabla inventario_prestamos
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS inventario_prestamos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        equipo_id INT NOT NULL,
        usuario_id INT NOT NULL,
        fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_devolucion_esperada DATETIME NOT NULL,
        fecha_devolucion_real DATETIME NULL,
        estado ENUM('pendiente_codigo', 'pendiente_aprobacion', 'prestado', 'rechazado', 'devuelto', 'atrasado') NOT NULL DEFAULT 'pendiente_codigo',
        observaciones TEXT,
        codigo_aprobacion VARCHAR(10) NULL,
        directivo_id INT NULL,
        FOREIGN KEY (equipo_id) REFERENCES inventario_equipos(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    // Tambien agregar la nueva tabla al script database.sql para futuras instalaciones limpias (Opcional, pero bueno)
    
    echo "DB Configurada Exitosamente.";
} catch (PDOException $e) {
    echo "Error DB: " . $e->getMessage();
}
?>

-- 1. Actualizar el ENUM de roles para soportar los nuevos y agregar la columna puede_pedir_equipos
ALTER TABLE `usuarios` MODIFY COLUMN `rol` ENUM('admin', 'profesor', 'diplomas', 'auxiliar', 'asistente_educacion', 'externo', 'directivo') NOT NULL DEFAULT 'profesor';

ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `puede_pedir_equipos` BOOLEAN NOT NULL DEFAULT 0;

-- Otorgar permiso por defecto a los profesores (opcional, ajusta según prefieras)
UPDATE `usuarios` SET `puede_pedir_equipos` = 1 WHERE `rol` = 'profesor';

-- 2. Tabla para el catálogo de equipos
CREATE TABLE IF NOT EXISTS `inventario_equipos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `descripcion` TEXT,
    `estado` ENUM('disponible', 'prestado', 'mantenimiento') NOT NULL DEFAULT 'disponible',
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabla para el historial y estado de los préstamos
CREATE TABLE IF NOT EXISTS `inventario_prestamos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `equipo_id` INT NOT NULL,
    `usuario_id` INT NOT NULL,
    `fecha_solicitud` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `fecha_devolucion_esperada` DATETIME NOT NULL,
    `fecha_devolucion_real` DATETIME NULL,
    `estado` ENUM('pendiente_codigo', 'pendiente_aprobacion', 'prestado', 'rechazado', 'devuelto', 'atrasado') NOT NULL DEFAULT 'pendiente_codigo',
    `observaciones` TEXT,
    `codigo_aprobacion` VARCHAR(10) NULL,
    `directivo_id` INT NULL,
    FOREIGN KEY (`equipo_id`) REFERENCES `inventario_equipos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

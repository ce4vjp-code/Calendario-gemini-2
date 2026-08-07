CREATE TABLE IF NOT EXISTS `evaluaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asignatura` varchar(100) NOT NULL,
  `curso` varchar(50) NOT NULL,
  `profesor` varchar(100) NOT NULL,
  `fecha` date NOT NULL,
  `hora` varchar(10) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rut` varchar(20) NOT NULL UNIQUE,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NULL,
  `clave` varchar(255) NOT NULL,
  `rol` enum('admin', 'profesor') NOT NULL DEFAULT 'profesor',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `invitaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_unico` varchar(50) NOT NULL UNIQUE,
  `email_destino` varchar(150) NOT NULL,
  `usado` boolean NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar un usuario administrador por defecto (RUT: admin, Clave: admin123)
-- NOTA: La clave está encriptada con bcrypt (admin123)
INSERT IGNORE INTO `usuarios` (`rut`, `nombre`, `clave`, `rol`) VALUES 
('admin', 'Administrador Principal', '$2y$10$tZ2zWcZ5W6E0vI/D.qO2/.E2O8aGqA5lV20VjY5wXlW3V5T/N2wU6', 'admin');

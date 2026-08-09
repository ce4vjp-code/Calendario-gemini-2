-- Actualización de la estructura de base de datos para manejar cantidades y el nuevo estado "no_disponible"

-- 1. Modificar tabla de equipos
ALTER TABLE inventario_equipos 
    MODIFY COLUMN estado ENUM('disponible', 'prestado', 'mantenimiento', 'no_disponible') DEFAULT 'disponible',
    ADD COLUMN cantidad INT NOT NULL DEFAULT 1 AFTER descripcion;

-- 2. Modificar tabla de préstamos para registrar cuántos ítems se solicitan
ALTER TABLE inventario_prestamos 
    ADD COLUMN cantidad INT NOT NULL DEFAULT 1 AFTER equipo_id;

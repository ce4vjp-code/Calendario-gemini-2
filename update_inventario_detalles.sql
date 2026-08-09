-- Actualización de la estructura de base de datos para detalles en actas

ALTER TABLE inventario_equipos 
    ADD COLUMN marca VARCHAR(100) NULL AFTER nombre,
    ADD COLUMN modelo VARCHAR(100) NULL AFTER marca,
    ADD COLUMN numero_serie VARCHAR(100) NULL AFTER modelo;

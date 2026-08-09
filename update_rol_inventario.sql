-- Agregar el rol 'inventario' al enum de la tabla usuarios
ALTER TABLE usuarios MODIFY COLUMN rol ENUM('admin', 'directivo', 'profesor', 'asistente', 'auxiliar', 'externo', 'inventario') NOT NULL;

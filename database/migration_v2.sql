-- Adaptaciones adicionales para qr_simple en aulavirtual

-- 1. Añadir first_name y last_name a usuarios
ALTER TABLE `usuarios`
ADD COLUMN `first_name` VARCHAR(100) DEFAULT NULL AFTER `id`,
ADD COLUMN `last_name` VARCHAR(100) DEFAULT NULL AFTER `first_name`;

-- Intentar poblar first_name y last_name desde nombres_apellidos (aproximación)
UPDATE `usuarios` 
SET 
    `first_name` = SUBSTRING_INDEX(`nombres_apellidos`, ' ', 1),
    `last_name` = TRIM(SUBSTRING(`nombres_apellidos`, LOCATE(' ', `nombres_apellidos`)));

-- 2. Crear tablas de tipos y modalidades para Cursos (Events)
CREATE TABLE IF NOT EXISTS `event_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_modalities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed types
INSERT IGNORE INTO `event_types` (`code`, `name`) VALUES 
('course', 'Curso'),
('diploma', 'Diplomado'),
('congress', 'Congreso'),
('seminar', 'Seminario'),
('workshop', 'Taller');

-- Seed modalities
INSERT IGNORE INTO `event_modalities` (`code`, `name`) VALUES 
('virtual', 'Virtual'),
('presencial', 'Presencial'),
('semipresencial', 'Semipresencial'),
('hybrid', 'Híbrido');

-- 3. Añadir columnas de FK a cursos si no existen (para soportar Course.php)
-- Primero verificamos si podemos añadir columnas que faltan
ALTER TABLE `cursos`
ADD COLUMN `event_type_id` INT DEFAULT NULL,
ADD COLUMN `event_modality_id` INT DEFAULT NULL,
ADD COLUMN `max_capacity` INT DEFAULT NULL,
ADD COLUMN `certificate_background_filename` VARCHAR(255) DEFAULT NULL;

-- Añadir FKs
-- (Omitimos constraints estrictos por ahora para evitar conflictos con datos existentes, o los añadimos con ON DELETE SET NULL)
-- ALTER TABLE `cursos` ADD CONSTRAINT `fk_cursos_type` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE SET NULL;
-- ALTER TABLE `cursos` ADD CONSTRAINT `fk_cursos_modality` FOREIGN KEY (`event_modality_id`) REFERENCES `event_modalities` (`id`) ON DELETE SET NULL;

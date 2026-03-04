-- Adaptación de aulavirtual para qr_simple

-- 1. Actualizar tabla usuarios (Participants)
-- Verificar si las columnas existen antes de añadirlas (MySQL no soporta IF NOT EXISTS en ADD COLUMN directamente,
-- pero asumiremos que no existen dado el dump proporcionado).

ALTER TABLE `usuarios`
ADD COLUMN `identity_document` VARCHAR(20) DEFAULT NULL,
ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL,
ADD COLUMN `country` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX idx_usuarios_identity ON `usuarios` (`identity_document`);

-- 2. Actualizar tabla cursos (Events)
ALTER TABLE `cursos`
ADD COLUMN `event_code` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `description` TEXT DEFAULT NULL,
ADD COLUMN `status` ENUM('active', 'inactive', 'archived') DEFAULT 'active',
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX idx_cursos_code ON `cursos` (`event_code`);

-- 3. Crear tabla de inscripciones (Enrollments)
-- Usaremos 'curso_estudiantes' para seguir la convención de aulavirtual, pero mapeada a EventEnrollment
CREATE TABLE IF NOT EXISTS `curso_estudiantes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `curso_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `status` ENUM('active', 'inactive', 'completed') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_curso_usuario` (`curso_id`, `usuario_id`),
  CONSTRAINT `fk_ce_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ce_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Crear tablas de auspicios
CREATE TABLE IF NOT EXISTS `auspices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `logo_url` VARCHAR(255) DEFAULT NULL,
  `website_url` VARCHAR(255) DEFAULT NULL,
  `active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `curso_auspicios` (
  `curso_id` INT NOT NULL,
  `auspice_id` INT NOT NULL,
  PRIMARY KEY (`curso_id`, `auspice_id`),
  CONSTRAINT `fk_ca_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ca_auspice` FOREIGN KEY (`auspice_id`) REFERENCES `auspices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Adaptar tabla user (Admins)
ALTER TABLE `user`
ADD COLUMN `email` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `name` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `role` VARCHAR(20) DEFAULT 'admin';

-- Actualizar usuarios existentes con email ficticio si es necesario para login
UPDATE `user` SET `email` = CONCAT(username, '@aulavirtual.com') WHERE email IS NULL;

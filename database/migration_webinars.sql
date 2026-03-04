-- Migración para Webinars y Constancias

-- 1. Tabla Webinars
CREATE TABLE IF NOT EXISTS `webinars` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `fecha_inicio` DATE DEFAULT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `imagen_webinar` VARCHAR(255) DEFAULT NULL COMMENT 'Imagen principal del webinar',
  `imagen_banner` VARCHAR(255) DEFAULT NULL COMMENT 'Banner para correos o certificados',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla Constancias (Certificados de Webinars)
CREATE TABLE IF NOT EXISTS `constancias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `webinar_id` INT NOT NULL,
  `qr_codigo` VARCHAR(255) UNIQUE DEFAULT NULL COMMENT 'Código único para validación QR',
  `fecha_generacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_constancia_usuario_webinar` (`usuario_id`, `webinar_id`),
  CONSTRAINT `fk_constancia_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_constancia_webinar` FOREIGN KEY (`webinar_id`) REFERENCES `webinars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. (Opcional) Si deseas marcar las tablas antiguas como "a eliminar" en el futuro, 
-- podrías renombrarlas o simplemente ignorarlas. No las borramos aquí para preservar datos por seguridad.

-- Migración para el módulo de Constancias (Sistema paralelo dentro de QR Simple)

-- 1. Tabla para los eventos específicos de constancias (separado de los cursos principales)
CREATE TABLE IF NOT EXISTS `constancia_eventos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `fondo_constancia` VARCHAR(255) DEFAULT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla para los participantes registrados en constancias (Leads)
CREATE TABLE IF NOT EXISTS `constancia_leads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `documento_identidad` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `celular` VARCHAR(20) DEFAULT NULL,
  `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_documento` (`documento_identidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de configuración para el módulo
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` VARCHAR(50) PRIMARY KEY,
  `valor` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar valor por defecto para habilitar constancias
INSERT IGNORE INTO `configuracion` (`clave`, `valor`) VALUES ('constancias_habilitadas', '1');

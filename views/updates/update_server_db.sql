-- Script de actualización de base de datos para el servidor
-- Ejecutar este script en phpMyAdmin o usar apply_updates.php

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabla para los eventos específicos de constancias
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

-- 4. Tabla principal de constancias generadas
CREATE TABLE IF NOT EXISTS `constancias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT NOT NULL,
    `evento_id` INT NOT NULL,
    `codigo_verificacion` VARCHAR(50) NOT NULL UNIQUE,
    `qr_codigo` VARCHAR(255) NOT NULL,
    `fecha_generacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `ip_generacion` VARCHAR(45),
    `user_agent` VARCHAR(255),
    FOREIGN KEY (`lead_id`) REFERENCES `constancia_leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`evento_id`) REFERENCES `constancia_eventos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

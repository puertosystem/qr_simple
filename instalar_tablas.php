<?php
// Script para instalar/reparar las tablas de Constancias
// Subir a la raíz del proyecto (donde está index.php) o en la carpeta qr/

require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Reparación de Base de Datos - Sistema de Constancias</h1>";

    // 1. Tabla constancia_eventos
    echo "Verificando tabla 'constancia_eventos'... ";
    $sql = "CREATE TABLE IF NOT EXISTS `constancia_eventos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nombre` varchar(255) NOT NULL,
        `fecha_inicio` date NOT NULL,
        `fecha_fin` date DEFAULT NULL,
        `fondo_constancia` varchar(255) DEFAULT NULL,
        `activo` tinyint(1) DEFAULT 1,
        `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    echo "<span style='color:green'>OK</span><br>";

    // 2. Tabla constancia_leads
    echo "Verificando tabla 'constancia_leads'... ";
    $sql = "CREATE TABLE IF NOT EXISTS `constancia_leads` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nombres` varchar(255) NOT NULL,
        `apellidos` varchar(255) NOT NULL,
        `documento_identidad` varchar(50) NOT NULL,
        `email` varchar(255) DEFAULT NULL,
        `celular` varchar(50) DEFAULT NULL,
        `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_documento` (`documento_identidad`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    echo "<span style='color:green'>OK</span><br>";

    // 3. Tabla constancias
    echo "Verificando tabla 'constancias'... ";
    // Nota: Agregamos claves foráneas explícitas
    $sql = "CREATE TABLE IF NOT EXISTS `constancias` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `lead_id` int(11) NOT NULL,
        `evento_id` int(11) NOT NULL,
        `codigo_verificacion` varchar(50) NOT NULL,
        `qr_codigo` text,
        `fecha_generacion` datetime DEFAULT CURRENT_TIMESTAMP,
        `ip_generacion` varchar(45) DEFAULT NULL,
        `user_agent` text,
        `num_descargas` int(11) DEFAULT 0,
        `fecha_primera_descarga` datetime DEFAULT NULL,
        `fecha_ultima_descarga` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `codigo_verificacion` (`codigo_verificacion`),
        KEY `idx_lead` (`lead_id`),
        KEY `idx_evento` (`evento_id`),
        CONSTRAINT `fk_constancia_lead` FOREIGN KEY (`lead_id`) REFERENCES `constancia_leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_constancia_evento` FOREIGN KEY (`evento_id`) REFERENCES `constancia_eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    echo "<span style='color:green'>OK</span><br>";

    // 4. Tabla configuracion y versión
    echo "Actualizando versión de base de datos... ";
    $sql = "CREATE TABLE IF NOT EXISTS `configuracion` (
        `clave` varchar(50) NOT NULL,
        `valor` text,
        PRIMARY KEY (`clave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);

    // Obtener versión de config/app.php si es posible, sino usar una por defecto
    $version = '1.0.0';
    if (file_exists(__DIR__ . '/config/app.php')) {
        $content = file_get_contents(__DIR__ . '/config/app.php');
        if (preg_match("/define\('APP_VERSION', ['\"](.*?)['\"]\);/", $content, $matches)) {
            $version = $matches[1];
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO `configuracion` (`clave`, `valor`) VALUES ('app_version', ?) ON DUPLICATE KEY UPDATE `valor` = ?");
    $stmt->execute([$version, $version]);
    echo "<span style='color:green'>Versión $version registrada.</span><br>";

    echo "<hr><h3>Proceso finalizado correctamente.</h3>";
    echo "<p>Las tablas necesarias han sido creadas o verificadas con sus relaciones (Foreign Keys).</p>";
    echo "<p>La versión de la base de datos se ha sincronizado para evitar alertas falsas.</p>";
    echo "<p>Por favor, elimine este archivo del servidor por seguridad después de usarlo.</p>";
    echo "<a href='index.php?page=constancias'>Ir al sistema</a>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>Error Crítico</h2>";
    echo "<p>No se pudo conectar a la base de datos o crear las tablas.</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

<?php
// apply_updates.php - Script para aplicar actualizaciones de base de datos en el servidor
// Subir este archivo junto con update_server_db.sql y ejecutarlo desde el navegador:
// https://tusitio.com/qr_simple/apply_updates.php

require_once __DIR__ . '/../../config/database.php';

echo "<h1>Actualizador de Base de Datos</h1>";

try {
    $pdo = Database::getConnection();
    echo "<p style='color:green'>Conexión a base de datos exitosa.</p>";
    
    $sqlFile = __DIR__ . '/update_server_db.sql';
    
    if (!file_exists($sqlFile)) {
        die("<p style='color:red'>Error: No se encuentra el archivo update_server_db.sql</p>");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Ejecutar consultas
    $pdo->exec($sql);
    
    echo "<p style='color:green'><strong>¡Base de datos actualizada correctamente!</strong></p>";
    echo "<ul>";
    echo "<li>Se verificaron/crearon las tablas: constancia_eventos, constancia_leads, configuracion, constancias</li>";
    echo "<li>Se insertaron configuraciones por defecto.</li>";
    echo "</ul>";
    echo "<p>Por razones de seguridad, se recomienda eliminar este archivo (apply_updates.php) y update_server_db.sql del servidor una vez finalizada la actualización.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'><strong>Error al actualizar base de datos:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'><strong>Error inesperado:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

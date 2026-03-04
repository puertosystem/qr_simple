<?php
/**
 * Shim de compatibilidad para URLs antiguas de certificados
 * Redirige la petición al controlador de validación
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/CertificateController.php';

// El controlador ya maneja $_GET['codigo'] además de $_GET['code']
$controller = new CertificateController();
$controller->validate();

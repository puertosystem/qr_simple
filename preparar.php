<?php
/**
 * Script para preparar el paquete de actualización automáticamente.
 * Versión Web con interfaz visual.
 */

// Configuración de exclusiones
$excludeItems = [
    '.git', '.gitignore', '.gitattributes', '.idea', '.vscode', 'server_config', 'temp_updates',
    'create_update.ps1', 'preparar.php', 'config/config.php', 'config/database.php',
    'README.md', 'LICENSE', 'template', 'composer.json', 'composer.lock',
    'ftp_config.json', 'database', 'images', 'aulavirtual.sql' // Excluir config FTP, carpeta database, carpeta images y dump de BD del ZIP
];

$foldersToEmpty = []; // Ya no vaciamos, simplemente excluimos toda la carpeta images
$excludeExtensions = ['zip', 'rar', 'gz'];

$rootDir = __DIR__;
$configPath = $rootDir . '/config/app.php';

// Detectar versión
$version = 'unknown';
if (file_exists($configPath)) {
    $content = file_get_contents($configPath);
    if (preg_match("/define\('APP_VERSION', [\"'](.*?)[\"']\);/", $content, $matches)) {
        $version = $matches[1];
    }
}

$zipFileName = "update_{$version}.zip";
$zipPath = $rootDir . DIRECTORY_SEPARATOR . $zipFileName;
$jsonPath = $rootDir . '/update_info.json';
$ftpConfigPath = $rootDir . '/ftp_config.json'; // Configuración FTP local (IGNORADA EN GIT)

// Guardar configuración FTP
if (isset($_POST['action']) && $_POST['action'] === 'save_ftp_config') {
    header('Content-Type: application/json');
    $config = [
        'host' => $_POST['ftp_host'] ?? '',
        'user' => $_POST['ftp_user'] ?? '',
        'pass' => $_POST['ftp_pass'] ?? '', // Se guarda en texto plano localmente, cuidado
        'path' => $_POST['ftp_path'] ?? '/'
    ];
    file_put_contents($ftpConfigPath, json_encode($config));
    echo json_encode(['status' => 'success', 'message' => 'Configuración FTP guardada.']);
    exit;
}

// Subir archivos vía FTP
if (isset($_POST['action']) && $_POST['action'] === 'upload_ftp') {
    header('Content-Type: application/json');

    if (!file_exists($ftpConfigPath)) {
        echo json_encode(['status' => 'error', 'message' => 'No hay configuración FTP guardada.']);
        exit;
    }

    $config = json_decode(file_get_contents($ftpConfigPath), true);
    $host = $config['host'];
    $user = $config['user'];
    $pass = $config['pass'];
    $remotePath = rtrim($config['path'], '/');

    // Conectar
    $conn_id = @ftp_connect($host);
    if (!$conn_id) {
        echo json_encode(['status' => 'error', 'message' => "No se pudo conectar al servidor FTP: $host"]);
        exit;
    }

    // Login
    if (!@ftp_login($conn_id, $user, $pass)) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña FTP incorrectos.']);
        ftp_close($conn_id);
        exit;
    }

    // Modo pasivo
    ftp_pasv($conn_id, true);

    // Obtener directorio actual para referencias
    $currentDir = ftp_pwd($conn_id);

    // Función recursiva para crear directorios
    function ftp_mksubdirs($ftpcon, $ftpbasedir, $ftpath){
        @ftp_chdir($ftpcon, $ftpbasedir);
        $parts = explode('/', $ftpath);
        foreach($parts as $part){
            if(!$part) continue;
            if(!@ftp_chdir($ftpcon, $part)){
                @ftp_mkdir($ftpcon, $part);
                @ftp_chdir($ftpcon, $part);
            }
        }
    }

    // Intentar cambiar directorio (con lógica inteligente para public_html)
    $chdirSuccess = false;

    // 1. Intentar ruta tal cual
    if (@ftp_chdir($conn_id, $remotePath)) {
        $chdirSuccess = true;
    } 
    // 2. Si falla y empieza con public_html/, intentar quitándolo (común en cPanel/Hestia)
    elseif (strpos($remotePath, 'public_html/') === 0) {
        $strippedPath = substr($remotePath, 12); // Quitar 'public_html/'
        if (@ftp_chdir($conn_id, $strippedPath)) {
            $remotePath = $strippedPath; // Actualizar ruta para el futuro
            $chdirSuccess = true;
        }
    }

    // Si no se pudo cambiar, intentar crear
    if (!$chdirSuccess) {
        // Intentar crear directorio recursivamente
        ftp_mksubdirs($conn_id, $currentDir, $remotePath);
        
        if (!@ftp_chdir($conn_id, $remotePath)) {
             // Si falla el chdir final, devolver error con detalles
             $errorMsg = error_get_last()['message'] ?? 'Desconocido';
             $finalDir = ftp_pwd($conn_id);
             
             // Obtener listado de archivos para ayudar al usuario a ubicarse
             $filesList = @ftp_nlist($conn_id, '.');
             $filesStr = $filesList ? implode(', ', array_slice($filesList, 0, 8)) : 'No disponible';

             echo json_encode([
                 'status' => 'error', 
                 'message' => "No se pudo acceder al directorio: $remotePath. (Ubicación actual: $finalDir). Carpetas aquí: [$filesStr]. Verifique la ruta."
             ]);
             ftp_close($conn_id);
             exit;
        }
    }

    // Subir update_info.json
    $upload1 = ftp_put($conn_id, 'update_info.json', $jsonPath, FTP_ASCII);
    
    // Subir ZIP
    $version = $_POST['version'] ?? ''; // Versión actual para el nombre del ZIP
    if(!$version) {
         // Fallback a leer del json si no viene por POST
         $jd = json_decode(file_get_contents($jsonPath), true);
         $version = $jd['version'];
    }
    $zipName = "update_{$version}.zip";
    $localZipPath = $rootDir . DIRECTORY_SEPARATOR . $zipName;

    if (!file_exists($localZipPath)) {
        echo json_encode(['status' => 'error', 'message' => "No se encuentra el archivo ZIP: $zipName"]);
        ftp_close($conn_id);
        exit;
    }

    $upload2 = ftp_put($conn_id, $zipName, $localZipPath, FTP_BINARY);

    ftp_close($conn_id);

    if ($upload1 && $upload2) {
        echo json_encode(['status' => 'success', 'message' => 'Archivos subidos correctamente al FTP.']);
    } else {
        $msg = '';
        if(!$upload1) $msg .= 'Fallo update_info.json. ';
        if(!$upload2) $msg .= 'Fallo ZIP. ';
        echo json_encode(['status' => 'error', 'message' => 'Error al subir archivos: ' . $msg]);
    }
    exit;
}

// Acción: Probar conexión FTP y listar directorio
if (isset($_POST['action']) && $_POST['action'] === 'test_ftp') {
    header('Content-Type: application/json');

    $host = $_POST['host'] ?? '';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    if (!$host || !$user || !$pass) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos de conexión.']);
        exit;
    }

    $conn_id = @ftp_connect($host);
    if (!$conn_id) {
        echo json_encode(['status' => 'error', 'message' => "No se pudo conectar al host: $host"]);
        exit;
    }

    if (!@ftp_login($conn_id, $user, $pass)) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
        ftp_close($conn_id);
        exit;
    }

    ftp_pasv($conn_id, true);
    // Listar directorio inicial
    $pwd = ftp_pwd($conn_id);
    $list = @ftp_nlist($conn_id, '.');
    $listStr = $list ? implode(', ', array_slice($list, 0, 8)) : '(Vacío o error)';
    
    echo json_encode(['status' => 'success', 'message' => "Conexión exitosa. <br>Ruta inicial: <b>$pwd</b><br>Contenido: <b>[$listStr]</b>"]);
    ftp_close($conn_id);
    exit;
}

// Lógica para actualizar la versión (POST)
if (isset($_POST['action']) && $_POST['action'] === 'update_version') {
    header('Content-Type: application/json');
    
    $newVersion = $_POST['version'] ?? '';
    $newDate = $_POST['date'] ?? date('Y-m-d');
    $newDesc = $_POST['description'] ?? '';

    if (!$newVersion) {
        echo json_encode(['status' => 'error', 'message' => 'La versión es requerida.']);
        exit;
    }

    // 1. Actualizar config/app.php
    if (file_exists($configPath)) {
        $content = "<?php\ndefine('APP_VERSION', '$newVersion');\n";
        file_put_contents($configPath, $content);
    }

    // 2. Actualizar update_info.json
    if (file_exists($jsonPath)) {
        $jsonData = json_decode(file_get_contents($jsonPath), true);
        if (!$jsonData) $jsonData = [];

        $jsonData['version'] = $newVersion;
        $jsonData['date'] = $newDate;
        $jsonData['description'] = $newDesc;

        // Actualizar URL de descarga automáticamente manteniendo la base
        if (isset($jsonData['download_url'])) {
            // Reemplaza update_X.X.X.zip por update_NUEVA_VERSION.zip
            $jsonData['download_url'] = preg_replace(
                '/update_.*?.zip/',
                "update_{$newVersion}.zip",
                $jsonData['download_url']
            );
        }

        file_put_contents($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    // 3. Actualizar update_server_db.sql con la versión de la base de datos y RENOMBRARLO
    $dbVersion = $_POST['db_version'] ?? '';
    
    // Buscar archivo existente
    $sqlFiles = glob(__DIR__ . '/views/updates/update_server_db*.sql');
    $currentSqlPath = !empty($sqlFiles) ? end($sqlFiles) : __DIR__ . '/views/updates/update_server_db.sql';
    
    // Nuevo nombre de archivo basado en la versión
    $newSqlFileName = "update_server_db_{$dbVersion}.sql";
    $newSqlPath = __DIR__ . "/views/updates/{$newSqlFileName}";

    if ($dbVersion) {
        // Si no existe el archivo actual, crearlo (caso base)
        if (!file_exists($currentSqlPath)) {
            $sqlContent = "INSERT INTO `configuracion` (`clave`, `valor`) VALUES ('app_version', '$dbVersion') ON DUPLICATE KEY UPDATE `valor` = '$dbVersion';";
            file_put_contents($newSqlPath, $sqlContent);
        } else {
            $sqlContent = file_get_contents($currentSqlPath);
            
            // Buscar y reemplazar la versión en el INSERT/UPDATE
            $pattern = "/VALUES \('app_version', '.*?'\)/";
            $replacement = "VALUES ('app_version', '$dbVersion')";
            $newSqlContent = preg_replace($pattern, $replacement, $sqlContent);
            
            $pattern2 = "/UPDATE `valor` = '.*?'/";
            $replacement2 = "UPDATE `valor` = '$dbVersion'";
            $newSqlContent = preg_replace($pattern2, $replacement2, $newSqlContent);

            // Escribir en el nuevo archivo (o sobrescribir si es el mismo)
            file_put_contents($newSqlPath, $newSqlContent);
            
            // Si el nombre cambió, borrar el viejo
            if (realpath($currentSqlPath) !== realpath($newSqlPath)) {
                unlink($currentSqlPath);
            }
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Versión y archivos actualizados correctamente.']);
    exit;
}

// Acción: Exportar Base de Datos Local
if (isset($_POST['action']) && $_POST['action'] === 'export_db') {
    header('Content-Type: application/json');
    
    $version = $_POST['version'] ?? '1.0.0';
    $exportType = $_POST['type'] ?? 'structure'; // structure, full
    
    require_once __DIR__ . '/config/database.php';
    
    try {
        $pdo = Database::getConnection();
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $sql = "-- Actualización de Base de Datos - Versión $version\n";
        $sql .= "-- Generado automáticamente desde preparar.php\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            // Estructura
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            if ($row) {
                $createTable = $row[1];
                // Asegurar IF NOT EXISTS
                $createTable = preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $createTable);
                $sql .= "-- Estructura de tabla `$table`\n";
                $sql .= "$createTable;\n\n";
            }
            
            // Datos (si se requiere, por ahora solo estructura para actualizaciones seguras)
            // Para actualizaciones, mejor NO exportar datos masivos para no sobrescribir clientes.
        }
        
        $sql .= "-- Actualizar versión de base de datos\n";
        $sql .= "CREATE TABLE IF NOT EXISTS `configuracion` (\n";
        $sql .= "  `clave` varchar(50) NOT NULL,\n";
        $sql .= "  `valor` text,\n";
        $sql .= "  PRIMARY KEY (`clave`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
        
        $sql .= "INSERT INTO `configuracion` (`clave`, `valor`) VALUES ('app_version', '$version')\n";
        $sql .= "ON DUPLICATE KEY UPDATE `valor` = '$version';\n\n";
        
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Limpiar archivos anteriores
        $oldFiles = glob(__DIR__ . '/views/updates/update_server_db*.sql');
        foreach ($oldFiles as $file) {
            @unlink($file);
        }
        
        // Guardar nuevo archivo
        $filename = "update_server_db_{$version}.sql";
        $filepath = __DIR__ . "/views/updates/{$filename}";
        file_put_contents($filepath, $sql);
        
        echo json_encode(['status' => 'success', 'message' => "Base de datos exportada correctamente a views/updates/$filename"]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error exportando BD: ' . $e->getMessage()]);
    }
    exit;
}

// Leer datos actuales para el formulario
$currentJsonData = [];
if (file_exists($jsonPath)) {
    $currentJsonData = json_decode(file_get_contents($jsonPath), true);
}
$formVersion = $currentJsonData['version'] ?? $version; // $version viene de UpdateController
$formDate = $currentJsonData['date'] ?? date('Y-m-d');
$formDesc = $currentJsonData['description'] ?? '';

// Leer versión actual de la base de datos del archivo SQL
$sqlFiles = glob(__DIR__ . '/views/updates/update_server_db*.sql');
$sqlPath = !empty($sqlFiles) ? end($sqlFiles) : '';
$formDbVersion = $formVersion; // Por defecto igual a la del sistema

if ($sqlPath && file_exists($sqlPath)) {
    $sqlContent = file_get_contents($sqlPath);
    if (preg_match("/VALUES \('app_version', '(.*?)'\)/", $sqlContent, $matches)) {
        $formDbVersion = $matches[1];
    }
}


// Leer configuración FTP si existe
$ftpHost = '';
$ftpUser = '';
$ftpPass = '';
$ftpPath = '/';
if (file_exists($ftpConfigPath)) {
    $ftpConfig = json_decode(file_get_contents($ftpConfigPath), true);
    $ftpHost = $ftpConfig['host'] ?? '';
    $ftpUser = $ftpConfig['user'] ?? '';
    $ftpPass = $ftpConfig['pass'] ?? '';
    $ftpPath = $ftpConfig['path'] ?? '/';
}

// Lógica de procesamiento AJAX (SSE)
if (isset($_GET['action']) && $_GET['action'] === 'create_zip') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    // Desactivar buffer para streaming
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', 1);
    }
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);
    for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
    ob_implicit_flush(1);

    function sendMsg($type, $msg, $progress = 0) {
        echo "data: " . json_encode(['type' => $type, 'message' => $msg, 'progress' => $progress]) . "\n\n";
        flush();
    }

    if (!class_exists('ZipArchive')) {
        sendMsg('error', 'La extensión ZipArchive no está habilitada en PHP.');
        exit;
    }

    if (file_exists($zipPath)) {
        @unlink($zipPath);
    }

    // Validar archivos de base de datos requeridos
    // update_server_db.sql ahora es versionado, así que verificamos si existe alguno
    $dbFiles = glob($rootDir . '/views/updates/update_server_db*.sql');
    if (empty($dbFiles)) {
         sendMsg('error', "Falta archivo SQL de actualización (views/updates/update_server_db*.sql).");
         exit;
    }

    if (!file_exists($rootDir . '/views/updates/apply_updates.php')) {
         sendMsg('error', "Falta archivo requerido: views/updates/apply_updates.php.");
         exit;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        sendMsg('error', 'No se puede crear el archivo ZIP. Verifica permisos.');
        exit;
    }

    // Contar archivos primero para la barra de progreso
    sendMsg('info', 'Escaneando archivos...', 5);
    $directory = new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
    
    $filesToAdd = [];
    foreach ($iterator as $file) {
        $filesToAdd[] = $file;
    }
    $totalFiles = count($filesToAdd);
    
    $fileCount = 0;
    $excludedCount = 0;
    $processed = 0;

    foreach ($filesToAdd as $file) {
        $processed++;
        $realPath = $file->getRealPath();
        $relativePath = substr($realPath, strlen($rootDir) + 1);
        $relativePathNormalized = str_replace('\\', '/', $relativePath);
        
        // Lógica de exclusión
        $exclude = false;

        // 1. Excluir propios archivos del script y ZIP
        if ($relativePathNormalized === 'preparar.php' || $relativePathNormalized === $zipFileName) $exclude = true;

        // 2. Lista de exclusión
        foreach ($excludeItems as $item) {
            if ($relativePathNormalized === $item || strpos($relativePathNormalized, $item . '/') === 0) {
                $exclude = true;
                break;
            }
        }

        // 3. Contenido de carpetas específicas
        foreach ($foldersToEmpty as $folder) {
            if (strpos($relativePathNormalized, $folder . '/') === 0) {
                $exclude = true;
                break;
            }
        }

        // 4. Extensiones
        $ext = pathinfo($relativePathNormalized, PATHINFO_EXTENSION);
        if (in_array($ext, $excludeExtensions)) $exclude = true;

        if ($exclude) {
            $excludedCount++;
            continue;
        }

        $zip->addFile($realPath, $relativePathNormalized);
        $fileCount++;

        // Actualizar progreso cada 50 archivos para no saturar
        if ($processed % 50 === 0 || $processed === $totalFiles) {
            $percent = round(($processed / $totalFiles) * 100);
            sendMsg('progress', "Procesando: $relativePathNormalized", $percent);
        }
    }

    $zip->close();
    
    $fileSize = round(filesize($zipPath) / 1024 / 1024, 2); // MB
    
    sendMsg('success', "¡Paquete creado exitosamente!<br><b>Archivo:</b> $zipFileName<br><b>Tamaño:</b> $fileSize MB<br><b>Incluidos:</b> $fileCount | <b>Excluidos:</b> $excludedCount", 100);
    echo "retry: 10000\n\n"; // Stop retrying
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Actualizaciones</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { background-color: #e9ecef; padding-top: 30px; padding-bottom: 30px; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); border-radius: 0.5rem; }
        .card-header { border-top-left-radius: 0.5rem !important; border-top-right-radius: 0.5rem !important; font-weight: 600; }
        .log-console { 
            background: #1e1e1e; color: #00ff00; font-family: 'Consolas', 'Monaco', monospace; 
            padding: 15px; height: 350px; overflow-y: auto; border-radius: 4px; font-size: 13px;
            border: 1px solid #333;
        }
        .progress { height: 25px; border-radius: 4px; background-color: #dee2e6; }
        .form-control { border-radius: 4px; }
        .btn { border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container-fluid px-4">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h3 class="text-dark mb-0"><i class="fas fa-cubes text-primary mr-2"></i>Generador de Actualizaciones</h3>
            </div>
            <div class="col-auto">
                 <span class="badge badge-secondary p-2">v<?php echo $version; ?></span>
            </div>
        </div>

        <div class="row">
            <!-- Columna 1: Configuración de Versión -->
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white text-primary border-bottom-0 pt-4 pb-0">
                        <h5 class="card-title"><span class="badge badge-primary mr-2">1</span>Versión del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <form id="version-form">
                            <div class="form-group">
                                <label class="text-muted small text-uppercase font-weight-bold">Versión del Sistema</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-weight-bold text-dark" id="version-input" name="version" value="<?php echo $formVersion; ?>" style="font-size: 1.2rem;">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-primary" type="button" id="btn-increment" title="Incrementar Patch (+0.0.1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="text-muted small text-uppercase font-weight-bold">Versión de Base de Datos</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-weight-bold text-info" id="db-version-input" name="db_version" value="<?php echo $formDbVersion; ?>" style="font-size: 1.2rem;">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-info" type="button" id="btn-sync-db" title="Sincronizar con Versión del Sistema">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                        <button class="btn btn-outline-success btn-export-db-action" type="button" title="Exportar Estructura BD Local">
                                            <i class="fas fa-database"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Define la versión de la estructura de base de datos. <br>
                                    <i class="fas fa-database text-success"></i> Exporta la estructura local actual a un archivo SQL versionado.
                                </small>
                            </div>


                            <div class="form-group">
                                <label class="text-muted small text-uppercase font-weight-bold">Fecha de Lanzamiento</label>
                                <input type="date" class="form-control" name="date" value="<?php echo $formDate; ?>">
                            </div>

                            <div class="form-group">
                                <label class="text-muted small text-uppercase font-weight-bold">Notas de la Versión</label>
                                <textarea class="form-control" name="description" rows="4" placeholder="Describe los cambios principales..."><?php echo htmlspecialchars($formDesc); ?></textarea>
                            </div>

                            <div class="alert alert-secondary mt-4 mb-0 pt-3 pb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-uppercase font-weight-bold text-muted">Archivo ZIP</small>
                                    <span class="badge badge-dark" id="display-version-badge"><?php echo $version; ?></span>
                                </div>
                                <code id="display-filename" class="text-dark font-weight-bold" style="font-size: 0.9rem;"><?php echo "update_{$version}.zip"; ?></code>
                            </div>

                            <div class="mt-4">
                                <button type="button" class="btn btn-info" id="btn-update-version">
                                    <i class="fas fa-save mr-1"></i> Actualizar Versión (Solo Config)
                                </button>
                                <button type="button" class="btn btn-success ml-2 btn-export-db-action">
                                    <i class="fas fa-database mr-1"></i> Exportar Estructura BD Local
                                </button>
                                <div id="version-msg" class="mt-2 text-left small font-weight-bold" style="min-height: 20px;"></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Columna 2: Configuración FTP -->
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white text-info border-bottom-0 pt-4 pb-0">
                        <h5 class="card-title"><span class="badge badge-info mr-2">2</span>Configuración FTP</h5>
                    </div>
                    <div class="card-body">
                        <form id="ftp-form">
                            <div class="form-group">
                                <label class="text-muted small text-uppercase font-weight-bold">Servidor FTP</label>
                                <input type="text" class="form-control" name="ftp_host" value="<?php echo $ftpHost; ?>" placeholder="ftp.ejemplo.com">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="text-muted small text-uppercase font-weight-bold">Usuario</label>
                                        <input type="text" class="form-control" name="ftp_user" value="<?php echo $ftpUser; ?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="text-muted small text-uppercase font-weight-bold">Contraseña</label>
                                        <input type="password" class="form-control" name="ftp_pass" value="<?php echo $ftpPass; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="text-muted small text-uppercase font-weight-bold">Ruta Remota</label>
                                <input type="text" class="form-control" name="ftp_path" value="<?php echo $ftpPath; ?>" placeholder="/public_html/updates/">
                            </div>
                            <button type="submit" class="btn btn-info btn-block shadow-sm" id="btn-save-ftp">
                                <i class="fas fa-save mr-2"></i>Guardar Credenciales
                            </button>
                            <button type="button" class="btn btn-outline-info btn-block shadow-sm mt-2" id="btn-test-ftp">
                                <i class="fas fa-plug mr-2"></i>Probar Conexión
                            </button>
                            <div id="ftp-msg" class="mt-2 text-center small font-weight-bold" style="min-height: 20px;"></div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Columna 3: Ejecución -->
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white text-dark border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><span class="badge badge-dark mr-2">3</span>Generar y Publicar</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="form-group">
                            <label class="text-muted small text-uppercase font-weight-bold">Progreso</label>
                            <div class="progress shadow-sm">
                                <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%">0%</div>
                            </div>
                        </div>

                        <div class="form-group flex-grow-1">
                            <label class="text-muted small text-uppercase font-weight-bold">Log del Sistema</label>
                            <div id="console" class="log-console shadow-inner">Esperando iniciar operación...</div>
                        </div>

                        <button id="btn-start" class="btn btn-primary btn-lg btn-block shadow mt-3">
                            <i class="fas fa-play mr-2"></i> Generar Paquete ZIP
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Éxito y Subida FTP -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-success shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel"><i class="fas fa-check-circle mr-2"></i>¡Paquete Creado Exitosamente!</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="modal-success-msg" class="mb-3"></div>
                    
                    <div class="alert alert-light border shadow-sm">
                        <small class="text-muted d-block font-weight-bold text-uppercase mb-1">Ruta de destino FTP:</small>
                        <code class="text-primary"><?php echo $ftpPath; ?></code>
                    </div>

                    <div id="modal-upload-msg" class="text-center font-weight-bold mb-2"></div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button id="btn-upload-modal" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-cloud-upload-alt mr-2"></i>Subir al Servidor FTP
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación FTP -->
    <div class="modal fade" id="confirmUploadModal" tabindex="-1" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-info shadow-lg">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-question-circle mr-2"></i>Confirmar Subida FTP</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div id="confirm-content">
                        <p class="font-weight-bold mb-2">¿Estás seguro de subir los archivos al servidor?</p>
                        <p class="small text-muted mb-0">Esta acción reemplazará la versión actual en la ruta especificada.</p>
                        <div class="alert alert-secondary mt-3 mb-0 py-2">
                            <code class="text-dark"><?php echo $ftpPath; ?></code>
                        </div>
                    </div>
                    <div id="upload-progress-container" class="d-none mt-3">
                        <p class="font-weight-bold text-info mb-2"><i class="fas fa-spinner fa-spin mr-2"></i>Subiendo archivos...</p>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                    <div id="upload-result-container" class="d-none mt-3">
                        <div id="upload-result-icon" class="mb-2" style="font-size: 3rem;"></div>
                        <p id="upload-result-msg" class="font-weight-bold mb-0"></p>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="btn-cancel-upload">Cancelar</button>
                    <button id="btn-confirm-upload" class="btn btn-info font-weight-bold px-4">
                        <i class="fas fa-check mr-2"></i>Sí, Subir Archivos
                    </button>
                    <button id="btn-close-upload" class="btn btn-secondary d-none" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const btnStart = document.getElementById('btn-start');
        const consoleDiv = document.getElementById('console');
        const progressBar = document.getElementById('progress-bar');
        const successMsg = document.getElementById('success-msg');
        
        const modalSuccessMsg = document.getElementById('modal-success-msg');
        const btnUploadModal = document.getElementById('btn-upload-modal'); // Botón en el modal de éxito
        
        // Elementos del modal de confirmación
        const confirmModal = $('#confirmUploadModal');
        const btnConfirmUpload = document.getElementById('btn-confirm-upload');
        const btnCancelUpload = document.getElementById('btn-cancel-upload');
        const btnCloseUpload = document.getElementById('btn-close-upload');
        const confirmContent = document.getElementById('confirm-content');
        const uploadProgressContainer = document.getElementById('upload-progress-container');
        const uploadResultContainer = document.getElementById('upload-result-container');
        const uploadResultIcon = document.getElementById('upload-result-icon');
        const uploadResultMsg = document.getElementById('upload-result-msg');

        const versionForm = document.getElementById('version-form');
        const versionInput = document.getElementById('version-input');
        const btnIncrement = document.getElementById('btn-increment');
        const btnUpdateVersion = document.getElementById('btn-update-version');
        const versionMsg = document.getElementById('version-msg');
        const displayVersionBadge = document.getElementById('display-version-badge');
        const displayFilename = document.getElementById('display-filename');

        // Incrementar versión
        btnIncrement.addEventListener('click', function() {
            let v = versionInput.value.split('.');
            if (v.length === 3) {
                v[2] = parseInt(v[2]) + 1;
                versionInput.value = v.join('.');
            } else {
                alert('Formato de versión no reconocido (x.x.x)');
            }
        });

        // Sincronizar versión de DB
        const btnSyncDb = document.getElementById('btn-sync-db');
        const dbVersionInput = document.getElementById('db-version-input');

        if(btnSyncDb) {
            btnSyncDb.addEventListener('click', function() {
                dbVersionInput.value = versionInput.value;
            });
        }

        // Guardar versión
        if (btnUpdateVersion) {
            btnUpdateVersion.addEventListener('click', function() {
                const formData = new FormData(versionForm);
                formData.append('action', 'update_version');

                btnUpdateVersion.disabled = true;
                if(versionMsg) {
                    versionMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                    versionMsg.className = 'mt-2 text-left small font-weight-bold text-info';
                }

                fetch('preparar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    btnUpdateVersion.disabled = false;
                    if (data.status === 'success') {
                        if(versionMsg) {
                            versionMsg.innerHTML = '<i class="fas fa-check"></i> Guardado';
                            versionMsg.className = 'mt-2 text-left small font-weight-bold text-success';
                        }
                        
                        // Actualizar vista previa
                        const newVer = formData.get('version');
                        if(displayVersionBadge) displayVersionBadge.textContent = newVer;
                        if(displayFilename) displayFilename.textContent = `update_${newVer}.zip`;
                        
                        // Resetear mensaje después de 3 seg
                        setTimeout(() => { if(versionMsg) versionMsg.innerHTML = ''; }, 3000);
                    } else {
                        if(versionMsg) {
                            versionMsg.textContent = data.message;
                            versionMsg.className = 'mt-2 text-left small font-weight-bold text-danger';
                        }
                    }
                })
                .catch(err => {
                    btnUpdateVersion.disabled = false;
                    if(versionMsg) {
                        versionMsg.textContent = 'Error de conexión';
                        versionMsg.className = 'mt-2 text-left small font-weight-bold text-danger';
                    }
                    console.error(err);
                });
            });
        }

        function log(msg, type = 'info') {
            const time = new Date().toLocaleTimeString();
            let color = '#00ff00';
            if(type === 'error') color = '#ff5555';
            if(type === 'warning') color = '#ffff55';
            
            consoleDiv.innerHTML += `<div style="color:${color}; margin-bottom: 2px;">[${time}] ${msg}</div>`;
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        }

        btnStart.addEventListener('click', function() {
            btnStart.disabled = true;
            // resultArea.classList.add('d-none');
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            progressBar.classList.remove('bg-danger', 'bg-success');
            progressBar.classList.add('bg-primary');
            
            consoleDiv.innerHTML = '';
            log('Iniciando proceso de empaquetado...', 'info');

            const eventSource = new EventSource('preparar.php?action=create_zip');

            eventSource.onmessage = function(e) {
                const data = JSON.parse(e.data);

                if (data.type === 'progress') {
                    progressBar.style.width = data.progress + '%';
                    progressBar.textContent = data.progress + '%';
                    // log(data.message); 
                } else if (data.type === 'success') {
                    eventSource.close();
                    progressBar.style.width = '100%';
                    progressBar.textContent = '100%';
                    progressBar.classList.remove('bg-primary');
                    progressBar.classList.add('bg-success');
                    log('PROCESO COMPLETADO', 'info');
                    
                    // Mostrar Modal de Éxito
                    modalSuccessMsg.innerHTML = data.message;
                    $('#successModal').modal('show');

                    btnStart.disabled = false;
                    btnStart.innerHTML = '<i class="fas fa-redo mr-2"></i> Crear Nuevo Paquete';
                } else if (data.type === 'error') {
                    eventSource.close();
                    log('ERROR FATAL: ' + data.message, 'error');
                    btnStart.disabled = false;
                    progressBar.classList.remove('bg-primary');
                    progressBar.classList.add('bg-danger');
                } else {
                    log(data.message, 'info');
                }
            };

            eventSource.onerror = function(e) {
                eventSource.close();
                // log('Conexión cerrada.', 'warning');
                btnStart.disabled = false;
            };
        });

        // Guardar FTP config
        const ftpForm = document.getElementById('ftp-form');
        const btnSaveFtp = document.getElementById('btn-save-ftp');
        const btnTestFtp = document.getElementById('btn-test-ftp');
        const ftpMsg = document.getElementById('ftp-msg');

        ftpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(ftpForm);
            formData.append('action', 'save_ftp_config');

            btnSaveFtp.disabled = true;
            ftpMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            ftpMsg.className = 'mt-2 text-center small font-weight-bold text-info';

            fetch('preparar.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                btnSaveFtp.disabled = false;
                ftpMsg.textContent = 'Guardado correctamente';
                ftpMsg.className = 'mt-2 text-center small font-weight-bold text-success';
                setTimeout(() => { ftpMsg.innerHTML = ''; }, 3000);
            })
            .catch(err => {
                btnSaveFtp.disabled = false;
                ftpMsg.textContent = 'Error al guardar';
                ftpMsg.className = 'mt-2 text-center small font-weight-bold text-danger';
            });
        });

        // Probar Conexión
        btnTestFtp.addEventListener('click', function() {
            const formData = new FormData(document.getElementById('ftp-form'));
            // Mapear nombres del formulario a los esperados por PHP (host, user, pass)
            formData.append('action', 'test_ftp');
            formData.append('host', formData.get('ftp_host'));
            formData.append('user', formData.get('ftp_user'));
            formData.append('pass', formData.get('ftp_pass'));


            btnTestFtp.disabled = true;
            ftpMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';
            ftpMsg.className = 'mt-2 text-center small font-weight-bold text-info';

            fetch('preparar.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                btnTestFtp.disabled = false;
                if(data.status === 'success') {
                    ftpMsg.innerHTML = data.message;
                    ftpMsg.className = 'mt-2 text-center small text-success';
                } else {
                    ftpMsg.textContent = data.message;
                    ftpMsg.className = 'mt-2 text-center small font-weight-bold text-danger';
                }
            })
            .catch(err => {
                btnTestFtp.disabled = false;
                ftpMsg.textContent = 'Error de conexión';
                ftpMsg.className = 'mt-2 text-center small font-weight-bold text-danger';
            });
        });

        // Exportar BD Local
        const btnsExportDb = document.querySelectorAll('.btn-export-db-action');
        btnsExportDb.forEach(btn => {
            btn.addEventListener('click', function() {
                const version = document.getElementById('db-version-input').value;
                if(!confirm('¿Desea exportar la estructura de la base de datos local y guardarla como actualización versión ' + version + '? Esto sobrescribirá los archivos SQL existentes en views/updates/.')) return;

                const formData = new FormData();
                formData.append('action', 'export_db');
                formData.append('version', version);
                
                log('Iniciando exportación de BD...', 'info');

                fetch('preparar.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        log(data.message, 'info');
                        alert('Base de datos exportada correctamente.');
                    } else {
                        log(data.message, 'error');
                        alert('Error al exportar BD: ' + data.message);
                    }
                })
                .catch(err => log('Error AJAX: ' + err, 'error'));
            });
        });

        // Lógica de Subida FTP con Modal
        
        // 1. Abrir modal de confirmación desde el modal de éxito
        btnUploadModal.addEventListener('click', function() {
            $('#successModal').modal('hide');
            
            // Resetear estado del modal de confirmación
            confirmContent.classList.remove('d-none');
            uploadProgressContainer.classList.add('d-none');
            uploadResultContainer.classList.add('d-none');
            btnConfirmUpload.classList.remove('d-none');
            btnCancelUpload.classList.remove('d-none');
            btnCloseUpload.classList.add('d-none');
            
            $('#confirmUploadModal').modal('show');
        });

        // 2. Confirmar y subir
        btnConfirmUpload.addEventListener('click', function() {
            const version = versionInput.value;
            const formData = new FormData();
            formData.append('action', 'upload_ftp');
            formData.append('version', version);

            // Cambiar UI a "Subiendo"
            confirmContent.classList.add('d-none');
            uploadProgressContainer.classList.remove('d-none');
            btnConfirmUpload.classList.add('d-none');
            btnCancelUpload.classList.add('d-none');

            log('Iniciando subida FTP...', 'info');

            fetch('preparar.php', { method: 'POST', body: formData })
            .then(r => {
                if (!r.ok) throw new Error('Network response was not ok');
                return r.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                // Ocultar progreso
                uploadProgressContainer.classList.add('d-none');
                uploadResultContainer.classList.remove('d-none');
                btnCloseUpload.classList.remove('d-none');

                if(data.status === 'success') {
                    uploadResultIcon.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
                    uploadResultMsg.innerHTML = data.message;
                    uploadResultMsg.className = 'text-success font-weight-bold';
                    log('SUBIDA FTP EXITOSA: ' + data.message, 'info');
                    
                    // Refrescar automáticamente la página después de 3 segundos
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                } else {
                    uploadResultIcon.innerHTML = '<i class="fas fa-times-circle text-danger"></i>';
                    uploadResultMsg.innerHTML = data.message;
                    uploadResultMsg.className = 'text-danger font-weight-bold';
                    log('ERROR FTP: ' + data.message, 'error');
                }
            })
            .catch(err => {
                uploadProgressContainer.classList.add('d-none');
                uploadResultContainer.classList.remove('d-none');
                btnCloseUpload.classList.remove('d-none');
                
                uploadResultIcon.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i>';
                uploadResultMsg.innerHTML = 'Error: ' + err.message;
                uploadResultMsg.className = 'text-danger font-weight-bold';
                log('ERROR: ' + err.message, 'error');
            });
        });
    </script>
</body>
</html>

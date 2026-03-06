<?php

class UpdateController {
    
    // CONFIGURACIÓN: URL de tu servidor (Hosting, Agencia, VPS)
    // Ejemplo: 'https://puertosystem.com/clientes/updates/qr_simple/update_info.json'
    private $updateUrl = 'https://puertosystem.com/updates/qr_simple/update_info.json';
    
    // CREDENCIALES (Opcional): Si proteges la carpeta con contraseña (.htaccess)
    private $updateUser = 'cliente_update';      // Usuario (si aplica)
    private $updatePassword = 'QrUpdate2026';  // Contraseña (si aplica)
    
    private $tempDir;

    public function __construct() {
        $this->tempDir = __DIR__ . '/../temp_updates/';
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    public function handleRequest() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'index';
        
        switch ($action) {
            case 'check':
                $this->checkUpdate();
                break;
            case 'process':
                $this->processUpdate();
                break;
            case 'apply_db':
                $this->applyDbUpdate();
                break;
            default:
                $this->index();
                break;
        }
    }

    public function applyDbUpdate() {
        header('Content-Type: application/json');
        
        $sqlFile = __DIR__ . '/../views/updates/update_server_db.sql';
        
        if (!file_exists($sqlFile)) {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró el archivo de actualización de base de datos (views/updates/update_server_db.sql).']);
            exit;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = Database::getConnection();
            
            $sql = file_get_contents($sqlFile);
            
            // Ejecutar consultas
            $pdo->exec($sql);
            
            // Eliminar el archivo después de actualizar para evitar que se ejecute de nuevo
            @unlink($sqlFile);
            
            echo json_encode(['status' => 'success', 'message' => 'Base de datos actualizada correctamente.']);
        } catch (Exception $e) {
            error_log('Error actualizando BD: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar SQL: ' . $e->getMessage()]);
        }
        exit;
    }

    public function index() {
        $requirements = $this->checkSystemRequirements();
        $currentVersion = APP_VERSION;
        
        // Verificar si existe una actualización de base de datos pendiente
        $sqlFile = __DIR__ . '/../views/updates/update_server_db.sql';
        $dbUpdateAvailable = file_exists($sqlFile);
        
        require __DIR__ . '/../views/updates/index.php';
    }

    private function checkSystemRequirements() {
        return [
            'php_version' => [
                'name' => 'PHP Version >= 7.4',
                'status' => version_compare(phpversion(), '7.4.0', '>='),
                'current' => phpversion()
            ],
            'zip_extension' => [
                'name' => 'Extensión Zip',
                'status' => extension_loaded('zip'),
                'current' => extension_loaded('zip') ? 'Habilitada' : 'Deshabilitada'
            ],
            'curl_extension' => [
                'name' => 'Extensión CURL',
                'status' => extension_loaded('curl'),
                'current' => extension_loaded('curl') ? 'Habilitada' : 'Deshabilitada'
            ],
            'writable_root' => [
                'name' => 'Permisos de Escritura (Raíz)',
                'status' => is_writable(__DIR__ . '/../'),
                'current' => is_writable(__DIR__ . '/../') ? 'Escritura permitida' : 'Solo lectura'
            ],
            'allow_url_fopen' => [
                'name' => 'allow_url_fopen',
                'status' => ini_get('allow_url_fopen'),
                'current' => ini_get('allow_url_fopen') ? 'On' : 'Off'
            ]
        ];
    }

    public function checkUpdate() {
        header('Content-Type: application/json');

        // Intentar obtener la información de la actualización
        $updateInfo = $this->getRemoteUpdateInfo();

        if (!$updateInfo) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo conectar con el servidor de actualizaciones. Verifica tu conexión a internet o la configuración de la URL.'
            ]);
            exit;
        }

        // Comparar versiones
        if (version_compare($updateInfo['version'], APP_VERSION, '>')) {
            echo json_encode([
                'status' => 'success',
                'update_available' => true,
                'version' => $updateInfo['version'],
                'description' => $updateInfo['description'],
                'date' => $updateInfo['date'],
                'download_url' => $updateInfo['download_url']
            ]);
        } else {
            echo json_encode([
                'status' => 'success',
                'update_available' => false,
                'message' => 'Tu sistema está actualizado. Tienes la última versión (' . APP_VERSION . ').'
            ]);
        }
        exit;
    }

    public function processUpdate() {
        header('Content-Type: application/json');
        
        // Verificar requisitos
        $reqs = $this->checkSystemRequirements();
        foreach ($reqs as $req) {
            if (!$req['status']) {
                echo json_encode(['status' => 'error', 'message' => 'No se cumplen los requisitos del sistema: ' . $req['name']]);
                exit;
            }
        }

        // 1. Obtener información de nuevo para asegurar la URL
        $updateInfo = $this->getRemoteUpdateInfo();
        if (!$updateInfo) {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo obtener la información de actualización.']);
            exit;
        }

        $downloadUrl = $updateInfo['download_url'];
        $zipFile = $this->tempDir . 'update_' . $updateInfo['version'] . '.zip';

        // 2. Descargar el archivo ZIP
        if (!$this->downloadFile($downloadUrl, $zipFile)) {
            echo json_encode(['status' => 'error', 'message' => 'Error al descargar el paquete de actualización.']);
            exit;
        }

        // 3. Descomprimir y actualizar
        if ($this->extractAndInstall($zipFile)) {
            // Limpiar
            @unlink($zipFile);
            echo json_encode(['status' => 'success', 'message' => 'Actualización instalada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al descomprimir o instalar los archivos.']);
        }
        exit;
    }

    private function getRemoteUpdateInfo() {
        $ch = curl_init($this->updateUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // Autenticación básica si se configuró usuario/pass
        if (!empty($this->updateUser) && !empty($this->updatePassword)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->updateUser . ":" . $this->updatePassword);
        }

        $json = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch); // Capturar error específico
        curl_close($ch);

        if ($httpCode === 200 && $json) {
            return json_decode($json, true);
        } else {
            // Loguear error para depuración
            error_log("Error UpdateController: HTTP $httpCode - CURL Error: $error - URL: " . $this->updateUrl);
        }
        
        // Fallback simulado SOLO para pruebas locales si la URL es de ejemplo
        if (strpos($this->updateUrl, 'tu-dominio.com') !== false) {
             return [
                'version' => '1.0.0', // Cambia esto a '1.1.0' para probar que hay actualización
                'date' => date('Y-m-d'),
                'description' => 'Esta es una respuesta simulada. Configura la variable $updateUrl en UpdateController.php para usar tu propio repositorio.',
                'download_url' => '#'
            ];
        }

        return null;
    }

    private function downloadFile($url, $path) {
        $fp = fopen($path, 'w+');
        if (!$fp) return false;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_FILE, $fp); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // Autenticación para la descarga también
        if (!empty($this->updateUser) && !empty($this->updatePassword)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->updateUser . ":" . $this->updatePassword);
        }
        
        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($httpCode !== 200) {
             error_log("Error UpdateController Download: HTTP $httpCode - CURL Error: $error - URL: $url");
             return false;
        }

        return $success;
    }

    private function extractAndInstall($zipFile) {
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            // Extraer en la raíz del proyecto (un nivel arriba de controllers)
            $extractPath = __DIR__ . '/../'; 
            
            // Extraer
            $zip->extractTo($extractPath);
            $zip->close();
            
            // Intentar ejecutar scripts SQL de actualización si existen
            // Buscamos un archivo con el nombre de la versión, ej: database/update_1.1.0.sql
            $version = $this->getUpdateVersionFromZipName($zipFile);
            if ($version) {
                // Actualizar la versión en config/app.php
                $this->updateAppVersion($version);
                
                // Ejecutar SQL si existe
                $this->runSqlUpdate($version);
            }
            
            return true;
        }
        return false;
    }

    private function updateAppVersion($version) {
        $configFile = __DIR__ . '/../config/app.php';
        $content = "<?php\ndefine('APP_VERSION', '" . $version . "');\n";
        file_put_contents($configFile, $content);
    }

    private function getUpdateVersionFromZipName($zipFile) {
        // Extraer versión del nombre del archivo: update_1.1.0.zip -> 1.1.0
        if (preg_match('/update_(.*?)\.zip/', basename($zipFile), $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function runSqlUpdate($version) {
        $sqlFile = __DIR__ . '/../database/update_' . $version . '.sql';
        
        if (file_exists($sqlFile)) {
            try {
                require_once __DIR__ . '/../config/database.php';
                $pdo = Database::getConnection();
                
                $sql = file_get_contents($sqlFile);
                
                // Ejecutar múltiples consultas
                $pdo->exec($sql);
                
                // Opcional: Eliminar el archivo SQL después de ejecutarlo para no dejar rastro
                // unlink($sqlFile);
                
                return true;
            } catch (Exception $e) {
                // Loguear error pero no detener la actualización completa (o sí, depende de la criticidad)
                error_log("Error ejecutando SQL de actualización $version: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }
}

<?php

class UpdateController {
    
    // CONFIGURACIÓN: Cambia esta URL por la dirección RAW de tu archivo JSON en GitHub o tu servidor
    // Ejemplo: 'https://raw.githubusercontent.com/usuario/repo/main/update_info.json'
    private $updateUrl = 'https://raw.githubusercontent.com/TU_USUARIO/TU_REPO/main/update_info.json';
    
    private $currentVersion = '1.0.0';
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
            default:
                $this->index();
                break;
        }
    }

    public function index() {
        $requirements = $this->checkSystemRequirements();
        $currentVersion = $this->currentVersion;
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
                'status' => is_writable(__DIR__ . '/../../'),
                'current' => is_writable(__DIR__ . '/../../') ? 'Escritura permitida' : 'Solo lectura'
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
        if (version_compare($updateInfo['version'], $this->currentVersion, '>')) {
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
                'message' => 'Tu sistema está actualizado. Tienes la última versión (' . $this->currentVersion . ').'
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
        // Opción 1: Usar cURL (Recomendado)
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->updateUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo para desarrollo/pruebas si hay problemas con SSL
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                return json_decode($response, true);
            }
        } 
        // Opción 2: file_get_contents (Fallback)
        else if (ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                'http' => ['timeout' => 10]
            ]);
            $response = @file_get_contents($this->updateUrl, false, $context);
            if ($response) {
                return json_decode($response, true);
            }
        }

        // Para propósitos de DEMOSTRACIÓN si no hay URL real configurada:
        // Si la URL contiene 'TU_USUARIO', devolvemos un JSON simulado para que el usuario vea cómo funciona.
        if (strpos($this->updateUrl, 'TU_USUARIO') !== false) {
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
        
        $success = curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        return $success;
    }

    private function extractAndInstall($zipFile) {
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            // Extraer en la raíz del proyecto (dos niveles arriba de controllers)
            $extractPath = __DIR__ . '/../../'; 
            
            // Extraer
            $zip->extractTo($extractPath);
            $zip->close();
            return true;
        }
        return false;
    }
}

<?php

class SettingsController {
    
    public function handleRequest() {
        $view = isset($_GET['view']) ? $_GET['view'] : 'license';
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        if ($action === 'save_license' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveLicense();
            return;
        }

        switch ($view) {
            case 'license':
                $this->license();
                break;
            default:
                $this->license();
                break;
        }
    }

    public function saveLicense() {
        $newKey = isset($_POST['license_key']) ? trim($_POST['license_key']) : '';
        
        // Guardar en archivo (simple storage por ahora)
        $configFile = __DIR__ . '/../config/license.key';
        file_put_contents($configFile, $newKey);
        
        // Redireccionar con mensaje
        header('Location: index.php?view=license&status=saved');
        exit;
    }

    public function license() {
        // Leer licencia desde archivo
        $configFile = __DIR__ . '/../config/license.key';
        $licenseKey = file_exists($configFile) ? file_get_contents($configFile) : '';
        
        // Si está vacía, usar placeholder para visualización si se desea, 
        // pero mejor dejarla vacía para que el usuario sepa que debe ingresarla.
        
        $localKey = ''; // Local key storage if needed
        
        // Mock WHMCS check
        $licenseData = $this->checkLicense($licenseKey);
        
        require __DIR__ . '/../views/settings/license.php';
    }

    private function checkLicense($licenseKey) {
        // ------------------------------------------------------------------
        // LÓGICA DE VALIDACIÓN DE LICENCIA (SIMULACIÓN)
        // ------------------------------------------------------------------
        // Para validar realmente contra un servidor externo (ej. tu web principal),
        // deberías usar cURL para enviar la $licenseKey a tu API endpoint.
        //
        // Ejemplo de implementación real:
        // $apiUrl = 'https://puertosystem.com/api/validate-license';
        // $response = file_get_contents($apiUrl . '?key=' . $licenseKey . '&domain=' . $_SERVER['HTTP_HOST']);
        // $data = json_decode($response, true);
        // return $data;
        // ------------------------------------------------------------------

        // Por ahora, retornamos datos simulados "Activos"
        return [
            'status' => 'Active', // Active, Suspended, Expired
            'message' => 'Licencia válida',
            'registeredname' => 'Cliente Final',
            'productname' => 'Sistema de Certificados QR Pro',
            'validdomain' => $_SERVER['HTTP_HOST'],
            'validip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
            'checkdate' => date('Y-m-d'),
            'nextduedate' => date('Y-m-d', strtotime('+1 year')),
            'version' => defined('APP_VERSION') ? APP_VERSION : '1.0.0'
        ];
    }
}

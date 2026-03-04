<?php

class SettingsController {
    
    public function handleRequest() {
        $view = isset($_GET['view']) ? $_GET['view'] : 'license';

        switch ($view) {
            case 'license':
                $this->license();
                break;
            default:
                $this->license();
                break;
        }
    }

    public function license() {
        $licenseKey = 'LICENSE-KEY-DEMO-123'; // Placeholder or fetch from DB/Config
        $localKey = ''; // Local key storage if needed
        
        // Mock WHMCS check
        $licenseData = $this->checkLicense($licenseKey);
        
        require __DIR__ . '/../views/settings/license.php';
    }

    private function checkLicense($licenseKey) {
        // Here you would implement the actual call to your WHMCS installation
        // using the check_token action or similar.
        // For now, we return a mock response.
        
        return [
            'status' => 'Active',
            'message' => 'Licencia válida',
            'registeredname' => 'Usuario Demo',
            'productname' => 'Sistema de Certificados QR Pro',
            'validdomain' => $_SERVER['HTTP_HOST'],
            'validip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
            'checkdate' => date('Y-m-d'),
            'nextduedate' => date('Y-m-d', strtotime('+1 year')),
            'version' => '1.0.0'
        ];
    }
}

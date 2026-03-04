<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Auspice.php';

class AuspiceController
{
    public function handleRequest(): void
    {
        $view = isset($_GET['view']) ? $_GET['view'] : 'list';
        $action = isset($_GET['action']) ? $_GET['action'] : null;

        if ($action === 'create') {
            $this->create();
            return;
        }

        if ($action === 'update') {
            $this->update();
            return;
        }

        if ($action === 'delete') {
            $this->delete();
            return;
        }

        if ($action === 'get') {
            $this->get();
            return;
        }

        // Default view
        try {
            $pdo = Database::getConnection();
            $auspices = Auspice::getAll($pdo);
            require __DIR__ . '/../views/auspices/index.php';
        } catch (Throwable $e) {
            error_log('Error loading auspices: ' . $e->getMessage());
            echo 'Error loading page.';
        }
    }

    private function handleFileUpload(): ?string
    {
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo_file'];
            $uploadDir = __DIR__ . '/../images/auspicio';
            
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($extension, $allowed)) {
                // Generate safe unique name
                $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', str_replace(' ', '_', $file['name']));
                
                if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $safeName)) {
                    return 'images/auspicio/' . $safeName;
                }
            }
        }
        return null;
    }

    private function create(): void
    {
        header('Content-Type: application/json');
        try {
            $pdo = Database::getConnection();
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $logo_url = trim($_POST['logo_url'] ?? ''); // Fallback or manual URL
            $website_url = trim($_POST['website_url'] ?? '');
            $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;

            if ($name === '') {
                echo json_encode(['error' => 'El nombre es obligatorio.']);
                return;
            }

            // Handle logo deletion
            if (isset($_POST['delete_logo']) && $_POST['delete_logo'] == '1') {
                $logo_url = '';
            }

            // Handle file upload
            $uploadedPath = $this->handleFileUpload();
            if ($uploadedPath) {
                $logo_url = $uploadedPath;
            }

            Auspice::create($pdo, [
                'name' => $name, 
                'code' => $code,
                'logo_url' => $logo_url,
                'website_url' => $website_url,
                'active' => $active
            ]);
            echo json_encode(['success' => true, 'message' => 'Auspicio creado correctamente.']);
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Error al crear auspicio: ' . $e->getMessage()]);
        }
        exit;
    }

    private function update(): void
    {
        header('Content-Type: application/json');
        try {
            $pdo = Database::getConnection();
            $id = $_POST['id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $logo_url = trim($_POST['logo_url'] ?? ''); // Existing or manual URL
            $website_url = trim($_POST['website_url'] ?? '');
            $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;

            if (!$id) {
                echo json_encode(['error' => 'ID no proporcionado.']);
                return;
            }

            if ($name === '') {
                echo json_encode(['error' => 'El nombre es obligatorio.']);
                return;
            }

            // Handle logo deletion
            if (isset($_POST['delete_logo']) && $_POST['delete_logo'] == '1') {
                $logo_url = '';
            }

            // Handle file upload
            $uploadedPath = $this->handleFileUpload();
            if ($uploadedPath) {
                $logo_url = $uploadedPath;
            }

            Auspice::update($pdo, $id, [
                'name' => $name, 
                'code' => $code,
                'logo_url' => $logo_url,
                'website_url' => $website_url,
                'active' => $active
            ]);
            echo json_encode(['success' => true, 'message' => 'Auspicio actualizado correctamente.']);
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Error al actualizar auspicio: ' . $e->getMessage()]);
        }
        exit;
    }

    private function delete(): void
    {
        header('Content-Type: application/json');
        try {
            $pdo = Database::getConnection();
            $id = $_POST['id'] ?? '';

            if (!$id) {
                echo json_encode(['error' => 'ID no proporcionado.']);
                return;
            }

            // Check if used in events? Usually yes, but simple delete for now.
            // Ideally should check foreign keys.
            // Assuming DB constraints will fail if used.
            
            Auspice::delete($pdo, $id);
            echo json_encode(['success' => true, 'message' => 'Auspicio eliminado correctamente.']);
        } catch (Throwable $e) {
             if (strpos($e->getMessage(), 'Constraint violation') !== false || strpos($e->getMessage(), 'foreign key') !== false) {
                echo json_encode(['error' => 'No se puede eliminar porque está asociado a cursos.']);
             } else {
                echo json_encode(['error' => 'Error al eliminar auspicio: ' . $e->getMessage()]);
             }
        }
        exit;
    }

    private function get(): void
    {
        header('Content-Type: application/json');
        try {
            $pdo = Database::getConnection();
            $id = $_GET['id'] ?? '';
            
            if (!$id) {
                echo json_encode(['error' => 'ID no proporcionado.']);
                return;
            }

            $auspice = Auspice::find($pdo, $id);
            if ($auspice) {
                echo json_encode($auspice);
            } else {
                echo json_encode(['error' => 'Auspicio no encontrado.']);
            }
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Error al obtener datos: ' . $e->getMessage()]);
        }
        exit;
    }
}

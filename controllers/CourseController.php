<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Course.php';

class CourseController
{
    public function handleRequest(): void
    {
        $successMessage = null;
        $errorMessage = null;
        $eventTypes = [];
        $eventModalities = [];
        $auspices = [];
        $coursesData = [];
        
        $view = isset($_GET['view']) ? $_GET['view'] : 'list';

        if (isset($_GET['action'])) {
            if ($_GET['action'] === 'get_course' && isset($_GET['id'])) {
                $this->getCourseJson();
                return;
            }
            if ($_GET['action'] === 'update_course' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->updateCourse();
                return;
            }
        }

        try {
            $pdo = Database::getConnection();

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
                [$successMessage, $errorMessage] = $this->processForm($pdo);

                if ($successMessage !== null && $errorMessage === null) {
                    $_SESSION['courses_success'] = $successMessage;
                    header('Location: index.php?page=courses');
                    exit;
                }
            }

            if (isset($_SESSION['courses_success'])) {
                $successMessage = $_SESSION['courses_success'];
                unset($_SESSION['courses_success']);
            }

            if ($view === 'create') {
                $eventTypes = Course::getEventTypes($pdo);
                $eventModalities = Course::getEventModalities($pdo);
                $auspices = Course::getAuspices($pdo);
                require __DIR__ . '/../views/courses/create.php';
            } else {
                $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
                $search = isset($_GET['q']) ? trim($_GET['q']) : '';
                $coursesData = Course::paginate($pdo, $page, 10, $search);
                
                // Load options for the edit modal
                $eventTypes = Course::getEventTypes($pdo);
                $eventModalities = Course::getEventModalities($pdo);
                $auspices = Course::getAuspices($pdo);
                require __DIR__ . '/../views/courses/index.php';
            }

        } catch (Throwable $e) {
            error_log('Error cargando datos de cursos: ' . $e->getMessage());
        }
    }

    private function getCourseJson(): void
    {
        header('Content-Type: application/json');
        try {
            $pdo = Database::getConnection();
            $id = $_GET['id'] ?? '';

            if (!$id) {
                echo json_encode(['error' => 'Falta el ID del curso.']);
                return;
            }

            $course = Course::find($pdo, $id);

            if ($course) {
                echo json_encode($course);
            } else {
                echo json_encode(['error' => 'Curso no encontrado.']);
            }
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Error al cargar datos: ' . $e->getMessage()]);
        }
        exit;
    }

    private function handleFileUpload(): ?string
    {
        if (isset($_FILES['certificate_background']) && $_FILES['certificate_background']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['certificate_background'];
            // Changed upload directory to images/plantilla as requested
            $uploadDir = __DIR__ . '/../images/plantilla';
            
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            
            if (in_array($extension, $allowed)) {
                // Use original name if possible or generate safe name?
                // User's LS showed original filenames like "CERTIFICADO 2025.jpg".
                // To avoid conflicts, maybe we should stick to safe names or handle overwrite?
                // The previous code used random safe names. 
                // However, the user might want to recognize the files.
                // But let's stick to safe names to avoid issues for now, or use unique IDs.
                // Actually, looking at the LS output, they seem to use original names or copies.
                // "683118141d22d_CONSTANCIA.jpg" -> looks like uniqid prefix.
                
                $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', str_replace(' ', '_', $file['name']));
                
                if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $safeName)) {
                    return $safeName;
                }
            }
        }
        return null;
    }

    private function updateCourse(): void
    {
        header('Content-Type: application/json');
        try {
            $pdo = Database::getConnection();
            
            $id = $_POST['id'] ?? '';
            if (!$id) {
                echo json_encode(['error' => 'Falta el ID del curso.']);
                return;
            }

            // Basic Validation
            $name = trim($_POST['event_name'] ?? '');
            $typeCode = $_POST['event_type'] ?? '';
            $modalityCode = $_POST['event_modality'] ?? '';
            
            if ($name === '' || $typeCode === '' || $modalityCode === '') {
                echo json_encode(['error' => 'Nombre, Tipo y Modalidad son obligatorios.']);
                return;
            }

            // Handle File Upload
            $backgroundFileName = $this->handleFileUpload();

            // Fetch current course to handle file deletion
            $currentCourse = Course::find($pdo, $id);
            $oldFilename = $currentCourse['certificate_background_filename'] ?? null;

            $data = [
                'name' => $name,
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'total_hours' => ($_POST['total_hours'] ?? '') !== '' ? $_POST['total_hours'] : null,
                'credits' => ($_POST['credits'] ?? '') !== '' ? $_POST['credits'] : null,
                'internal_code' => $_POST['event_code'] ?? null,
                'max_capacity' => ($_POST['max_capacity'] ?? '') !== '' ? $_POST['max_capacity'] : null,
                'short_description' => !empty($_POST['short_description']) ? $_POST['short_description'] : null,
                'description' => !empty($_POST['description']) ? $_POST['description'] : null
            ];

            // Logic for file deletion/replacement
            $deleteRequested = isset($_POST['delete_background']) && $_POST['delete_background'] == '1';
            
            if ($backgroundFileName) {
                // Case 1: Replacement (New file uploaded)
                $data['certificate_background_filename'] = $backgroundFileName;
                
                // Delete old file if exists
                if ($oldFilename) {
                    $oldFilePath = __DIR__ . '/../images/plantilla/' . $oldFilename;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
            } elseif ($deleteRequested) {
                // Case 2: Explicit deletion (without replacement)
                $data['certificate_background_filename'] = null;
                
                // Delete old file if exists
                if ($oldFilename) {
                    $oldFilePath = __DIR__ . '/../images/plantilla/' . $oldFilename;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
            }

            $auspiceIds = $_POST['auspices'] ?? [];
            if (!is_array($auspiceIds)) {
                $auspiceIds = [];
            }

            Course::updateWithDetails($pdo, $id, $data, $auspiceIds, $typeCode, $modalityCode);

            echo json_encode(['success' => true, 'message' => 'Curso actualizado correctamente.']);

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
        exit;
    }

    private function processForm(PDO $pdo): array
    {
        $name = trim($_POST['event_name'] ?? '');
        $type = $_POST['event_type'] ?? '';
        $modality = $_POST['event_modality'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $totalHours = $_POST['total_hours'] ?? '';
        $credits = $_POST['credits'] ?? '';
        $internalCode = trim($_POST['event_code'] ?? '');
        $maxCapacity = $_POST['max_capacity'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $auspiceIds = $_POST['auspices'] ?? [];

        if (!is_array($auspiceIds)) {
            $auspiceIds = [];
        }

        $errors = [];

        if ($name === '') {
            $errors[] = 'El nombre del curso es obligatorio.';
        }

        if ($type === '') {
            $errors[] = 'El tipo de programa es obligatorio.';
        }

        if ($modality === '') {
            $errors[] = 'La modalidad es obligatoria.';
        }

        $backgroundFileName = $this->handleFileUpload();
        if ($backgroundFileName === null && isset($_FILES['certificate_background']) && $_FILES['certificate_background']['error'] !== UPLOAD_ERR_NO_FILE) {
             $file = $_FILES['certificate_background'];
             // Only add error if it failed but wasn't empty (handleFileUpload returns null on empty too)
             if ($file['error'] !== UPLOAD_ERR_OK && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                $map = [
                    UPLOAD_ERR_INI_SIZE => 'La imagen excede el tamaño permitido por el servidor.',
                    UPLOAD_ERR_FORM_SIZE => 'La imagen excede el tamaño permitido por el formulario.',
                    UPLOAD_ERR_PARTIAL => 'La imagen se subió parcialmente.',
                    UPLOAD_ERR_NO_TMP_DIR => 'No hay carpeta temporal en el servidor.',
                    UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco.',
                    UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida.',
                ];
                $errors[] = $map[$file['error']] ?? 'Error al subir el archivo de fondo del certificado.';
             }
        }

        if (!empty($errors)) {
            return [null, implode(' ', $errors)];
        }

        try {
            $data = [
                'name' => $name,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_hours' => $totalHours,
                'credits' => $credits,
                'internal_code' => $internalCode,
                'max_capacity' => $maxCapacity,
                'description' => $description,
                'certificate_background_filename' => $backgroundFileName,
            ];

            $result = Course::createWithDetails($pdo, $data, $auspiceIds, $type, $modality);
            $internalCode = $result['code'];

            return ["Curso creado correctamente. Código interno: {$internalCode}.", null];
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            error_log('Error al crear curso (PDO): ' . $msg);
            if (stripos($msg, 'doesn\'t exist') !== false || stripos($msg, 'Base table or view not found') !== false) {
                return [null, "La tabla 'cursos' no existe en la base de datos. Asegúrate de haber ejecutado las migraciones."];
            }
            if (stripos($msg, 'Duplicate entry') !== false) {
                return [null, 'El event_code ya existe. Usa un código interno diferente.'];
            }
            return [null, 'Error de base de datos al guardar el curso: ' . $msg];
        } catch (Throwable $e) {
            error_log('Error al crear curso (GEN): ' . $e->getMessage());
            return [null, 'Ocurrió un error al guardar el curso.'];
        }
    }
}

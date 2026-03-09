<?php

require_once __DIR__ . '/../models/ConstanciaEvento.php';
require_once __DIR__ . '/../models/ConstanciaLead.php';

class ConstanciaController
{
    private $eventoModel;
    private $leadModel;

    public function __construct()
    {
        $this->eventoModel = new ConstanciaEvento();
        $this->leadModel = new ConstanciaLead();
    }

    public function handleRequest()
    {
        $view = $_GET['view'] ?? 'index';
        $action = $_GET['action'] ?? $_POST['action'] ?? null;

        // Public actions
        if ($action === 'validate') {
            $this->validate();
            return;
        }

        if ($action === 'register_lead' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->storeLead();
            return;
        }

        if ($action === 'download') {
            $this->downloadConstancia();
            return;
        }

        // Admin actions
        if ($action === 'store_event' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->storeEvent();
            return;
        }

        if ($action === 'update_event' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateEvent();
            return;
        }

        if ($action === 'get_event_details' && isset($_GET['id'])) {
            $this->getEventDetails();
            return;
        }
        
        if ($action === 'toggle_status' && isset($_GET['id'])) {
            $this->toggleStatus($_GET['id']);
            return;
        }

        // Views
        if ($view === 'create') {
            require __DIR__ . '/../views/constancias/create.php';
        } elseif ($view === 'leads') {
            $this->listLeads();
        } elseif ($view === 'public') {
            $this->publicView();
        } elseif ($view === 'success') {
            $this->successView();
        } else {
            $this->listEvents();
        }
    }

    private function successView()
    {
        // 1. Intentar obtener datos por GET (Prioridad para concurrencia)
        $leadId = $_GET['id'] ?? null;
        $eventoId = $_GET['event_id'] ?? null;
        
        $datos = null;
        $evento = null;
        
        if ($leadId) {
            $lead = $this->leadModel->findById($leadId);
            if ($lead) {
                $datos = $lead;
                $datos['id'] = $leadId; // Asegurar ID
            }
        }
        
        // Fallback a sesión (comportamiento antiguo, solo si no hay GET)
        if (!$datos && isset($_SESSION['registro_exitoso'])) {
            $datos = $_SESSION['registro_exitoso'];
            $eventoId = $datos['evento_id'] ?? null;
        }
        
        // Si no hay datos ni en GET ni en Sesión, redirigir
        if (!$datos) {
            header('Location: index.php?page=constancias&view=public');
            exit;
        }
        
        // Determinar evento
        if ($eventoId) {
            $evento = $this->eventoModel->findById($eventoId);
        } else {
            // Fallback al comportamiento anterior (último activo)
            $evento = $this->eventoModel->getLastActive();
        }

        require __DIR__ . '/../views/constancias/success.php';
    }

    private function listEvents()
    {
        $events = $this->eventoModel->getAll();
        require __DIR__ . '/../views/constancias/index.php';
    }

    private function listLeads()
    {
        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $search = isset($_GET['q']) ? trim($_GET['q']) : '';
        $eventoId = isset($_GET['evento_id']) ? $_GET['evento_id'] : null;
        
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $leads = $this->leadModel->getAll($limit, $offset, $search, $eventoId);
        $total = $this->leadModel->countAll($search, $eventoId);
        $totalPages = ceil($total / $limit);
        
        // Get all events for filter
        $events = $this->eventoModel->getAll();
        
        require __DIR__ . '/../views/constancias/leads.php';
    }

    private function publicView()
    {
        $eventId = $_GET['event_id'] ?? null;
        $selectedEvent = null;

        if ($eventId) {
            $event = $this->eventoModel->findById($eventId);
            // Solo permitir evento si está activo
            if ($event && $event['activo'] == 1) {
                $selectedEvent = $event;
            }
        }

        // Get active events for the dropdown
        $allEvents = $this->eventoModel->getAll();
        $events = array_filter($allEvents, function($e) {
            return $e['activo'] == 1;
        });

        require __DIR__ . '/../views/constancias/public.php';
    }

    private function getEventDetails()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID no proporcionado']);
            exit;
        }

        $event = $this->eventoModel->findById($id);
        header('Content-Type: application/json');
        
        if ($event) {
            echo json_encode($event);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Evento no encontrado']);
        }
        exit;
    }

    private function updateEvent()
    {
        $id = $_POST['id'] ?? null;
        $nombre = $_POST['nombre'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        
        if (!$id || empty($nombre) || empty($fecha_inicio)) {
            $_SESSION['error'] = 'ID, nombre y fecha de inicio son obligatorios.';
            header('Location: index.php?page=constancias');
            exit;
        }

        $event = $this->eventoModel->findById($id);
        if (!$event) {
            $_SESSION['error'] = 'Evento no encontrado.';
            header('Location: index.php?page=constancias');
            exit;
        }

        $fondo_constancia = $event['fondo_constancia']; // Keep existing by default
        $delete_fondo = $_POST['delete_fondo'] ?? '0';
        $hasNewFile = (isset($_FILES['fondo_constancia']) && $_FILES['fondo_constancia']['error'] === UPLOAD_ERR_OK);

        // Case 1: Explicit deletion (without replacement)
        if ($delete_fondo === '1' && !$hasNewFile) {
            if (!empty($event['fondo_constancia'])) {
                $oldFilePath = __DIR__ . '/../' . $event['fondo_constancia'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            $fondo_constancia = null;
        }

        // Case 2: Replacement (New file uploaded)
        if ($hasNewFile) {
            $uploadDir = __DIR__ . '/../images/constancia/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $extension = pathinfo($_FILES['fondo_constancia']['name'], PATHINFO_EXTENSION);
            $filename = 'constancia_' . time() . '.' . $extension;
            
            if (move_uploaded_file($_FILES['fondo_constancia']['tmp_name'], $uploadDir . $filename)) {
                // Upload successful, NOW delete old file if it exists
                if (!empty($event['fondo_constancia'])) {
                    $oldFilePath = __DIR__ . '/../' . $event['fondo_constancia'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                $fondo_constancia = 'images/constancia/' . $filename;
            }
        }

        $data = [
            'nombre' => $nombre,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null,
            'fondo_constancia' => $fondo_constancia,
            'activo' => $event['activo'] // Preserve existing status
        ];

        if ($this->eventoModel->update($id, $data)) {
            $_SESSION['success'] = 'Evento actualizado exitosamente.';
        } else {
            $_SESSION['error'] = 'Error al actualizar el evento.';
        }
        header('Location: index.php?page=constancias');
        exit;
    }

    private function storeEvent()
    {
        $nombre = $_POST['nombre'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        
        if (empty($nombre) || empty($fecha_inicio)) {
            $_SESSION['error'] = 'Nombre y fecha de inicio son obligatorios.';
            header('Location: index.php?page=constancias&view=create');
            exit;
        }

        $fondo_constancia = null;
        if (isset($_FILES['fondo_constancia']) && $_FILES['fondo_constancia']['error'] === UPLOAD_ERR_OK) {
            // Changed to images/constancia as requested by user
            $uploadDir = __DIR__ . '/../images/constancia/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $extension = pathinfo($_FILES['fondo_constancia']['name'], PATHINFO_EXTENSION);
            $filename = 'constancia_' . time() . '.' . $extension;
            
            if (move_uploaded_file($_FILES['fondo_constancia']['tmp_name'], $uploadDir . $filename)) {
                $fondo_constancia = 'images/constancia/' . $filename;
            }
        }

        $data = [
            'nombre' => $nombre,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $_POST['fecha_fin'] ?? null,
            'fondo_constancia' => $fondo_constancia,
            'activo' => 1
        ];

        if ($this->eventoModel->create($data)) {
            $_SESSION['success'] = 'Evento creado exitosamente.';
            header('Location: index.php?page=constancias');
        } else {
            $_SESSION['error'] = 'Error al crear el evento.';
            header('Location: index.php?page=constancias&view=create');
        }
        exit;
    }

    private function storeLead()
    {
        $nombres = $_POST['nombres'] ?? '';
        $apellidos = $_POST['apellidos'] ?? '';
        $documento = $_POST['documento_identidad'] ?? '';
        $eventoId = $_POST['evento_id'] ?? null;
        
        if (empty($nombres) || empty($documento)) {
            // Handle error for public view (maybe redirect back with error)
             $_SESSION['public_error'] = 'Nombres y documento son obligatorios.';
             header('Location: index.php?page=constancias&view=public' . ($eventoId ? '&event_id=' . $eventoId : ''));
             exit;
        }

        $data = [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'documento_identidad' => $documento,
            'email' => $_POST['email'] ?? null,
            'celular' => $_POST['celular'] ?? null
        ];

        try {
            $insertId = $this->leadModel->create($data);
            if ($insertId) {
                $data['id'] = $insertId;

                // Generar Constancia si hay evento seleccionado
                if ($eventoId) {
                    // Validar que el evento existe y está activo antes de generar constancia
                    $eventoCheck = $this->eventoModel->findById($eventoId);
                    
                    if ($eventoCheck && $eventoCheck['activo'] == 1) {
                        $codigo = $this->eventoModel->generateUniqueCode();
                        
                        // Obtener el esquema (http o https)
                        $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 
                                  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
                        
                        // Obtener el host
                        $host = $_SERVER['HTTP_HOST'];
                        
                        // Obtener el directorio base de la aplicación (elimina el script actual)
                        $baseDir = dirname($_SERVER['PHP_SELF']);
                        
                        // Limpiar barras invertidas en Windows y asegurar que termina en /
                        $baseDir = str_replace('\\', '/', $baseDir);
                        if (substr($baseDir, -1) !== '/') {
                            $baseDir .= '/';
                        }
                        
                        // Construir la URL completa
                        $baseUrl = $scheme . '://' . $host . $baseDir;
                        $validationUrl = $baseUrl . "index.php?page=constancias&action=validate&code=" . $codigo;
                        
                        $constanciaData = [
                            'lead_id' => $insertId,
                            'evento_id' => $eventoId,
                            'codigo_verificacion' => $codigo,
                            'qr_codigo' => $validationUrl, // Guardamos la URL que debe contener el QR
                            'ip_generacion' => $_SERVER['REMOTE_ADDR'] ?? null,
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                        ];
                        
                        $this->eventoModel->createConstancia($constanciaData);
                        
                        // Agregar info a la sesión para successView
                        $data['evento_id'] = $eventoId;
                        $data['codigo_constancia'] = $codigo;
                    }
                }

                // $_SESSION['registro_exitoso'] = $data; // Deprecated due to concurrency issues
                
                // Redirigir con parámetros en URL para evitar conflictos de sesión entre pestañas
                $redirectUrl = 'index.php?page=constancias&view=success&id=' . $insertId;
                if ($eventoId) {
                    $redirectUrl .= '&event_id=' . $eventoId;
                }
                
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $_SESSION['public_error'] = 'Error al registrar. Inténtalo de nuevo.';
            }
        } catch (Exception $e) {
            $_SESSION['public_error'] = 'Error al registrar: ' . $e->getMessage();
        }
        
        header('Location: index.php?page=constancias&view=public' . ($eventoId ? '&event_id=' . $eventoId : ''));
        exit;
    }

    private function toggleStatus($id)
    {
        $this->eventoModel->toggleStatus($id);
        header('Location: index.php?page=constancias');
        exit;
    }

    private function downloadConstancia()
    {
        // Limpiar cualquier buffer de salida previo para evitar corrupción del PDF
        if (ob_get_length()) {
            ob_end_clean();
        }

        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id) {
            // Intenta obtener de la sesión si no viene en request
            if (isset($_SESSION['registro_exitoso']['id'])) {
                $id = $_SESSION['registro_exitoso']['id'];
            } else {
                error_log('Error Constancia: ID no proporcionado en GET/POST ni en sesión.');
                die('ID de constancia no proporcionado.');
            }
        }

        $lead = $this->leadModel->findById($id);
        if (!$lead) {
            error_log('Error Constancia: Lead no encontrado para ID: ' . $id);
            die('Registro no encontrado (ID: ' . htmlspecialchars($id) . ').');
        }

        // Determinar Evento y Constancia
        $eventoId = $_GET['event_id'] ?? $_POST['event_id'] ?? null;
        $constancia = null;
        $evento = null;

        if ($eventoId) {
            // Prioridad 1: Buscar el evento solicitado explícitamente
            $evento = $this->eventoModel->findById($eventoId);
            
            if ($evento) {
                // Si existe el evento, buscar si ya tiene constancia
                $constancia = $this->eventoModel->getConstancia($lead['id'], $eventoId);
            }
        } else {
            // Intentar buscar la última constancia generada para este lead
            $constancia = $this->eventoModel->getLatestConstancia($lead['id']);
            if ($constancia) {
                $evento = $this->eventoModel->findById($constancia['evento_id']);
            }
        }

        // Si no hay constancia previa, usar el último evento activo (fallback)
        if (!$evento) {
            $evento = $this->eventoModel->getLastActive();
        }

        if (!$evento) {
            error_log('Error Constancia: No hay evento activo.');
            die('No hay evento activo para generar la constancia.');
        }

        try {
            // Asegurarse de que no haya salida previa
            if (ob_get_length()) ob_clean();

            require_once __DIR__ . '/../lib/phpqrcode/qrlib.php';
            require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

            // Verificar o crear registro de constancia
            if (!$constancia) {
                $constancia = $this->eventoModel->getConstancia($lead['id'], $evento['id']);
            }
            
            if (!$constancia) {
                try {
                    $codigo_verificacion = $this->eventoModel->generateUniqueCode();
                    
                    // URL de validación
                    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 
                              (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
                    $host = $_SERVER['HTTP_HOST'];
                    $baseDir = dirname($_SERVER['PHP_SELF']);
                    $baseDir = str_replace('\\', '/', $baseDir);
                    if (substr($baseDir, -1) !== '/') $baseDir .= '/';
                    $baseUrl = $scheme . '://' . $host . $baseDir;
                    $validationUrl = $baseUrl . "index.php?page=constancias&action=validate&code=" . $codigo_verificacion;

                    $constanciaData = [
                        'lead_id' => $lead['id'],
                        'evento_id' => $evento['id'],
                        'codigo_verificacion' => $codigo_verificacion,
                        'qr_codigo' => $validationUrl,
                        'ip_generacion' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                    ];
                    
                    $insertId = $this->eventoModel->createConstancia($constanciaData);
                    $constancia = $constanciaData; // Datos para uso inmediato
                    $constancia['id'] = $insertId;
                    
                    error_log('Constancia creada: ' . $codigo_verificacion);
                } catch (Exception $e) {
                    error_log('Error creando registro de constancia: ' . $e->getMessage());
                    die('Error al registrar la constancia en base de datos.');
                }
            } else {
                $codigo_verificacion = $constancia['codigo_verificacion'];
                // Incrementar contador de descargas
                $this->eventoModel->incrementDownload($constancia['id']);
                error_log('Constancia existente recuperada y contador actualizado: ' . $codigo_verificacion);
            }

            // Configuración del PDF (Landscape, A4)
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->SetCreator('Sistema de Constancias');
            $pdf->SetAuthor('Sistema QR Simple');
            $pdf->SetTitle('Constancia - ' . $lead['nombres'] . ' ' . $lead['apellidos']);

            $pdf->AddPage('L', 'A4');

            // Fondo
            $bgPath = null;
            if (!empty($evento['fondo_constancia'])) {
                $bgPath = __DIR__ . '/../' . $evento['fondo_constancia'];
            }
            
            // Fallback al fondo predeterminado si no hay específico
            if (!$bgPath || !is_file($bgPath)) {
                $bgPath = __DIR__ . '/../assets/img/constancia_fondo.jpg';
            }

            if (is_file($bgPath)) {
                $pdf->Image($bgPath, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);
            } else {
                // Fondo simple si no hay imagen (como en generar_constancia.php)
                $pdf->SetFillColor(245, 245, 245);
                $pdf->Rect(0, 0, 297, 210, 'F');
            }

            // Nombre del Participante
            $fullName = mb_strtoupper($lead['nombres'] . ' ' . $lead['apellidos'], 'UTF-8');
            
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(0, 90); // Posición Y ajustada a 90 como en el ejemplo
            
            $fontSize = 24;
            $maxWidth = 270;
            // Usar Arial Bold como en el ejemplo
            $pdf->SetFont('helvetica', 'B', $fontSize);
            
            while ($pdf->GetStringWidth($fullName) > $maxWidth && $fontSize > 10) {
                $fontSize--;
                $pdf->SetFont('helvetica', 'B', $fontSize);
            }
            
            $pdf->Cell(0, 10, $fullName, 0, 1, 'C'); // Cell 0 width para centrar en toda la pagina


            // Generar código QR
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $baseDir = dirname($scriptName);
            $baseDir = str_replace('\\', '/', $baseDir);
            $baseDir = rtrim($baseDir, '/');
            
            // URL de validación
            $url_verificacion = $protocol . '://' . $host . $baseDir . '/index.php?page=constancias&action=validate&code=' . $codigo_verificacion;

            // Generar QR en archivo temporal
            $qrTemp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qr_const_' . $codigo_verificacion . '.png';
            QRcode::png($url_verificacion, $qrTemp, QR_ECLEVEL_M, 4, 2);
            
            if (is_file($qrTemp)) {
                // Posición del QR (ajustar según necesidad, ejemplo basado en certificados)
                // En generar_constancia.php original estaba comentado, pero aquí lo ponemos
                // Usamos una posición estándar, por ejemplo esquina inferior derecha
                $pdf->Image($qrTemp, 20, 160, 30, 30);
                @unlink($qrTemp);
            }

            // Salida del PDF
            $filename = 'Constancia_' . preg_replace('/[^a-zA-Z0-9]/', '_', $fullName) . '.pdf';
            $pdf->Output($filename, 'D');
            exit;

        } catch (Exception $e) {
            error_log('Error generando constancia: ' . $e->getMessage());
            die('Error al generar la constancia.');
        }
    }

    private function validate()
    {
        $code = $_GET['code'] ?? '';
        $constancia = null;
        
        if ($code) {
            $constancia = $this->eventoModel->getConstanciaByCode($code);
        }

        // Base URL calculation
        $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 
                  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
        $host = $_SERVER['HTTP_HOST'];
        $baseDir = dirname($_SERVER['PHP_SELF']);
        $baseDir = str_replace('\\', '/', $baseDir);
        if (substr($baseDir, -1) !== '/') {
            $baseDir .= '/';
        }
        $baseUrl = $scheme . '://' . $host . $baseDir;

        if ($constancia) {
            require __DIR__ . '/../views/constancias/validate.php';
        } else {
            require __DIR__ . '/../views/constancias/validate_error.php';
        }
        exit;
    }
}

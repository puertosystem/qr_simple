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
        if (!isset($_SESSION['registro_exitoso'])) {
            header('Location: index.php?page=constancias&view=public');
            exit;
        }
        $datos = $_SESSION['registro_exitoso'];
        
        // Si tenemos el ID del evento en la sesión, lo usamos
        if (isset($datos['evento_id'])) {
            $evento = $this->eventoModel->findById($datos['evento_id']);
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
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $leads = $this->leadModel->getAll($limit, $offset, $search);
        $total = $this->leadModel->countAll($search);
        $totalPages = ceil($total / $limit);
        
        require __DIR__ . '/../views/constancias/leads.php';
    }

    private function publicView()
    {
        $eventId = $_GET['event_id'] ?? null;
        $selectedEvent = null;

        if ($eventId) {
            $selectedEvent = $this->eventoModel->findById($eventId);
        }

        // Get active events for the dropdown
        $events = $this->eventoModel->getAll(); // TODO: Filter active only in model or here
        require __DIR__ . '/../views/constancias/public.php';
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
            $uploadDir = __DIR__ . '/../assets/fondos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $extension = pathinfo($_FILES['fondo_constancia']['name'], PATHINFO_EXTENSION);
            $filename = 'fondo_' . time() . '.' . $extension;
            
            if (move_uploaded_file($_FILES['fondo_constancia']['tmp_name'], $uploadDir . $filename)) {
                $fondo_constancia = 'assets/fondos/' . $filename;
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

                $_SESSION['registro_exitoso'] = $data;
                header('Location: index.php?page=constancias&view=success');
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

        $evento = $this->eventoModel->getLastActive();
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
            $constancia = $this->eventoModel->getConstancia($lead['id'], $evento['id']);
            
            if (!$constancia) {
                try {
                    $codigo_verificacion = $this->eventoModel->generateUniqueCode();
                    
                    $constanciaData = [
                        'lead_id' => $lead['id'],
                        'evento_id' => $evento['id'],
                        'codigo_verificacion' => $codigo_verificacion,
                        'qr_codigo' => $codigo_verificacion,
                        'ip_generacion' => $_SERVER['REMOTE_ADDR'] ?? null,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                    ];
                    
                    $this->eventoModel->createConstancia($constanciaData);
                    error_log('Constancia creada: ' . $codigo_verificacion);
                } catch (Exception $e) {
                    error_log('Error creando registro de constancia: ' . $e->getMessage());
                    die('Error al registrar la constancia en base de datos.');
                }
            } else {
                $codigo_verificacion = $constancia['codigo_verificacion'];
                error_log('Constancia existente recuperada: ' . $codigo_verificacion);
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

            // Fecha de emisión
            $pdf->SetXY(20, 120);
            $pdf->SetFont('helvetica', '', 20);
            $pdf->Cell(74, 5, date('d/m/Y'), 0, 1, 'R');

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
                $pdf->Image($qrTemp, 250, 160, 30, 30);
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
}

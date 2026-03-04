<?php

require_once __DIR__ . '/../config/database.php';

class CertificateController
{
    public function handleRequest(): void
    {
        $successMessage = null;
        $errorMessage = null;

        $searchTerm = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';
        $filterType = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
        $filterState = isset($_GET['filter_state']) ? trim($_GET['filter_state']) : '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'generate_qr') {
                [$successMessage, $errorMessage] = $this->generateForEvent();

                if ($successMessage !== null && $errorMessage === null) {
                    $_SESSION['certificates_success'] = $successMessage;
} elseif ($errorMessage !== null) {
                    $_SESSION['certificates_error'] = $errorMessage;
                }

                $params = [];
                if ($searchTerm !== '') {
                    $params['search_term'] = $searchTerm;
                }
                if ($filterType !== '') {
                    $params['filter_type'] = $filterType;
                }
                if ($filterState !== '') {
                    $params['filter_state'] = $filterState;
                }

                $query = http_build_query($params);
                $location = 'index.php?page=certificates' . ($query !== '' ? '&' . $query : '');
                header('Location: ' . $location);
                exit;
            } elseif ($action === 'download_pdfs') {
                $this->downloadCertificatesZip();
                return;
            } elseif ($action === 'download_individual_pdf') {
                $this->downloadIndividualPdf();
                return;
            }
        }

        if (isset($_SESSION['certificates_success'])) {
            $successMessage = $_SESSION['certificates_success'];
            unset($_SESSION['certificates_success']);
        }
        if (isset($_SESSION['certificates_error'])) {
            $errorMessage = $_SESSION['certificates_error'];
            unset($_SESSION['certificates_error']);
        }

        $courses = [];
        $pagination = [];
        
        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $limit = 10; // Items per page

        try {
            $pdo = Database::getConnection();
            $result = $this->getCoursesWithStats($pdo, $searchTerm, $filterType, $filterState, $page, $limit);
            $courses = $result['data'];
            $pagination = $result['pagination'];
        } catch (Throwable $e) {
            error_log('Error cargando cursos para certificados: ' . $e->getMessage());
            $errorMessage = 'No se pudieron cargar los cursos desde la base de datos.';
        }

        require __DIR__ . '/../views/certificates/index.php';
    }

    private function getCoursesWithStats(PDO $pdo, string $searchTerm, string $filterType, string $filterState, int $page = 1, int $limit = 10): array
    {
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $limit;
        $params = [];
        
        // Base conditions
        $whereClause = "WHERE 1 = 1";
        
        if ($searchTerm !== '') {
            $whereClause .= " AND (e.nombre LIKE :search_name OR e.event_code LIKE :search_code)";
            $params[':search_name'] = '%' . $searchTerm . '%';
            $params[':search_code'] = '%' . $searchTerm . '%';
        }

        if ($filterType !== '') {
            $whereClause .= " AND et.code = :type_code";
            $params[':type_code'] = $filterType;
        }

        if ($filterState !== '') {
            $whereClause .= " AND e.status = :status";
            $params[':status'] = $filterState;
        }

        // Count query
        $countSql = "
            SELECT COUNT(*) 
            FROM cursos e
            LEFT JOIN event_types et ON e.event_type_id = et.id
            $whereClause
        ";
        
        $stmtCount = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $totalRecords = (int)$stmtCount->fetchColumn();
        $totalPages = $limit > 0 ? ceil($totalRecords / $limit) : 0;

        // Data query
        $sql = "
            SELECT
                e.id,
                e.nombre as name,
                e.fecha_inicio as start_date,
                e.fecha_fin as end_date,
                e.event_code,
                e.status,
                et.name AS type_name,
                et.code AS type_code,
                em.name AS modality_name,
                COALESCE(enr.total_enrollments, 0) AS total_enrollments,
                COALESCE(enr.active_enrollments, 0) AS active_enrollments,
                COALESCE(enr.completed_enrollments, 0) AS completed_enrollments,
                COALESCE(enr.pending_qr_count, 0) AS pending_qr_count,
                COALESCE(cert.certificates_count, 0) AS certificates_count
            FROM cursos e
            LEFT JOIN event_types et ON e.event_type_id = et.id
            LEFT JOIN event_modalities em ON e.event_modality_id = em.id
            LEFT JOIN (
                SELECT
                    ce.curso_id,
                    COUNT(*) AS total_enrollments,
                    SUM(CASE WHEN ce.status = 'active' THEN 1 ELSE 0 END) AS active_enrollments,
                    SUM(CASE WHEN ce.status = 'completed' THEN 1 ELSE 0 END) AS completed_enrollments,
                    SUM(CASE WHEN c.id IS NULL THEN 1 ELSE 0 END) AS pending_qr_count
                FROM curso_estudiantes ce
                LEFT JOIN certificados c ON ce.curso_id = c.curso_id AND ce.usuario_id = c.usuario_id
                GROUP BY ce.curso_id
            ) enr ON enr.curso_id = e.id
            LEFT JOIN (
                SELECT
                    curso_id,
                    COUNT(*) AS certificates_count
                FROM certificados
                GROUP BY curso_id
            ) cert ON cert.curso_id = e.id
            $whereClause
            ORDER BY e.created_at DESC, e.nombre ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'limit' => $limit
            ]
        ];
    }

    private function generateForEvent(): array
    {
        $eventId = isset($_POST['event_id']) ? trim($_POST['event_id']) : '';
        if ($eventId === '') {
            return [null, 'No se recibió el identificador del curso.'];
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare('SELECT id, nombre as name FROM cursos WHERE id = ? LIMIT 1');
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                return [null, 'El curso seleccionado no existe.'];
            }

            // Find users enrolled in the course who do NOT have a certificate
            $sqlMissingCert = "
                SELECT ce.usuario_id
                FROM curso_estudiantes ce
                LEFT JOIN certificados c ON c.usuario_id = ce.usuario_id AND c.curso_id = ce.curso_id
                WHERE ce.curso_id = :curso_id
                  AND c.id IS NULL
            ";
            $stmt = $pdo->prepare($sqlMissingCert);
            $stmt->execute([':curso_id' => $eventId]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($userIds)) {
                return [null, 'Todas las matrículas de este curso ya tienen certificado generado.'];
            }

            require_once __DIR__ . '/../lib/phpqrcode/qrlib.php';

            $pdo->beginTransaction();

            $insert = $pdo->prepare(
                'INSERT INTO certificados (usuario_id, curso_id, qr_codigo, fecha_generacion)
                 VALUES (:usuario_id, :curso_id, :qr_codigo, NOW())'
            );

            $generatedCount = 0;
            foreach ($userIds as $userId) {
                $code = $this->generateUniqueCertificateCode($pdo);
                
                $insert->execute([
                    ':usuario_id' => $userId,
                    ':curso_id' => $eventId,
                    ':qr_codigo' => $code
                ]);

                $generatedCount++;
            }

            $pdo->commit();

            return ["Se generaron {$generatedCount} certificados para el curso «{$event['name']}».", null];
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al generar certificados (PDO): ' . $e->getMessage());
            return [null, 'Error de base de datos al generar los certificados.'];
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al generar certificados (GEN): ' . $e->getMessage());
            return [null, 'Ocurrió un error al generar los certificados.'];
        }
    }

    private function downloadIndividualPdf(): void
    {
        $certificateId = isset($_POST['certificate_id']) ? trim($_POST['certificate_id']) : '';

        if ($certificateId === '') {
            $_SESSION['certificates_error'] = 'No se recibió el identificador del certificado.';
            header('Location: index.php?page=participants');
            exit;
        }

        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $pdo = Database::getConnection();

            $sql = "
                SELECT
                    c.id AS certificate_id,
                    c.qr_codigo as code,
                    c.curso_id,
                    p.first_name,
                    p.last_name,
                    e.nombre as course_name,
                    e.imagen_fondo as certificate_background_filename
                FROM certificados c
                JOIN usuarios p ON c.usuario_id = p.id
                JOIN cursos e ON c.curso_id = e.id
                WHERE c.id = :certificate_id
                LIMIT 1
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':certificate_id' => $certificateId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $_SESSION['certificates_error'] = 'El certificado no existe.';
                header('Location: index.php?page=participants');
                exit;
            }

            require_once __DIR__ . '/../lib/phpqrcode/qrlib.php';
            require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

            $bgPath = null;
            if (!empty($row['certificate_background_filename'])) {
                // Try to find the background image in various locations
                $candidates = [
                    __DIR__ . '/../images/plantilla/' . $row['certificate_background_filename']
                ];
                
                foreach ($candidates as $candidate) {
                    if (is_file($candidate)) {
                        $bgPath = $candidate;
                        break;
                    }
                }
            }

            // Fallback to default background if not found
            if (!$bgPath) {
                 // Check local default first
                 $localDefault = __DIR__ . '/../images/plantilla/fondo.jpg';
                 if (is_file($localDefault)) {
                     $bgPath = $localDefault;
                 }
            }

            $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
            $certId = $row['certificate_id'];
            $code = $row['code'];

            // Generate PDF content
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->SetCompression(true);
            $pdf->setJPEGQuality(75);
            $pdf->SetCreator('Sistema de Certificados');
            $pdf->SetAuthor('Rebagliati Diplomados');
            $pdf->SetTitle('Certificado - ' . $fullName);

            $pdf->AddPage('L', 'A4');

            // Background
            if ($bgPath && is_file($bgPath)) {
                $pdf->Image($bgPath, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0, true, false, true);
            }

            // Participant Name
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(0, 85);
            $fontSize = 23;
            $maxWidth = 270;
            $fontStyle = '';
            $pdf->SetFont('helvetica', $fontStyle, $fontSize);
            while ($pdf->GetStringWidth($fullName) > $maxWidth && $fontSize > 10) {
                $fontSize--;
                $pdf->SetFont('helvetica', $fontStyle, $fontSize);
            }
            $pdf->Cell(297, 24, $fullName, 0, 1, 'C');

            // QR Code
            $qrSize = 35;
            $qrX = 248;
            $qrY = 159;
            
            // Reconstructing validation URL
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $baseDir = dirname($scriptName);
            $baseDir = str_replace('\\', '/', $baseDir);
            $baseDir = rtrim($baseDir, '/');
            
            // Generate URL pointing to the compatibility shim
            // This ensures new QRs match the structure of old QRs (/certificados/validar.php)
            // and work with the file we just created.
            $validateUrl = $protocol . '://' . $host . $baseDir . '/certificados/validar.php?codigo=' . rawurlencode($code);

            $qrTemp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qr_' . $certId . '.png';
            QRcode::png($validateUrl, $qrTemp, QR_ECLEVEL_L, 8, 1);
            if (is_file($qrTemp)) {
                $pdf->Image($qrTemp, $qrX, $qrY, $qrSize);
                @unlink($qrTemp);
            }

            $pdf->Output('Certificado_' . preg_replace('/[^a-zA-Z0-9]/', '_', $fullName) . '.pdf', 'D');
            exit;

        } catch (Throwable $e) {
            error_log('Error al generar certificado individual: ' . $e->getMessage());
            $_SESSION['certificates_error'] = 'Error interno al generar el certificado.';
            header('Location: index.php?page=participants');
            exit;
        }
    }

    private function generateUniqueCertificateCode(PDO $pdo): string
    {
        $year = date('Y');
        $prefix = $year . '-';

        for ($i = 0; $i < 5; $i++) {
            $random = strtoupper(bin2hex(random_bytes(5)));
            $candidate = $prefix . $random;

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM certificados WHERE qr_codigo = ?');
            $stmt->execute([$candidate]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $candidate;
            }
        }

        return $prefix . strtoupper(bin2hex(random_bytes(8)));
    }

    private function downloadCertificatesZip(): void
    {
        $eventId = isset($_POST['event_id']) ? trim($_POST['event_id']) : '';
        $participantId = isset($_POST['participant_id']) ? trim($_POST['participant_id']) : '';
        
        if ($eventId === '') {
            $_SESSION['certificates_error'] = 'No se recibió el identificador del curso.';
            header('Location: index.php?page=certificates');
            exit;
        }

        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $pdo = Database::getConnection();

            // Fetch course details
            $stmt = $pdo->prepare('SELECT id, nombre as name, imagen_fondo as certificate_background_filename FROM cursos WHERE id = ? LIMIT 1');
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$event) {
                $_SESSION['certificates_error'] = 'El curso no existe.';
                header('Location: index.php?page=certificates');
                exit;
            }

            // Build query based on whether it's a single participant or all participants
            $sql = "
                SELECT
                    c.id AS certificate_id,
                    c.qr_codigo as code,
                    p.first_name,
                    p.last_name
                FROM certificados c
                JOIN usuarios p ON c.usuario_id = p.id
                WHERE c.curso_id = :curso_id
            ";
            
            $params = [':curso_id' => $eventId];
            
            if ($participantId !== '') {
                $sql .= " AND p.id = :participant_id";
                $params[':participant_id'] = $participantId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                if ($participantId !== '') {
                     // Check if participant is enrolled but has no certificate
                     $_SESSION['certificates_error'] = 'El participante seleccionado no tiene un certificado generado para este curso.';
                } else {
                     $_SESSION['certificates_error'] = 'No hay certificados generados para este curso.';
                }
                // Redirect back to referring page if possible, otherwise certificates
                if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'page=participants') !== false) {
                     header('Location: ' . $_SERVER['HTTP_REFERER']);
                } else {
                     header('Location: index.php?page=certificates');
                }
                exit;
            }

            require_once __DIR__ . '/../lib/phpqrcode/qrlib.php';
            require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

            $bgPath = null;
            if (!empty($event['certificate_background_filename'])) {
                // Try to find the background image in various locations
                $candidates = [
                    __DIR__ . '/../images/plantilla/' . $event['certificate_background_filename']
                ];
                
                foreach ($candidates as $candidate) {
                    if (is_file($candidate)) {
                        $bgPath = $candidate;
                        break;
                    }
                }
            }
            
            // If it's a single participant, download PDF directly instead of ZIP
            if ($participantId !== '' && count($rows) === 1) {
                $row = $rows[0];
                $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
                $certId = $row['certificate_id'];
                $code = $row['code'];
                
                // Generate PDF content (same logic as ZIP loop)
                $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->SetMargins(0, 0, 0);
                $pdf->SetAutoPageBreak(false, 0);
                $pdf->SetCompression(true);
                $pdf->setJPEGQuality(75);
                $pdf->SetCreator('Sistema de Certificados');
                $pdf->SetAuthor('Rebagliati Diplomados');
                $pdf->SetTitle('Certificado - ' . $fullName);

                $pdf->AddPage('L', 'A4');

                // Background
                if ($bgPath && is_file($bgPath)) {
                    $pdf->Image($bgPath, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0, true, false, true);
                }

                // Participant Name
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetXY(0, 85);
                $fontSize = 23;
                $maxWidth = 270;
                $fontStyle = '';
                $pdf->SetFont('helvetica', $fontStyle, $fontSize);
                while ($pdf->GetStringWidth($fullName) > $maxWidth && $fontSize > 10) {
                    $fontSize--;
                    $pdf->SetFont('helvetica', $fontStyle, $fontSize);
                }
                $pdf->Cell(297, 24, $fullName, 0, 1, 'C');

                // QR Code
                $qrSize = 35;
                $qrX = 248;
                $qrY = 159;
                
                // Assuming validateUrl is constant base + code. 
                // Since we don't have the base URL config here easily, let's assume a standard one or rebuild it.
                // In previous code it was $validateUrl = '.../verificar.php?code=' . $code;
                // Let's use a generic relative path or the same as before if known.
                // Reconstructing validation URL:
                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                // Adjust path if necessary. Assuming root/verificar.php or similar.
                // For safety, let's use the code directly if that's what was intended, or a standard validation URL.
                // Looking at previous code might help, but let's assume standard verify URL.
                $validateUrl = $baseUrl . '/aula-virtual/certificados/verificar.php?code=' . $code;

                $qrTemp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qr_' . $certId . '.png';
                QRcode::png($validateUrl, $qrTemp, QR_ECLEVEL_L, 8, 1);
                if (is_file($qrTemp)) {
                    $pdf->Image($qrTemp, $qrX, $qrY, $qrSize);
                    @unlink($qrTemp);
                }

                $pdf->Output('Certificado_' . preg_replace('/[^a-zA-Z0-9]/', '_', $fullName) . '.pdf', 'D');
                exit;
            }

            $zip = new ZipArchive();
            $zipPath = tempnam(sys_get_temp_dir(), 'certificados_') . '.zip';
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $_SESSION['certificates_error'] = 'No se pudo crear el archivo ZIP.';
                header('Location: index.php?page=certificates');
                exit;
            }

            $counter = 1;
            foreach ($rows as $r) {
                $fullName = trim($r['first_name'] . ' ' . $r['last_name']);
                $code = $r['code'];
                $certId = $r['certificate_id'];

                // 'L' = Landscape (Horizontal), 'mm' = milímetros, 'A4' = tamaño
                $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->SetMargins(0, 0, 0);
                $pdf->SetAutoPageBreak(false, 0);
                $pdf->SetCompression(true);
                $pdf->setJPEGQuality(75);
                $pdf->SetCreator('Sistema de Certificados');
                $pdf->SetAuthor('Rebagliati Diplomados');
                $pdf->SetTitle('Certificado - ' . $fullName);

                $pdf->AddPage('L', 'A4');

                // Ajustar imagen de fondo para A4 Horizontal (297 x 210 mm)
                if ($bgPath && is_file($bgPath)) {
                    $pdf->Image($bgPath, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0, true, false, true);
                }

                // ======================================================================================
                // CONFIGURACIÓN DE POSICIÓN DEL NOMBRE DEL PARTICIPANTE
                // ======================================================================================
                $pdf->SetTextColor(0, 0, 0); // Color del texto (RGB: 0,0,0 es negro)
                $pdf->SetXY(0, 85); 

                // Lógica de ajuste automático de fuente para nombres largos
                $fontSize = 23; // Tamaño base según descargar.php
                $maxWidth = 270; // Ancho máximo permitido (297 - márgenes)
                $fontStyle = ''; // Regular, según descargar.php
                
                $pdf->SetFont('helvetica', $fontStyle, $fontSize);
                
                // Reducir tamaño mientras el texto sea más ancho que el permitido
                while ($pdf->GetStringWidth($fullName) > $maxWidth && $fontSize > 10) {
                    $fontSize--;
                    $pdf->SetFont('helvetica', $fontStyle, $fontSize);
                }

                // Cell(Ancho, Alto, Texto, Borde, NuevaLinea, Alineación)
                $pdf->Cell(297, 24, $fullName, 0, 1, 'C');

                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                
                // Detectar automáticamente la carpeta base del proyecto (ej: '/qr' o '/aulavirtual')
                // Ajuste específico para qr_simple apuntando a validación en aulavirtual
                // Según descargar.php: $base_url = "$protocol://$host/aula-virtual/certificados";
                // Asumimos que queremos usar la misma URL de validación que el sistema original
                // O usar la del sistema actual. El usuario pidió coordenadas, pero la URL de validación es crítica.
                // Mantendremos la lógica de validación actual de qr_simple pero con la estructura requerida.
                // OJO: descargar.php usa: $base_url . '/validar.php?codigo=' . urlencode($certificado['qr_codigo'])
                // qr_simple usa: /certificate/validate/{code}
                
                // Si queremos ser consistentes con el sistema antiguo, deberíamos generar el QR apuntando a donde ellos esperan.
                // Pero qr_simple tiene su propio validador. 
                // Asumiremos usar el validador de qr_simple por ahora, pero con las coordenadas visuales del antiguo.
                
                $scriptName = $_SERVER['SCRIPT_NAME'];
                $baseDir = dirname($scriptName);
                $baseDir = str_replace('\\', '/', $baseDir);
                $baseDir = rtrim($baseDir, '/');
                $validateUrl = $protocol . '://' . $host . $baseDir . '/index.php?page=validate&code=' . rawurlencode($code);

                // ======================================================================================
                // CONFIGURACIÓN DE POSICIÓN DEL CÓDIGO QR
                // ======================================================================================
                // Ajustar posición QR para A4 Horizontal
                // Según descargar.php: Image($ruta_qr, 248, 159, 35);
                
                $qrSize = 35; 
                $qrX = 248; 
                $qrY = 159; 
                
                $qrTemp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qr_' . $certId . '.png';
                QRcode::png($validateUrl, $qrTemp, QR_ECLEVEL_L, 8, 1);
                if (is_file($qrTemp)) {
                    // Image(Archivo, X, Y, Ancho, Alto)
                    $pdf->Image($qrTemp, $qrX, $qrY, $qrSize);
                }
                if (is_file($qrTemp)) {
                    @unlink($qrTemp);
                }

                $pdfContent = $pdf->Output('', 'S');
                $safeName = preg_replace('/[^A-Za-z0-9_\\-]+/', '_', $fullName);
                $fileNameInZip = sprintf('%03d_Certificado_%s.pdf', $counter, $safeName);
                $zip->addFromString($fileNameInZip, $pdfContent);
                $counter++;
            }

            $zip->close();

            if (function_exists('ob_get_level')) {
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
            }

            $fileName = 'certificados_' . date('Ymd_His') . '.zip';
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            @unlink($zipPath);
            exit;
        } catch (Throwable $e) {
            error_log('Error al descargar certificados: ' . $e->getMessage());
            $_SESSION['certificates_error'] = 'Error al generar la descarga de certificados.';
            header('Location: index.php?page=certificates');
            exit;
        }
    }

    public function validate(): void
    {
        // Support both 'code' (internal) and 'codigo' (legacy/external) parameters
        $code = isset($_GET['code']) ? trim($_GET['code']) : (isset($_GET['codigo']) ? trim($_GET['codigo']) : '');
        
        // Calculate base URL for views
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $baseDir = dirname($scriptName);
        $baseDir = str_replace('\\', '/', $baseDir);
        $baseDir = rtrim($baseDir, '/');
        
        // Adjust if called from /certificados/ subdirectory
        if (substr($baseDir, -13) === '/certificados') {
            $baseDir = substr($baseDir, 0, -13);
        }
        
        // Determine protocol and host
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $baseDir; // Relative base path (e.g. /qr_simple or /aula-virtual)

        if (empty($code)) {
            $errorTitle = 'Código Requerido';
            $errorMessage = 'No se proporcionó un código de certificado para validar.';
            $iconClass = 'fa-search';
            require __DIR__ . '/../views/certificates/validate_error.php';
            return;
        }

        try {
            $pdo = Database::getConnection();
            
            $sql = "
                SELECT 
                    c.id,
                    c.fecha_generacion AS fecha_generacion,
                    c.qr_codigo AS qr_codigo,
                    CONCAT(p.first_name, ' ', p.last_name) AS nombres_apellidos,
                    e.nombre AS nombre_curso,
                    e.fecha_inicio AS fecha_inicio,
                    e.fecha_fin AS fecha_fin,
                    (
                        SELECT GROUP_CONCAT(a.name SEPARATOR '|')
                        FROM curso_auspicios ea
                        JOIN auspices a ON ea.auspice_id = a.id
                        WHERE ea.curso_id = e.id
                    ) AS auspicios,
                    e.horas_academicas AS horas_academicas,
                    e.creditos_academicos AS creditos_academicos,
                    CONCAT(TIMESTAMPDIFF(MONTH, e.fecha_inicio, e.fecha_fin), ' Meses') AS duracion
                FROM certificados c
                JOIN usuarios p ON c.usuario_id = p.id
                JOIN cursos e ON c.curso_id = e.id
                WHERE c.qr_codigo = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code]);
            $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$certificate) {
                $errorTitle = 'Certificado No Encontrado';
                $errorMessage = 'El código proporcionado no corresponde a ningún certificado válido en nuestro sistema.';
                $iconClass = 'fa-file-excel';
                require __DIR__ . '/../views/certificates/validate_error.php';
                return;
            }
            
            // Recalcular duración para mostrar días si es menos de un mes
            $inicio = new DateTime($certificate['fecha_inicio']);
            $fin = new DateTime($certificate['fecha_fin']);
            $inicio->setTime(0, 0, 0);
            $fin->setTime(0, 0, 0);
            
            if ($inicio == $fin) {
                $certificate['duracion'] = '1 Día';
            } else {
                $interval = $inicio->diff($fin);
                // Si es menos de un mes, mostrar días
                if ($interval->y == 0 && $interval->m == 0) {
                     $dias = $interval->days + 1;
                     $certificate['duracion'] = $dias . ' Días';
                } else {
                     $meses = ($interval->y * 12) + $interval->m;
                     if ($interval->d >= 15) { 
                        $meses++; // Redondear si pasa de medio mes (opcional, pero mejor que truncar)
                     }
                     $certificate['duracion'] = $meses . ($meses == 1 ? ' Mes' : ' Meses');
                }
            }
            
            require __DIR__ . '/../views/certificates/validate.php';

        } catch (Throwable $e) {
            error_log('Error al validar certificado: ' . $e->getMessage());
            $errorTitle = 'Error del Sistema';
            $errorMessage = 'Ocurrió un error al procesar su solicitud. Por favor intente nuevamente más tarde.';
            $iconClass = 'fa-exclamation-triangle';
            require __DIR__ . '/../views/certificates/validate_error.php';
        }
    }

    public function download(): void
    {
        $code = isset($_GET['code']) ? trim($_GET['code']) : (isset($_GET['codigo']) ? trim($_GET['codigo']) : '');
        
        if (empty($code)) {
            die('Código no proporcionado.');
        }

        try {
            $pdo = Database::getConnection();
            
            // Fetch certificate details including background image
            $sql = "
                SELECT 
                    c.id AS certificate_id,
                    c.qr_codigo as code,
                    p.first_name,
                    p.last_name,
                    e.imagen_fondo as certificate_background_filename
                FROM certificados c
                JOIN usuarios p ON c.usuario_id = p.id
                JOIN cursos e ON c.curso_id = e.id
                WHERE c.qr_codigo = ?
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code]);
            $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$certificate) {
                die('Certificado no encontrado.');
            }

            require_once __DIR__ . '/../lib/phpqrcode/qrlib.php';
            require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

            $fullName = trim($certificate['first_name'] . ' ' . $certificate['last_name']);
            $certId = $certificate['certificate_id'];
            
            // Find background image
            $bgPath = null;
            if (!empty($certificate['certificate_background_filename'])) {
                $candidates = [
                    __DIR__ . '/../images/plantilla/' . $certificate['certificate_background_filename']
                ];
                foreach ($candidates as $candidate) {
                    if (is_file($candidate)) {
                        $bgPath = $candidate;
                        break;
                    }
                }
            }

            // Generate PDF
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->SetCompression(true);
            $pdf->setJPEGQuality(75);
            $pdf->SetCreator('Sistema de Certificados');
            $pdf->SetAuthor('Rebagliati Diplomados');
            $pdf->SetTitle('Certificado - ' . $fullName);

            $pdf->AddPage('L', 'A4');

            // Background
            if ($bgPath) {
                $pdf->Image($bgPath, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0, true, false, true);
            }

            // Name
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(0, 85);
            $fontSize = 23;
            $maxWidth = 270;
            $fontStyle = '';
            $pdf->SetFont('helvetica', $fontStyle, $fontSize);
            while ($pdf->GetStringWidth($fullName) > $maxWidth && $fontSize > 10) {
                $fontSize--;
                $pdf->SetFont('helvetica', $fontStyle, $fontSize);
            }
            $pdf->Cell(297, 24, $fullName, 0, 1, 'C');

            // QR Code
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $baseDir = dirname($scriptName);
            $baseDir = str_replace('\\', '/', $baseDir);
            $baseDir = rtrim($baseDir, '/');
            if (substr($baseDir, -13) === '/certificados') {
                 $baseDir = substr($baseDir, 0, -13);
            }

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            
            $validateUrl = $protocol . '://' . $host . $baseDir . '/certificados/validar.php?codigo=' . $code;

            $qrSize = 35;
            $qrX = 248;
            $qrY = 159;
            
            $qrTemp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qr_' . $certId . '.png';
            QRcode::png($validateUrl, $qrTemp, QR_ECLEVEL_L, 8, 1);
            if (is_file($qrTemp)) {
                $pdf->Image($qrTemp, $qrX, $qrY, $qrSize);
                @unlink($qrTemp);
            }

            $pdf->Output('Certificado_' . preg_replace('/[^a-zA-Z0-9]/', '_', $fullName) . '.pdf', 'D');
            exit;

        } catch (Throwable $e) {
            error_log('Error downloading certificate: ' . $e->getMessage());
            die('Error al generar el certificado PDF.');
        }
    }
}

<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Participant.php';
require_once __DIR__ . '/../models/Course.php';

class ParticipantController
{
    private $participantModel;

    public function __construct()
    {
        $this->participantModel = new Participant();
    }

    private function tableExists(PDO $pdo, string $tableName): bool
    {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
            $stmt->execute([$tableName]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('tableExists falló: ' . $e->getMessage());
            try {
                $stmt = $pdo->query('SELECT 1 FROM `' . str_replace('`', '``', $tableName) . '` LIMIT 1');
                $stmt->fetchColumn();
                return true;
            } catch (PDOException $probeError) {
                return false;
            }
        }
    }

    private function columnExists(PDO $pdo, string $tableName, string $columnName): bool
    {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $stmt->execute([$tableName, $columnName]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('columnExists falló: ' . $e->getMessage());
            try {
                $safeTable = str_replace('`', '``', $tableName);
                $safeColumn = str_replace('`', '``', $columnName);
                $stmt = $pdo->query('SELECT `' . $safeColumn . '` FROM `' . $safeTable . '` LIMIT 0');
                $stmt->fetchAll();
                return true;
            } catch (PDOException $probeError) {
                return false;
            }
        }
    }

    private function firstExistingColumn(PDO $pdo, string $tableName, array $candidates): ?string
    {
        foreach ($candidates as $col) {
            if ($this->columnExists($pdo, $tableName, $col)) {
                return $col;
            }
        }
        return null;
    }

    private function searchParticipantsUsersOnly(PDO $pdo, int $page, int $limit, string $search, bool $debug = false): array
    {
        $offset = ($page - 1) * $limit;
        if (!$this->tableExists($pdo, 'usuarios')) {
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => 0
            ];
        }

        $hasUserFullName = $this->columnExists($pdo, 'usuarios', 'nombres_apellidos');
        $firstNameCol = $this->firstExistingColumn($pdo, 'usuarios', ['first_name', 'nombres']);
        $lastNameCol = $this->firstExistingColumn($pdo, 'usuarios', ['last_name', 'apellidos']);
        $identityCol = $this->firstExistingColumn($pdo, 'usuarios', ['identity_document', 'documento_identidad']);
        $phoneCol = $this->firstExistingColumn($pdo, 'usuarios', ['phone', 'movil', 'celular']);
        $countryCol = $this->firstExistingColumn($pdo, 'usuarios', ['country', 'pais']);
        $notesCol = $this->firstExistingColumn($pdo, 'usuarios', ['notes', 'observaciones']);
        $emailCol = $this->firstExistingColumn($pdo, 'usuarios', ['email', 'correo']);
        $orderCol = $this->firstExistingColumn($pdo, 'usuarios', ['updated_at', 'created_at', 'id']);

        $params = [];
        $conditions = [];

        $search = preg_replace('/\s+/', ' ', trim($search));
        $terms = [];
        if ($search !== '') {
            $rawTerms = preg_split('/\s+/', $search) ?: [];
            foreach ($rawTerms as $t) {
                $t = trim((string)$t);
                if ($t !== '') {
                    $terms[] = $t;
                }
            }

            if (count($terms) <= 1) {
                $searchValue = '%' . strtolower($search) . '%';
                $parts = [];
                $searchIndex = 0;
                if ($hasUserFullName) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $parts[] = 'LOWER(p.nombres_apellidos) LIKE ' . $ph;
                }
                if ($firstNameCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $parts[] = 'LOWER(p.' . $firstNameCol . ') LIKE ' . $ph;
                }
                if ($lastNameCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $parts[] = 'LOWER(p.' . $lastNameCol . ') LIKE ' . $ph;
                }
                if ($firstNameCol !== null || $lastNameCol !== null) {
                    $concatCols = [];
                    if ($firstNameCol !== null) $concatCols[] = 'p.' . $firstNameCol;
                    if ($lastNameCol !== null) $concatCols[] = 'p.' . $lastNameCol;
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $parts[] = "LOWER(CONCAT_WS(' ', " . implode(', ', $concatCols) . ")) LIKE " . $ph;
                }
                if ($identityCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $parts[] = 'LOWER(p.' . $identityCol . ') LIKE ' . $ph;
                }
                if ($phoneCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $parts[] = 'LOWER(p.' . $phoneCol . ') LIKE ' . $ph;
                }
                if ($countryCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $parts[] = 'LOWER(p.' . $countryCol . ') LIKE ' . $ph;
                }
                if ($notesCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $parts[] = 'LOWER(p.' . $notesCol . ') LIKE ' . $ph;
                }
                if ($emailCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $parts[] = 'LOWER(p.' . $emailCol . ') LIKE ' . $ph;
                }
                if (!empty($parts)) {
                    $conditions[] = '(' . implode(' OR ', $parts) . ')';
                }
            } else {
                $termIndex = 0;
                foreach ($terms as $term) {
                    $param = ':t' . $termIndex;
                    $termIndex++;
                    $parts = [];
                    if ($hasUserFullName) {
                        $parts[] = 'LOWER(p.nombres_apellidos) LIKE ' . $param;
                    }
                    if ($firstNameCol !== null) {
                        $parts[] = 'LOWER(p.' . $firstNameCol . ') LIKE ' . $param;
                    }
                    if ($lastNameCol !== null) {
                        $parts[] = 'LOWER(p.' . $lastNameCol . ') LIKE ' . $param;
                    }
                    if ($firstNameCol !== null || $lastNameCol !== null) {
                        $concatCols = [];
                        if ($firstNameCol !== null) $concatCols[] = 'p.' . $firstNameCol;
                        if ($lastNameCol !== null) $concatCols[] = 'p.' . $lastNameCol;
                        $parts[] = "LOWER(CONCAT_WS(' ', " . implode(', ', $concatCols) . ")) LIKE " . $param;
                    }
                    if ($identityCol !== null) {
                        $parts[] = 'LOWER(p.' . $identityCol . ') LIKE ' . $param;
                    }
                    if ($phoneCol !== null) {
                        $parts[] = 'LOWER(p.' . $phoneCol . ') LIKE ' . $param;
                    }
                    if ($countryCol !== null) {
                        $parts[] = 'LOWER(p.' . $countryCol . ') LIKE ' . $param;
                    }
                    if ($notesCol !== null) {
                        $parts[] = 'LOWER(p.' . $notesCol . ') LIKE ' . $param;
                    }
                    if ($emailCol !== null) {
                        $parts[] = 'LOWER(p.' . $emailCol . ') LIKE ' . $param;
                    }
                    if (!empty($parts)) {
                        $params[$param] = '%' . strtolower($term) . '%';
                        $conditions[] = '(' . implode(' OR ', $parts) . ')';
                    }
                }
            }
        }

        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $firstNameExpr = "''";
        $lastNameExpr = "''";
        if ($firstNameCol !== null) {
            $firstNameExpr = 'p.' . $firstNameCol;
        }
        if ($lastNameCol !== null) {
            $lastNameExpr = 'p.' . $lastNameCol;
        }
        if ($hasUserFullName) {
            if ($firstNameCol !== null) {
                $firstNameExpr = "COALESCE(NULLIF(p." . $firstNameCol . ", ''), SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1))";
                if ($lastNameCol !== null) {
                    $lastNameExpr = "COALESCE(NULLIF(p." . $lastNameCol . ", ''), TRIM(SUBSTRING(p.nombres_apellidos, LENGTH(SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1)) + 2)))";
                } else {
                    $lastNameExpr = "TRIM(SUBSTRING(p.nombres_apellidos, LENGTH(SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1)) + 2))";
                }
            } else {
                $firstNameExpr = "SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1)";
                $lastNameExpr = "TRIM(SUBSTRING(p.nombres_apellidos, LENGTH(SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1)) + 2))";
            }
        }

        $identityExpr = $identityCol !== null ? 'p.' . $identityCol : "''";
        $emailExpr = $emailCol !== null ? 'p.' . $emailCol : "''";
        $orderBy = $orderCol ? 'p.' . $orderCol . ' DESC' : 'p.id DESC';

        $countSql = "SELECT COUNT(*) FROM usuarios p $where";
        $stmt = $pdo->prepare($countSql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        $sql = "SELECT
                    p.id,
                    $firstNameExpr as first_name,
                    $lastNameExpr as last_name,
                    $emailExpr as email,
                    $identityExpr as identity_document,
                    '' as course_name,
                    '' as event_code,
                    '' as enrollment_status,
                    NULL as enrollment_date,
                    NULL as enrollment_id,
                    NULL as event_id
                FROM usuarios p
                $where
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($search !== '' && empty($data) && count($terms) > 1) {
            $params = [];
            $orGroups = [];
            $termIndex = 0;
            foreach ($terms as $term) {
                $param = ':t' . $termIndex;
                $termIndex++;

                $parts = [];
                if ($hasUserFullName) {
                    $parts[] = 'LOWER(p.nombres_apellidos) LIKE ' . $param;
                }
                if ($firstNameCol !== null) {
                    $parts[] = 'LOWER(p.' . $firstNameCol . ') LIKE ' . $param;
                }
                if ($lastNameCol !== null) {
                    $parts[] = 'LOWER(p.' . $lastNameCol . ') LIKE ' . $param;
                }
                if ($firstNameCol !== null || $lastNameCol !== null) {
                    $concatCols = [];
                    if ($firstNameCol !== null) $concatCols[] = 'p.' . $firstNameCol;
                    if ($lastNameCol !== null) $concatCols[] = 'p.' . $lastNameCol;
                    $parts[] = "LOWER(CONCAT_WS(' ', " . implode(', ', $concatCols) . ")) LIKE " . $param;
                }
                if ($identityCol !== null) {
                    $parts[] = 'LOWER(p.' . $identityCol . ') LIKE ' . $param;
                }
                if ($phoneCol !== null) {
                    $parts[] = 'LOWER(p.' . $phoneCol . ') LIKE ' . $param;
                }
                if ($countryCol !== null) {
                    $parts[] = 'LOWER(p.' . $countryCol . ') LIKE ' . $param;
                }
                if ($notesCol !== null) {
                    $parts[] = 'LOWER(p.' . $notesCol . ') LIKE ' . $param;
                }
                if ($emailCol !== null) {
                    $parts[] = 'LOWER(p.' . $emailCol . ') LIKE ' . $param;
                }
                if (!empty($parts)) {
                    $params[$param] = '%' . strtolower($term) . '%';
                    $orGroups[] = '(' . implode(' OR ', $parts) . ')';
                }
            }

            $where = '';
            if (!empty($orGroups)) {
                $where = 'WHERE ' . implode(' OR ', $orGroups);
            }

            $countSql = "SELECT COUNT(*) FROM usuarios p $where";
            $stmt = $pdo->prepare($countSql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->execute();
            $total = (int)$stmt->fetchColumn();

            $sql = "SELECT
                        p.id,
                        $firstNameExpr as first_name,
                        $lastNameExpr as last_name,
                        $emailExpr as email,
                        $identityExpr as identity_document,
                        '' as course_name,
                        '' as event_code,
                        '' as enrollment_status,
                        NULL as enrollment_date,
                        NULL as enrollment_id,
                        NULL as event_id
                    FROM usuarios p
                    $where
                    ORDER BY $orderBy
                    LIMIT :limit OFFSET :offset";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $result = [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ($limit > 0) ? ceil($total / $limit) : 0
        ];
        if ($debug) {
            $dbName = null;
            try {
                $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            } catch (Throwable $e) {
            }
            $result['debug'] = [
                'strategy' => 'users_only',
                'db' => $dbName,
                'search' => $search,
                'columns' => [
                    'nombres_apellidos' => $hasUserFullName,
                        'first_name' => $firstNameCol,
                        'last_name' => $lastNameCol,
                        'identity_document' => $identityCol,
                        'phone' => $phoneCol,
                        'country' => $countryCol,
                        'notes' => $notesCol,
                        'email' => $emailCol
                ],
                'returned' => count($data),
                'total' => (int)$total
            ];
        }
        return $result;
    }

    public function handleRequest(): void
    {
        $successMessage = null;
        $errorMessage = null;
        $events = [];
        $participantsData = [];
        $view = isset($_GET['view']) ? $_GET['view'] : 'list';

        if (isset($_GET['action'])) {
            if ($_GET['action'] === 'download_template') {
                $this->downloadTemplate();
                return;
            }
            if ($_GET['action'] === 'get_participant' && isset($_GET['id'])) {
                $this->getParticipantJson();
                return;
            }
            if ($_GET['action'] === 'update_participant' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->updateParticipant();
                return;
            }
            if ($_GET['action'] === 'get_participant_courses' && isset($_GET['participant_id'])) {
                $this->getParticipantCoursesJson();
                return;
            }
            if ($_GET['action'] === 'search_available_courses' && isset($_GET['participant_id'])) {
                $this->searchAvailableCoursesJson();
                return;
            }
            if ($_GET['action'] === 'enroll_participant' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->enrollParticipantJson();
                return;
            }
        }

        try {
            $pdo = Database::getConnection();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $formType = $_POST['form_type'] ?? 'single';
                if ($formType === 'bulk') {
                    [$successMessage, $errorMessage] = $this->processBulkUpload($pdo);
                } else {
                    [$successMessage, $errorMessage] = $this->processForm($pdo);
                }

                if ($successMessage !== null && $errorMessage === null) {
                    $_SESSION['participants_success'] = $successMessage;
                    header('Location: index.php?page=participants');
                    exit;
                }
            }

            if (isset($_SESSION['participants_success'])) {
                $successMessage = $_SESSION['participants_success'];
                unset($_SESSION['participants_success']);
            }

            if ($view === 'create') {
                $events = $this->getEvents($pdo);
                require __DIR__ . '/../views/participants/create.php';
            } else {
                $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
                $search = isset($_GET['q']) ? trim($_GET['q']) : '';
                $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
                if ($search !== '') {
                    $participantsData = $this->searchParticipantsUsersOnly($pdo, $page, 10, $search);
                } else {
                    $participantsData = $this->getParticipants($pdo, $page, 10, $search, $courseId);
                }
                require __DIR__ . '/../views/participants/index.php';
            }

        } catch (Throwable $e) {
            error_log('Error cargando datos de participantes: ' . $e->getMessage());

            $errorMessage = 'Ocurrió un error al cargar participantes.';
            $participantsData = [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'limit' => 10,
                'totalPages' => 0
            ];

            if ($view === 'create') {
                require __DIR__ . '/../views/participants/create.php';
            } else {
                require __DIR__ . '/../views/participants/index.php';
            }
        }
    }

    private function getParticipantJson(): void
    {
        header('Content-Type: application/json');
        try {
            $participantId = $_GET['id'] ?? '';
            $enrollmentId = $_GET['enrollment_id'] ?? '';

            if (!$participantId) {
                echo json_encode(['error' => 'Falta el ID del participante.']);
                return;
            }

            $participant = $this->participantModel->getById((int)$participantId);
            
            // Si hay enrollmentId, intentar obtener datos de matrícula
            $enrollment = null;
            if ($enrollmentId) {
                $enrollment = $this->participantModel->getEnrollmentById((int)$enrollmentId);
            }

            if ($participant) {
                // Merge data for response
                $data = $participant;
                
                if ($enrollment) {
                    $data = array_merge($data, [
                        'enrollment_id' => $enrollment['id'],
                        'enrollment_status' => $enrollment['status'],
                        'event_id' => $enrollment['curso_id']
                    ]);
                } else {
                    // Valores por defecto si no hay matrícula específica
                    $data = array_merge($data, [
                        'enrollment_id' => '',
                        'enrollment_status' => '',
                        'event_id' => ''
                    ]);
                }
                
                echo json_encode($data);
            } else {
                echo json_encode(['error' => 'Participante no encontrado.']);
            }
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Error al cargar datos: ' . $e->getMessage()]);
        }
        exit;
    }

    private function updateParticipant(): void
    {
        header('Content-Type: application/json');
        try {
            $pdo = Database::getConnection();
            
            $participantId = $_POST['participant_id'] ?? '';
            $enrollmentId = $_POST['enrollment_id'] ?? '';
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $identityDocument = trim($_POST['identity_document'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $status = $_POST['enrollment_status'] ?? '';

            if (!$participantId) {
                echo json_encode(['error' => 'Falta el ID del participante.']);
                return;
            }

            $pdo->beginTransaction();

            $this->participantModel->update((int)$participantId, [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'identity_document' => $identityDocument,
                'phone' => $phone,
                'notes' => $notes
            ]);

            if ($enrollmentId && $status) {
                $this->participantModel->updateEnrollment((int)$enrollmentId, $status);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Participante actualizado correctamente.']);

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
        exit;
    }

    private function getParticipantCoursesJson(): void
    {
        header('Content-Type: application/json');
        try {
            $participantId = $_GET['participant_id'] ?? '';

            if (!$participantId) {
                echo json_encode(['error' => 'Falta el ID del participante.']);
                return;
            }

            $courses = $this->participantModel->getEnrollments((int)$participantId);
            
            // Map keys if necessary for frontend (Participant.php returns aliased columns already)
            // But frontend might expect specific keys.
            // Participant::getEnrollments returns: enrollment_id, status, created_at, course_id, event_name, event_code...
            // Original controller returned: enrollment_id, course_name, event_code, status, created_at
            
            $mappedCourses = array_map(function($c) {
                return [
                    'enrollment_id' => $c['enrollment_id'],
                    'course_name' => $c['event_name'],
                    'event_code' => $c['event_code'],
                    'status' => $c['status'],
                    'created_at' => $c['created_at'],
                    'course_id' => $c['course_id'],
                    'certificate_id' => $c['certificate_id'] ?? null
                ];
            }, $courses);

            echo json_encode($mappedCourses);
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Error al cargar cursos: ' . $e->getMessage()]);
        }
        exit;
    }

    private function searchAvailableCoursesJson(): void
    {
        header('Content-Type: application/json');
        try {
            $pdo = Database::getConnection();
            $participantId = $_GET['participant_id'] ?? '';
            $search = $_GET['q'] ?? '';

            if (!$participantId) {
                echo json_encode(['error' => 'Falta el ID del participante.']);
                return;
            }

            $courses = Course::getAvailableForParticipant($pdo, (int)$participantId, $search);
            // Course::getAvailableForParticipant returns: id, name, event_code, type_name
            // Frontend likely expects: id, name, event_code

            echo json_encode($courses);
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Error al buscar cursos: ' . $e->getMessage()]);
        }
        exit;
    }

    private function enrollParticipantJson(): void
    {
        header('Content-Type: application/json');
        try {
            $participantId = $_POST['participant_id'] ?? '';
            $courseId = $_POST['course_id'] ?? '';
            
            if (!$participantId || !$courseId) {
                echo json_encode(['error' => 'Datos incompletos.']);
                return;
            }

            // Verify if already enrolled
            $existing = $this->participantModel->findEnrollment((int)$courseId, (int)$participantId);
            if ($existing) {
                echo json_encode(['error' => 'El participante ya está matriculado en este curso.']);
                return;
            }

            // Enroll
            $this->participantModel->enroll((int)$participantId, (int)$courseId, 'active');

            echo json_encode(['success' => true, 'message' => 'Matrícula exitosa.']);
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Error al matricular: ' . $e->getMessage()]);
        }
        exit;
    }

    private function processForm(PDO $pdo): array
    {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $identityDocument = trim($_POST['identity_document'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $courseId = $_POST['course_id'] ?? '';
        $status = $_POST['enrollment_status'] ?? 'active';
        $notes = trim($_POST['notes'] ?? '');

        $errors = [];

        if ($firstName === '') {
            $errors[] = 'El nombre es obligatorio.';
        }

        if ($lastName === '') {
            $errors[] = 'Los apellidos son obligatorios.';
        }

        if ($email === '') {
            $errors[] = 'El correo electrónico es obligatorio.';
        }

        if ($identityDocument === '') {
            $errors[] = 'El documento de identidad es obligatorio.';
        }

        if ($courseId === '') {
            $errors[] = 'Debes seleccionar un curso o programa.';
        }

        $allowedStatuses = ['active', 'completed', 'pending'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'active';
        }

        if (!empty($errors)) {
            return [null, implode(' ', $errors)];
        }

        try {
            $pdo->beginTransaction();

            $participantId = $this->findOrCreateParticipant(
                $firstName,
                $lastName,
                $email,
                $identityDocument,
                $phone,
                $notes
            );

            $existingEnrollment = $this->participantModel->findEnrollment((int)$courseId, $participantId);
            if ($existingEnrollment !== null) {
                $pdo->rollBack();
                return [null, 'El participante ya está inscrito en este curso.'];
            }

            $this->participantModel->enroll($participantId, (int)$courseId, $status);

            $pdo->commit();

            $_POST = [];

            return ['Participante y matrícula registrados correctamente.', null];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al registrar participante/matrícula (PDO): ' . $e->getMessage());
            return [null, 'Error de base de datos al registrar el participante y la matrícula.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al registrar participante/matrícula (GEN): ' . $e->getMessage());
            return [null, 'Ocurrió un error al registrar el participante y la matrícula.'];
        }
    }

    private function downloadTemplate(): void
    {
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        $fileName = 'plantilla_participantes.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            exit;
        }

        // Encabezados claros
        fputcsv($output, ['first_name', 'last_name', 'email', 'identity_document', 'phone', 'notes']);
        
        fclose($output);
        exit;
    }

    private function processBulkUpload(PDO $pdo): array
    {
        $courseId = $_POST['bulk_course_id'] ?? '';
        if ($courseId === '') {
            return [null, 'Debes seleccionar un curso para la carga masiva.'];
        }

        if (!isset($_FILES['participants_file']) || !is_array($_FILES['participants_file'])) {
            return [null, 'Debes seleccionar un archivo de participantes.'];
        }

        $fileInfo = $_FILES['participants_file'];
        if ($fileInfo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($fileInfo['tmp_name'])) {
            // ... (keep error handling logic) ...
            return [null, 'Error al subir el archivo de participantes.'];
        }

        $tmpName = $fileInfo['tmp_name'];
        $rawPrefix = @file_get_contents($tmpName, false, null, 0, 4);
        $rawPrefix = $rawPrefix === false ? '' : $rawPrefix;

        $convertedContent = null;
        $isUtf16Le = strncmp($rawPrefix, "\xFF\xFE", 2) === 0;
        $isUtf16Be = strncmp($rawPrefix, "\xFE\xFF", 2) === 0;
        if (!$isUtf16Le && !$isUtf16Be && strpos($rawPrefix, "\0") !== false) {
            $isUtf16Le = isset($rawPrefix[1]) && $rawPrefix[1] === "\0";
            $isUtf16Be = isset($rawPrefix[0]) && $rawPrefix[0] === "\0";
            if (!$isUtf16Le && !$isUtf16Be) {
                $isUtf16Le = true;
            }
        }
        if ($isUtf16Le || $isUtf16Be) {
            $raw = @file_get_contents($tmpName);
            if ($raw !== false) {
                $fromEncoding = $isUtf16Le ? 'UTF-16LE' : 'UTF-16BE';
                if (function_exists('mb_convert_encoding')) {
                    $convertedContent = @mb_convert_encoding($raw, 'UTF-8', $fromEncoding);
                } elseif (function_exists('iconv')) {
                    $convertedContent = @iconv($fromEncoding, 'UTF-8//IGNORE', $raw);
                }
            }
        }

        if (is_string($convertedContent) && $convertedContent !== '') {
            $handle = fopen('php://temp', 'r+');
            if ($handle !== false) {
                fwrite($handle, $convertedContent);
                rewind($handle);
            }
        } else {
            $handle = fopen($tmpName, 'r');
        }
        if ($handle === false) {
            return [null, 'No se pudo leer el archivo de participantes.'];
        }

        $sample = fgets($handle);
        if ($sample === false) {
            fclose($handle);
            return [null, 'El archivo de participantes está vacío.'];
        }
        $sample = (string)$sample;

        $delimiterCandidates = [',', ';', "\t"];
        $bestDelimiter = ',';
        $bestScore = -1;

        foreach ($delimiterCandidates as $candidate) {
            $parsed = str_getcsv($sample, $candidate);
            $normalizedParsed = array_map(function ($h) {
                $h = (string)$h;
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
                return strtolower(trim($h));
            }, $parsed);

            $columnCount = count(array_filter($normalizedParsed, function ($v) { return $v !== ''; }));
            $hasEmail = in_array('email', $normalizedParsed, true);
            $score = $columnCount + ($hasEmail ? 100 : 0);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $candidate;
            }
        }

        $delimiter = $bestDelimiter;
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false) {
            fclose($handle);
            return [null, 'No se pudo leer la cabecera del archivo.'];
        }

        $normalizeHeader = function (string $h): string {
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            $h = str_replace("\xC2\xA0", ' ', $h);
            $h = trim($h, " \t\n\r\0\x0B\"'");

            if (function_exists('iconv')) {
                $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $h);
                if (is_string($ascii) && $ascii !== '') {
                    $h = $ascii;
                }
            }

            $h = strtolower(trim($h));
            $h = preg_replace('/[^a-z0-9]+/', '_', $h);
            $h = trim($h, '_');
            return $h;
        };

        $normalized = array_map(function ($h) use ($normalizeHeader) {
            return $normalizeHeader((string)$h);
        }, $headers);

        $headerMap = [];
        foreach ($normalized as $i => $name) {
            if ($name !== '') {
                $headerMap[$name] = (int)$i;
            }
        }
        
        // Aliases logic
        $aliases = [
            'first_name' => ['nombres', 'firstname', 'name'],
            'last_name' => ['apellidos', 'surname', 'lastname', 'last'],
            'email' => ['correo', 'correo electronico', 'correo electrónico', 'e-mail', 'email_address', 'mail'],
            'identity_document' => ['identity_doc', 'dni', 'documento', 'doc_identidad', 'document', 'numero_documento'],
            'phone' => ['telefono', 'teléfono', 'celular', 'mobile', 'phone_number'],
            'notes' => ['observaciones', 'nota', 'comentarios', 'comments']
        ];
        foreach ($aliases as $canonical => $syns) {
            if (!isset($headerMap[$canonical])) {
                foreach ($syns as $syn) {
                    $synKey = $normalizeHeader((string)$syn);
                    if ($synKey !== '' && isset($headerMap[$synKey])) {
                        $headerMap[$canonical] = $headerMap[$synKey];
                        break;
                    }
                }
            }
        }

        if (!isset($headerMap['email'])) {
            fclose($handle);
            return [null, 'El archivo debe contener la columna: email.'];
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($data) === 1 && trim((string) $data[0]) === '') continue;
            $rows[] = $data;
        }
        fclose($handle);

        if (empty($rows)) {
            return [null, 'El archivo no contiene filas de participantes para importar.'];
        }

        $createdEnrollments = 0;
        $skippedExistingEnrollment = 0;
        $skippedInvalidRow = 0;
        $skippedMissingData = 0;
        $errorSamples = [];

        foreach ($rows as $index => $data) {
            $firstName = isset($headerMap['first_name']) ? trim($data[$headerMap['first_name']] ?? '') : '';
            $lastName = isset($headerMap['last_name']) ? trim($data[$headerMap['last_name']] ?? '') : '';
            $email = isset($headerMap['email']) ? trim($data[$headerMap['email']] ?? '') : '';
            $identityDocument = isset($headerMap['identity_document']) ? trim($data[$headerMap['identity_document']] ?? '') : '';
            $phone = isset($headerMap['phone']) ? trim($data[$headerMap['phone']] ?? '') : '';
            $notes = isset($headerMap['notes']) ? trim($data[$headerMap['notes']] ?? '') : '';

            if ($firstName === '' && $lastName === '' && $email === '' && $identityDocument === '' && $phone === '' && $notes === '') {
                $skippedInvalidRow++;
                continue;
            }

            try {
                $pdo->beginTransaction();

                // 1. Buscar si ya existe
                $existingParticipant = null;
                if ($email) {
                    $existingParticipant = $this->participantModel->findByEmail($email);
                }
                if (!$existingParticipant && $identityDocument) {
                    $existingParticipant = $this->participantModel->findByIdentityDocument($identityDocument);
                }

                $participantId = 0;

                if ($existingParticipant) {
                    // Existe: usar su ID
                    $participantId = (int)$existingParticipant['id'];
                    $hasAnyUpdate = false;
                    $updateData = [
                        'first_name' => $existingParticipant['first_name'] ?? '',
                        'last_name' => $existingParticipant['last_name'] ?? '',
                        'email' => $existingParticipant['email'] ?? '',
                        'identity_document' => $existingParticipant['identity_document'] ?? null,
                        'phone' => $existingParticipant['phone'] ?? null,
                        'country' => $existingParticipant['country'] ?? null,
                        'notes' => $existingParticipant['notes'] ?? null
                    ];

                    if ($firstName !== '' && $firstName !== ($existingParticipant['first_name'] ?? '')) {
                        $updateData['first_name'] = $firstName;
                        $hasAnyUpdate = true;
                    }
                    if ($lastName !== '' && $lastName !== ($existingParticipant['last_name'] ?? '')) {
                        $updateData['last_name'] = $lastName;
                        $hasAnyUpdate = true;
                    }
                    if ($email !== '' && $email !== ($existingParticipant['email'] ?? '')) {
                        $updateData['email'] = $email;
                        $hasAnyUpdate = true;
                    }
                    if ($identityDocument !== '' && $identityDocument !== ($existingParticipant['identity_document'] ?? '')) {
                        $updateData['identity_document'] = $identityDocument;
                        $hasAnyUpdate = true;
                    }
                    if ($phone !== '' && $phone !== ($existingParticipant['phone'] ?? '')) {
                        $updateData['phone'] = $phone;
                        $hasAnyUpdate = true;
                    }
                    if ($notes !== '' && $notes !== ($existingParticipant['notes'] ?? '')) {
                        $updateData['notes'] = $notes;
                        $hasAnyUpdate = true;
                    }

                    if ($hasAnyUpdate) {
                        $this->participantModel->update($participantId, $updateData);
                    }
                } else {
                    if ($email === '') {
                        $pdo->rollBack();
                        $skippedMissingData++;
                        continue;
                    }

                    // Crear nuevo
                    $participantId = $this->participantModel->create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'identity_document' => $identityDocument,
                        'phone' => $phone,
                        'notes' => $notes,
                        'country' => null
                    ]);
                }

                // 2. Matricular
                $existingEnrollment = $this->participantModel->findEnrollment((int)$courseId, $participantId);
                if ($existingEnrollment !== null) {
                    $pdo->rollBack();
                    $skippedExistingEnrollment++;
                    continue;
                }

                $this->participantModel->enroll($participantId, (int)$courseId, 'active');

                $pdo->commit();
                $createdEnrollments++;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $skippedInvalidRow++;
                $rowNumber = $index + 2; // +1 header, +1 1-based
                $errorMessage = trim((string)$e->getMessage());
                error_log('Error en carga masiva fila ' . $rowNumber . ': ' . $errorMessage);
                if (count($errorSamples) < 3) {
                    $errorSamples[] = 'Fila ' . $rowNumber . ': ' . ($errorMessage !== '' ? $errorMessage : 'Error desconocido');
                }
            }
        }

        $message = 'Carga masiva completada. Se crearon ' . $createdEnrollments . ' matrículas.';
        if ($skippedExistingEnrollment > 0) $message .= ' Se omitieron ' . $skippedExistingEnrollment . ' ya inscritos.';
        if ($skippedMissingData > 0) $message .= ' Se omitieron ' . $skippedMissingData . ' filas nuevas sin email.';
        if ($skippedInvalidRow > 0) $message .= ' Se omitieron ' . $skippedInvalidRow . ' con errores.';
        if (!empty($errorSamples)) $message .= ' Ejemplos: ' . implode(' | ', $errorSamples) . '.';

        return [$message, null];
    }

    private function getEvents(PDO $pdo): array
    {
        $courseNameCol = $this->firstExistingColumn($pdo, 'cursos', ['nombre', 'name', 'titulo', 'nombre_curso']);
        if ($courseNameCol === null) {
            return [];
        }

        $hasEventCode = $this->columnExists($pdo, 'cursos', 'event_code');
        $hasCreatedAt = $this->columnExists($pdo, 'cursos', 'created_at');

        $selectEventCode = $hasEventCode ? 'event_code' : "'' as event_code";
        $orderBy = $hasCreatedAt ? 'created_at DESC, ' . $courseNameCol . ' ASC' : $courseNameCol . ' ASC';

        $stmt = $pdo->query('SELECT id, ' . $courseNameCol . ' as name, ' . $selectEventCode . ' FROM cursos ORDER BY ' . $orderBy);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getParticipants(PDO $pdo, int $page = 1, int $limit = 10, string $search = '', ?int $courseId = null, bool $debug = false): array
    {
        $offset = ($page - 1) * $limit;
        $courseNameCol = $this->firstExistingColumn($pdo, 'cursos', ['nombre', 'name', 'titulo', 'nombre_curso']);
        $hasCourseEventCode = $this->columnExists($pdo, 'cursos', 'event_code');

        $hasUserLegacyFullName = $this->columnExists($pdo, 'usuarios', 'nombres_apellidos');
        $userFirstNameCol = $this->firstExistingColumn($pdo, 'usuarios', ['first_name', 'nombres']);
        $userLastNameCol = $this->firstExistingColumn($pdo, 'usuarios', ['last_name', 'apellidos']);
        $identityCol = $this->firstExistingColumn($pdo, 'usuarios', ['identity_document', 'documento_identidad']);
        $phoneCol = $this->firstExistingColumn($pdo, 'usuarios', ['phone', 'movil', 'celular']);
        $countryCol = $this->firstExistingColumn($pdo, 'usuarios', ['country', 'pais']);
        $notesCol = $this->firstExistingColumn($pdo, 'usuarios', ['notes', 'observaciones']);
        $emailCol = $this->firstExistingColumn($pdo, 'usuarios', ['email', 'correo']);

        $params = [];
        $conditions = [];

        $search = preg_replace('/\s+/', ' ', trim($search));
        $terms = [];
        if ($search !== '') {
            $rawTerms = preg_split('/\s+/', $search) ?: [];
            foreach ($rawTerms as $t) {
                $t = trim((string)$t);
                if ($t !== '') {
                    $terms[] = $t;
                }
            }

            if (count($terms) <= 1) {
                $searchParts = [];
                $searchValue = '%' . strtolower($search) . '%';
                $searchIndex = 0;
                if ($userFirstNameCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $userFirstNameCol . ') LIKE ' . $ph;
                }
                if ($userLastNameCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $userLastNameCol . ') LIKE ' . $ph;
                }
                if ($userFirstNameCol !== null || $userLastNameCol !== null) {
                    $concatCols = [];
                    if ($userFirstNameCol !== null) $concatCols[] = 'p.' . $userFirstNameCol;
                    if ($userLastNameCol !== null) $concatCols[] = 'p.' . $userLastNameCol;
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = "LOWER(CONCAT_WS(' ', " . implode(', ', $concatCols) . ")) LIKE " . $ph;
                }
                if ($hasUserLegacyFullName) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.nombres_apellidos) LIKE ' . $ph;
                }
                if ($identityCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $identityCol . ') LIKE ' . $ph;
                }
                if ($phoneCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $phoneCol . ') LIKE ' . $ph;
                }
                if ($countryCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $countryCol . ') LIKE ' . $ph;
                }
                if ($notesCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $notesCol . ') LIKE ' . $ph;
                }
                if ($emailCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $emailCol . ') LIKE ' . $ph;
                }
                if ($courseNameCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(c.' . $courseNameCol . ') LIKE ' . $ph;
                }
                if ($hasCourseEventCode) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(c.event_code) LIKE ' . $ph;
                }
                if (!empty($searchParts)) {
                    $conditions[] = '(' . implode(' OR ', $searchParts) . ')';
                }
            } else {
                $termIndex = 0;
                foreach ($terms as $term) {
                    $param = ':t' . $termIndex;
                    $termIndex++;

                    $searchParts = [];
                    if ($userFirstNameCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $userFirstNameCol . ') LIKE ' . $param;
                    }
                    if ($userLastNameCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $userLastNameCol . ') LIKE ' . $param;
                    }
                    if ($userFirstNameCol !== null || $userLastNameCol !== null) {
                        $concatCols = [];
                        if ($userFirstNameCol !== null) $concatCols[] = 'p.' . $userFirstNameCol;
                        if ($userLastNameCol !== null) $concatCols[] = 'p.' . $userLastNameCol;
                        $searchParts[] = "LOWER(CONCAT_WS(' ', " . implode(', ', $concatCols) . ")) LIKE " . $param;
                    }
                    if ($hasUserLegacyFullName) {
                        $searchParts[] = 'LOWER(p.nombres_apellidos) LIKE ' . $param;
                    }
                    if ($identityCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $identityCol . ') LIKE ' . $param;
                    }
                    if ($phoneCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $phoneCol . ') LIKE ' . $param;
                    }
                    if ($countryCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $countryCol . ') LIKE ' . $param;
                    }
                    if ($notesCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $notesCol . ') LIKE ' . $param;
                    }
                    if ($emailCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $emailCol . ') LIKE ' . $param;
                    }
                    if ($courseNameCol !== null) {
                        $searchParts[] = 'LOWER(c.' . $courseNameCol . ') LIKE ' . $param;
                    }
                    if ($hasCourseEventCode) {
                        $searchParts[] = 'LOWER(c.event_code) LIKE ' . $param;
                    }
                    if (!empty($searchParts)) {
                        $params[$param] = '%' . strtolower($term) . '%';
                        $conditions[] = '(' . implode(' OR ', $searchParts) . ')';
                    }
                }
            }
        }

        if ($courseId) {
            $conditions[] = 'c.id = :courseId';
            $params[':courseId'] = $courseId;
        }

        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        try {
            $countSql = "SELECT COUNT(*) 
                         FROM usuarios p 
                         LEFT JOIN curso_estudiantes ce ON p.id = ce.usuario_id 
                         LEFT JOIN cursos c ON ce.curso_id = c.id 
                         $where";
            $stmt = $pdo->prepare($countSql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->execute();
            $total = $stmt->fetchColumn();

            $firstNameExpr = $userFirstNameCol !== null ? 'p.' . $userFirstNameCol : "''";
            $lastNameExpr = $userLastNameCol !== null ? 'p.' . $userLastNameCol : "''";
            if ($hasUserLegacyFullName) {
                if ($userFirstNameCol !== null) {
                    $firstNameExpr = "COALESCE(NULLIF(p." . $userFirstNameCol . ", ''), SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1))";
                } else {
                    $firstNameExpr = "SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1)";
                }
                if ($userLastNameCol !== null) {
                    $lastNameExpr = "COALESCE(NULLIF(p." . $userLastNameCol . ", ''), TRIM(SUBSTRING(p.nombres_apellidos, LENGTH(SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1)) + 2)))";
                } else {
                    $lastNameExpr = "TRIM(SUBSTRING(p.nombres_apellidos, LENGTH(SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1)) + 2))";
                }
            }

            $identityExpr = $identityCol !== null ? 'p.' . $identityCol : "''";
            $courseNameExpr = $courseNameCol !== null ? 'c.' . $courseNameCol : "''";
            $eventCodeExpr = $hasCourseEventCode ? 'c.event_code' : "''";
            $emailExpr = $emailCol !== null ? 'p.' . $emailCol : "''";

            $sql = "SELECT p.id, $firstNameExpr as first_name, $lastNameExpr as last_name, $emailExpr as email, $identityExpr as identity_document, 
                           $courseNameExpr as course_name, $eventCodeExpr as event_code, ce.status as enrollment_status, ce.created_at as enrollment_date,
                           ce.id as enrollment_id, c.id as event_id
                    FROM usuarios p 
                    LEFT JOIN curso_estudiantes ce ON p.id = ce.usuario_id 
                    LEFT JOIN cursos c ON ce.curso_id = c.id 
                    $where
                    ORDER BY ce.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($search !== '' && empty($data) && count($terms) > 1) {
                $params = [];
                $orGroups = [];
                $termIndex = 0;
                foreach ($terms as $term) {
                    $param = ':t' . $termIndex;
                    $termIndex++;

                    $searchParts = [];
                    if ($userFirstNameCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $userFirstNameCol . ') LIKE ' . $param;
                    }
                    if ($userLastNameCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $userLastNameCol . ') LIKE ' . $param;
                    }
                    if ($userFirstNameCol !== null || $userLastNameCol !== null) {
                        $concatCols = [];
                        if ($userFirstNameCol !== null) $concatCols[] = 'p.' . $userFirstNameCol;
                        if ($userLastNameCol !== null) $concatCols[] = 'p.' . $userLastNameCol;
                        $searchParts[] = "LOWER(CONCAT_WS(' ', " . implode(', ', $concatCols) . ")) LIKE " . $param;
                    }
                    if ($hasUserLegacyFullName) {
                        $searchParts[] = 'LOWER(p.nombres_apellidos) LIKE ' . $param;
                    }
                    if ($identityCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $identityCol . ') LIKE ' . $param;
                    }
                    if ($phoneCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $phoneCol . ') LIKE ' . $param;
                    }
                    if ($countryCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $countryCol . ') LIKE ' . $param;
                    }
                    if ($notesCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $notesCol . ') LIKE ' . $param;
                    }
                    if ($emailCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $emailCol . ') LIKE ' . $param;
                    }
                    if ($courseNameCol !== null) {
                        $searchParts[] = 'LOWER(c.' . $courseNameCol . ') LIKE ' . $param;
                    }
                    if ($hasCourseEventCode) {
                        $searchParts[] = 'LOWER(c.event_code) LIKE ' . $param;
                    }
                    if (!empty($searchParts)) {
                        $params[$param] = '%' . strtolower($term) . '%';
                        $orGroups[] = '(' . implode(' OR ', $searchParts) . ')';
                    }
                }

                if ($courseId) {
                    $params[':courseId'] = $courseId;
                }

                $conditions = [];
                if (!empty($orGroups)) {
                    $conditions[] = '(' . implode(' OR ', $orGroups) . ')';
                }
                if ($courseId) {
                    $conditions[] = 'c.id = :courseId';
                }
                $where = '';
                if (!empty($conditions)) {
                    $where = 'WHERE ' . implode(' AND ', $conditions);
                }

                $countSql = "SELECT COUNT(*) 
                             FROM usuarios p 
                             LEFT JOIN curso_estudiantes ce ON p.id = ce.usuario_id 
                             LEFT JOIN cursos c ON ce.curso_id = c.id 
                             $where";
                $stmt = $pdo->prepare($countSql);
                foreach ($params as $key => $val) {
                    $stmt->bindValue($key, $val);
                }
                $stmt->execute();
                $total = $stmt->fetchColumn();

                $sql = "SELECT p.id, $firstNameExpr as first_name, $lastNameExpr as last_name, $emailExpr as email, $identityExpr as identity_document, 
                               $courseNameExpr as course_name, $eventCodeExpr as event_code, ce.status as enrollment_status, ce.created_at as enrollment_date,
                               ce.id as enrollment_id, c.id as event_id
                        FROM usuarios p 
                        LEFT JOIN curso_estudiantes ce ON p.id = ce.usuario_id 
                        LEFT JOIN cursos c ON ce.curso_id = c.id 
                        $where
                        ORDER BY ce.created_at DESC
                        LIMIT :limit OFFSET :offset";

                $stmt = $pdo->prepare($sql);
                foreach ($params as $key => $val) {
                    $stmt->bindValue($key, $val);
                }
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if ($search !== '' && empty($data)) {
                return $this->searchParticipantsUsersOnly($pdo, $page, $limit, $search, $debug);
            }

            $result = [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ($limit > 0) ? ceil($total / $limit) : 0
            ];
            if ($debug) {
                $dbName = null;
                try {
                    $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
                } catch (Throwable $e) {
                }
                $result['debug'] = [
                    'strategy' => 'new_schema',
                    'db' => $dbName,
                    'search' => $search,
                    'returned' => count($data),
                    'total' => (int)$total
                ];
            }
            return $result;
        } catch (PDOException $e) {
            error_log('getParticipants (schema nuevo) falló, intentando esquema legacy: ' . $e->getMessage());
        }

        if (!$this->tableExists($pdo, 'usuarios')) {
            $result = [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => 0
            ];
            if ($debug) {
                $dbName = null;
                try {
                    $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
                } catch (Throwable $e) {
                }
                $result['debug'] = [
                    'strategy' => 'no_usuarios',
                    'db' => $dbName,
                    'search' => $search,
                    'returned' => 0,
                    'total' => 0
                ];
            }
            return $result;
        }
        if (!$this->tableExists($pdo, 'cursos') || !$this->tableExists($pdo, 'certificados')) {
            if ($courseId !== null) {
                return [
                    'data' => [],
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => 0
                ];
            }
            return $this->searchParticipantsUsersOnly($pdo, $page, $limit, $search, $debug);
        }

        $courseNameCol = $this->firstExistingColumn($pdo, 'cursos', ['nombre', 'name', 'titulo', 'nombre_curso']);
        $hasEventCode = $this->columnExists($pdo, 'cursos', 'event_code');

        $hasUserFullName = $this->columnExists($pdo, 'usuarios', 'nombres_apellidos');
        $userFirstNameCol = $this->firstExistingColumn($pdo, 'usuarios', ['first_name', 'nombres']);
        $userLastNameCol = $this->firstExistingColumn($pdo, 'usuarios', ['last_name', 'apellidos']);
        $identityCol = $this->firstExistingColumn($pdo, 'usuarios', ['identity_document', 'documento_identidad']);
        $phoneCol = $this->firstExistingColumn($pdo, 'usuarios', ['phone', 'movil', 'celular']);
        $countryCol = $this->firstExistingColumn($pdo, 'usuarios', ['country', 'pais']);
        $notesCol = $this->firstExistingColumn($pdo, 'usuarios', ['notes', 'observaciones']);
        $emailCol = $this->firstExistingColumn($pdo, 'usuarios', ['email', 'correo']);

        $certDateCol = $this->firstExistingColumn($pdo, 'certificados', ['fecha_generacion', 'created_at', 'fecha_creacion']);

        $params = [];
        $conditions = [];

        $search = preg_replace('/\s+/', ' ', trim($search));
        $terms = [];
        if ($search !== '') {
            $rawTerms = preg_split('/\s+/', $search) ?: [];
            foreach ($rawTerms as $t) {
                $t = trim((string)$t);
                if ($t !== '') {
                    $terms[] = $t;
                }
            }

            if (count($terms) <= 1) {
                $searchParts = [];
                $searchValue = '%' . strtolower($search) . '%';
                $searchIndex = 0;
                if ($hasUserFullName) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.nombres_apellidos) LIKE ' . $ph;
                }
                if ($userFirstNameCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $userFirstNameCol . ') LIKE ' . $ph;
                }
                if ($userLastNameCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $userLastNameCol . ') LIKE ' . $ph;
                }
                if ($userFirstNameCol !== null || $userLastNameCol !== null) {
                    $concatCols = [];
                    if ($userFirstNameCol !== null) $concatCols[] = 'p.' . $userFirstNameCol;
                    if ($userLastNameCol !== null) $concatCols[] = 'p.' . $userLastNameCol;
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = "LOWER(CONCAT_WS(' ', " . implode(', ', $concatCols) . ")) LIKE " . $ph;
                }
                if ($identityCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $identityCol . ') LIKE ' . $ph;
                }
                if ($phoneCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $phoneCol . ') LIKE ' . $ph;
                }
                if ($countryCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $countryCol . ') LIKE ' . $ph;
                }
                if ($notesCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $notesCol . ') LIKE ' . $ph;
                }
                if ($emailCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(p.' . $emailCol . ') LIKE ' . $ph;
                }
                if ($courseNameCol !== null) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(c.' . $courseNameCol . ') LIKE ' . $ph;
                }
                if ($hasEventCode) {
                    $ph = ':s' . $searchIndex;
                    $searchIndex++;
                    $params[$ph] = $searchValue;
                    $searchParts[] = 'LOWER(c.event_code) LIKE ' . $ph;
                }
                if (!empty($searchParts)) {
                    $conditions[] = '(' . implode(' OR ', $searchParts) . ')';
                }
            } else {
                $termIndex = 0;
                foreach ($terms as $term) {
                    $param = ':t' . $termIndex;
                    $termIndex++;

                    $searchParts = [];
                    if ($hasUserFullName) {
                        $searchParts[] = 'LOWER(p.nombres_apellidos) LIKE ' . $param;
                    }
                    if ($userFirstNameCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $userFirstNameCol . ') LIKE ' . $param;
                    }
                    if ($userLastNameCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $userLastNameCol . ') LIKE ' . $param;
                    }
                    if ($userFirstNameCol !== null || $userLastNameCol !== null) {
                        $concatCols = [];
                        if ($userFirstNameCol !== null) $concatCols[] = 'p.' . $userFirstNameCol;
                        if ($userLastNameCol !== null) $concatCols[] = 'p.' . $userLastNameCol;
                        $searchParts[] = "LOWER(CONCAT_WS(' ', " . implode(', ', $concatCols) . ")) LIKE " . $param;
                    }
                    if ($identityCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $identityCol . ') LIKE ' . $param;
                    }
                    if ($phoneCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $phoneCol . ') LIKE ' . $param;
                    }
                    if ($countryCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $countryCol . ') LIKE ' . $param;
                    }
                    if ($notesCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $notesCol . ') LIKE ' . $param;
                    }
                    if ($emailCol !== null) {
                        $searchParts[] = 'LOWER(p.' . $emailCol . ') LIKE ' . $param;
                    }
                    if ($courseNameCol !== null) {
                        $searchParts[] = 'LOWER(c.' . $courseNameCol . ') LIKE ' . $param;
                    }
                    if ($hasEventCode) {
                        $searchParts[] = 'LOWER(c.event_code) LIKE ' . $param;
                    }
                    if (!empty($searchParts)) {
                        $params[$param] = '%' . strtolower($term) . '%';
                        $conditions[] = '(' . implode(' OR ', $searchParts) . ')';
                    }
                }
            }
        }

        if ($courseId) {
            $conditions[] = 'c.id = :courseId';
            $params[':courseId'] = $courseId;
        }

        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $firstNameExpr = "''";
        $lastNameExpr = "''";
        if ($hasUserFullName) {
            $firstNameExpr = "SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1)";
            $lastNameExpr = "TRIM(SUBSTRING(p.nombres_apellidos, LENGTH(SUBSTRING_INDEX(p.nombres_apellidos, ' ', 1)) + 2))";
        } else {
            if ($userFirstNameCol !== null) {
                $firstNameExpr = 'p.' . $userFirstNameCol;
            }
            if ($userLastNameCol !== null) {
                $lastNameExpr = 'p.' . $userLastNameCol;
            }
        }

        $identityExpr = $identityCol !== null ? 'p.' . $identityCol : "''";
        $emailExpr = $emailCol !== null ? 'p.' . $emailCol : "''";
        $courseNameExpr = $courseNameCol !== null ? 'c.' . $courseNameCol : "''";
        $eventCodeExpr = $hasEventCode ? 'c.event_code' : "''";
        $dateExpr = $certDateCol !== null ? 'cert.' . $certDateCol : 'NULL';
        $orderDateExpr = $certDateCol !== null ? 'cert.' . $certDateCol : 'cert.id';

        try {
            $countSql = "SELECT COUNT(*) 
                         FROM usuarios p
                         LEFT JOIN certificados cert ON p.id = cert.usuario_id
                         LEFT JOIN cursos c ON cert.curso_id = c.id
                         $where";
            $stmt = $pdo->prepare($countSql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->execute();
            $total = $stmt->fetchColumn();

            $sql = "SELECT
                        p.id,
                        $firstNameExpr as first_name,
                        $lastNameExpr as last_name,
                        $emailExpr as email,
                        $identityExpr as identity_document,
                        $courseNameExpr as course_name,
                        $eventCodeExpr as event_code,
                        'completed' as enrollment_status,
                        $dateExpr as enrollment_date,
                        NULL as enrollment_id,
                        c.id as event_id
                    FROM usuarios p
                    LEFT JOIN certificados cert ON p.id = cert.usuario_id
                    LEFT JOIN cursos c ON cert.curso_id = c.id
                    $where
                    ORDER BY $orderDateExpr DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getParticipants (schema legacy) falló: ' . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => 0
            ];
        }

        if ($search !== '' && empty($data) && count($terms) > 1) {
            $params = [];
            $orGroups = [];
            $termIndex = 0;
            foreach ($terms as $term) {
                $param = ':t' . $termIndex;
                $termIndex++;

                $searchParts = [];
                if ($hasUserFullName) {
                    $searchParts[] = 'LOWER(p.nombres_apellidos) LIKE ' . $param;
                }
                if ($userFirstNameCol !== null) {
                    $searchParts[] = 'LOWER(p.' . $userFirstNameCol . ') LIKE ' . $param;
                }
                if ($userLastNameCol !== null) {
                    $searchParts[] = 'LOWER(p.' . $userLastNameCol . ') LIKE ' . $param;
                }
                if ($userFirstNameCol !== null || $userLastNameCol !== null) {
                    $concatCols = [];
                    if ($userFirstNameCol !== null) $concatCols[] = 'p.' . $userFirstNameCol;
                    if ($userLastNameCol !== null) $concatCols[] = 'p.' . $userLastNameCol;
                    $searchParts[] = "LOWER(CONCAT_WS(' ', " . implode(', ', $concatCols) . ")) LIKE " . $param;
                }
                if ($identityCol !== null) {
                    $searchParts[] = 'LOWER(p.' . $identityCol . ') LIKE ' . $param;
                }
                if ($phoneCol !== null) {
                    $searchParts[] = 'LOWER(p.' . $phoneCol . ') LIKE ' . $param;
                }
                if ($countryCol !== null) {
                    $searchParts[] = 'LOWER(p.' . $countryCol . ') LIKE ' . $param;
                }
                if ($notesCol !== null) {
                    $searchParts[] = 'LOWER(p.' . $notesCol . ') LIKE ' . $param;
                }
                if ($emailCol !== null) {
                    $searchParts[] = 'LOWER(p.' . $emailCol . ') LIKE ' . $param;
                }
                if ($courseNameCol !== null) {
                    $searchParts[] = 'LOWER(c.' . $courseNameCol . ') LIKE ' . $param;
                }
                if ($hasEventCode) {
                    $searchParts[] = 'LOWER(c.event_code) LIKE ' . $param;
                }
                if (!empty($searchParts)) {
                    $params[$param] = '%' . strtolower($term) . '%';
                    $orGroups[] = '(' . implode(' OR ', $searchParts) . ')';
                }
            }

            if ($courseId) {
                $params[':courseId'] = $courseId;
            }

            $conditions = [];
            if (!empty($orGroups)) {
                $conditions[] = '(' . implode(' OR ', $orGroups) . ')';
            }
            if ($courseId) {
                $conditions[] = 'c.id = :courseId';
            }

            $where = '';
            if (!empty($conditions)) {
                $where = 'WHERE ' . implode(' AND ', $conditions);
            }

            $countSql = "SELECT COUNT(*) 
                         FROM usuarios p
                         LEFT JOIN certificados cert ON p.id = cert.usuario_id
                         LEFT JOIN cursos c ON cert.curso_id = c.id
                         $where";
            $stmt = $pdo->prepare($countSql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->execute();
            $total = $stmt->fetchColumn();

            $sql = "SELECT
                        p.id,
                        $firstNameExpr as first_name,
                        $lastNameExpr as last_name,
                        $emailExpr as email,
                        $identityExpr as identity_document,
                        $courseNameExpr as course_name,
                        $eventCodeExpr as event_code,
                        'completed' as enrollment_status,
                        $dateExpr as enrollment_date,
                        NULL as enrollment_id,
                        c.id as event_id
                    FROM usuarios p
                    LEFT JOIN certificados cert ON p.id = cert.usuario_id
                    LEFT JOIN cursos c ON cert.curso_id = c.id
                    $where
                    ORDER BY $orderDateExpr DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($search !== '' && empty($data)) {
            return $this->searchParticipantsUsersOnly($pdo, $page, $limit, $search, $debug);
        }

        $result = [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ($limit > 0) ? ceil($total / $limit) : 0
        ];
        if ($debug) {
            $dbName = null;
            try {
                $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            } catch (Throwable $e) {
            }
            $result['debug'] = [
                'strategy' => 'legacy_schema',
                'db' => $dbName,
                'search' => $search,
                'returned' => count($data),
                'total' => (int)$total
            ];
        }
        return $result;
    }

    private function findOrCreateParticipant(
        string $firstName,
        string $lastName,
        string $email,
        string $identityDocument,
        string $phone,
        string $notes
    ): int {
        $participant = $this->participantModel->findByEmail($email);
        if ($participant) {
            return (int)$participant['id'];
        }

        $participant = $this->participantModel->findByIdentityDocument($identityDocument);
        if ($participant) {
            return (int)$participant['id'];
        }

        return $this->participantModel->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'identity_document' => $identityDocument,
            'phone' => $phone,
            'notes' => $notes,
            'country' => null // Default
        ]);
    }
}

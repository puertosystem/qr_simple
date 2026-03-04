<?php
require_once __DIR__ . '/../config/database.php';

class Participant {
    private $pdo;
    private $table = 'usuarios';

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function findByEmail(string $email) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByIdentityDocument(string $identityDocument) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE identity_document = ? LIMIT 1");
        $stmt->execute([$identityDocument]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int {
        // Sync nombres_apellidos for legacy compatibility
        $nombresApellidos = trim($data['first_name'] . ' ' . $data['last_name']);

        $sql = "INSERT INTO {$this->table} (
                    first_name, 
                    last_name, 
                    nombres_apellidos,
                    email, 
                    identity_document, 
                    phone, 
                    country,
                    notes,
                    created_at, 
                    updated_at
                ) VALUES (
                    :first_name, 
                    :last_name, 
                    :nombres_apellidos,
                    :email, 
                    :identity_document, 
                    :phone, 
                    :country,
                    :notes,
                    NOW(), 
                    NOW()
                )";

        $stmt = $this->pdo->prepare($sql);
        
        $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':nombres_apellidos' => $nombresApellidos,
            ':email' => $data['email'],
            ':identity_document' => $data['identity_document'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':country' => $data['country'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void {
        // Sync nombres_apellidos for legacy compatibility
        $nombresApellidos = trim($data['first_name'] . ' ' . $data['last_name']);

        $sql = "UPDATE {$this->table} SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    nombres_apellidos = :nombres_apellidos,
                    email = :email,
                    identity_document = :identity_document,
                    phone = :phone,
                    country = :country,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':nombres_apellidos' => $nombresApellidos,
            ':email' => $data['email'],
            ':identity_document' => $data['identity_document'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':country' => $data['country'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);
    }
    
    public function getEnrollments(int $participantId) {
        // Join with cursos (mapped from events) and left join with certificados
        $sql = "SELECT 
                    ce.id as enrollment_id,
                    ce.status,
                    ce.created_at,
                    c.id as course_id,
                    c.nombre as event_name,
                    c.event_code,
                    c.fecha_inicio as start_date,
                    c.fecha_fin as end_date,
                    cert.id as certificate_id
                FROM curso_estudiantes ce
                JOIN cursos c ON ce.curso_id = c.id
                LEFT JOIN certificados cert ON cert.curso_id = c.id AND cert.usuario_id = ce.usuario_id
                WHERE ce.usuario_id = ?
                ORDER BY ce.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$participantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function paginate(int $page, int $limit, string $search): array {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = '';

        if ($search !== '') {
            $where = "WHERE (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR identity_document LIKE :search)";
            $params[':search'] = "%$search%";
        }

        // Count total
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} $where");
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();
        $totalPages = ($limit > 0) ? ceil($total / $limit) : 0;

        // Fetch data
        $sql = "SELECT * FROM {$this->table} $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages
        ];
    }

    public function findEnrollment(int $courseId, int $participantId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM curso_estudiantes WHERE curso_id = ? AND usuario_id = ? LIMIT 1");
        $stmt->execute([$courseId, $participantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function enroll(int $participantId, int $courseId, string $status = 'active'): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO curso_estudiantes (curso_id, usuario_id, status, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$courseId, $participantId, $status]);
    }

    public function updateEnrollment(int $enrollmentId, string $status): void {
        $stmt = $this->pdo->prepare("UPDATE curso_estudiantes SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $enrollmentId]);
    }

    public function getEnrollmentById(int $enrollmentId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM curso_estudiantes WHERE id = ?");
        $stmt->execute([$enrollmentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}

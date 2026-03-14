<?php
require_once __DIR__ . '/../config/database.php';

class Participant {
    private $pdo;
    private $table = 'usuarios';
    private $columnCache = [];

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    private function columnExists(string $column, ?string $table = null): bool {
        $table = $table ?: $this->table;
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->columnCache)) {
            return (bool)$this->columnCache[$cacheKey];
        }

        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$column]);
            $exists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $exists = false;
        }

        $this->columnCache[$cacheKey] = $exists;
        return $exists;
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

        $fields = [
            'first_name',
            'last_name',
            'nombres_apellidos',
            'email'
        ];
        foreach (['identity_document', 'phone', 'notes', 'country'] as $optionalColumn) {
            if ($this->columnExists($optionalColumn)) {
                $fields[] = $optionalColumn;
            }
        }

        $placeholders = array_map(function ($field) {
            return ':' . $field;
        }, $fields);

        $insertFields = $fields;
        $insertValues = $placeholders;
        if ($this->columnExists('created_at')) {
            $insertFields[] = 'created_at';
            $insertValues[] = 'NOW()';
        }
        if ($this->columnExists('updated_at')) {
            $insertFields[] = 'updated_at';
            $insertValues[] = 'NOW()';
        }

        $sql = "INSERT INTO {$this->table} (
                    " . implode(", \n                    ", $insertFields) . "
                ) VALUES (
                    " . implode(", \n                    ", $insertValues) . "
                )";

        $stmt = $this->pdo->prepare($sql);

        $params = [
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':nombres_apellidos' => $nombresApellidos,
            ':email' => $data['email'],
        ];
        foreach (['identity_document', 'phone', 'notes', 'country'] as $optionalColumn) {
            if ($this->columnExists($optionalColumn)) {
                $params[':' . $optionalColumn] = $data[$optionalColumn] ?? null;
            }
        }

        $stmt->execute($params);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void {
        // Sync nombres_apellidos for legacy compatibility
        $nombresApellidos = trim($data['first_name'] . ' ' . $data['last_name']);

        $setParts = [
            'first_name = :first_name',
            'last_name = :last_name',
            'nombres_apellidos = :nombres_apellidos',
            'email = :email'
        ];
        foreach (['identity_document', 'phone', 'notes', 'country'] as $optionalColumn) {
            if ($this->columnExists($optionalColumn)) {
                $setParts[] = $optionalColumn . ' = :' . $optionalColumn;
            }
        }
        if ($this->columnExists('updated_at')) {
            $setParts[] = 'updated_at = NOW()';
        }

        $sql = "UPDATE {$this->table} SET 
                    " . implode(",\n                    ", $setParts) . "
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $params = [
            ':id' => $id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':nombres_apellidos' => $nombresApellidos,
            ':email' => $data['email'],
        ];
        foreach (['identity_document', 'phone', 'notes', 'country'] as $optionalColumn) {
            if ($this->columnExists($optionalColumn)) {
                $params[':' . $optionalColumn] = $data[$optionalColumn] ?? null;
            }
        }

        $stmt->execute($params);
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
        $table = 'curso_estudiantes';
        $fields = ['curso_id', 'usuario_id', 'status'];
        $values = ['?', '?', '?'];
        $params = [$courseId, $participantId, $status];

        if ($this->columnExists('created_at', $table)) {
            $fields[] = 'created_at';
            $values[] = 'NOW()';
        }
        if ($this->columnExists('updated_at', $table)) {
            $fields[] = 'updated_at';
            $values[] = 'NOW()';
        }

        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function updateEnrollment(int $enrollmentId, string $status): void {
        $table = 'curso_estudiantes';
        $setParts = ['status = ?'];
        $params = [$status];
        if ($this->columnExists('updated_at', $table)) {
            $setParts[] = 'updated_at = NOW()';
        }
        $params[] = $enrollmentId;

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
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

<?php
require_once __DIR__ . '/../config/database.php';

class Certificate {
    private $pdo;
    private $table = 'certificados';

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function create(int $userId, int $courseId, string $qrCode): int {
        $sql = "INSERT INTO {$this->table} (
                    usuario_id, 
                    curso_id, 
                    qr_codigo, 
                    fecha_generacion
                ) VALUES (
                    :usuario_id, 
                    :curso_id, 
                    :qr_codigo, 
                    NOW()
                )";

        $stmt = $this->pdo->prepare($sql);
        
        $stmt->execute([
            ':usuario_id' => $userId,
            ':curso_id' => $courseId,
            ':qr_codigo' => $qrCode
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findByQrCode(string $qrCode) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE qr_codigo = ? LIMIT 1");
        $stmt->execute([$qrCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByEnrollment(int $userId, int $courseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE usuario_id = ? AND curso_id = ? LIMIT 1");
        $stmt->execute([$userId, $courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

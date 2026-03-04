<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $pdo;
    private $table = 'user'; // Adapted to 'user' table in aulavirtual

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function findByEmail($email) {
        // The migration added 'email' column to 'user' table
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([$email, $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

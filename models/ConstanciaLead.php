<?php

require_once __DIR__ . '/../config/database.php';

class ConstanciaLead
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getAll($limit = 10, $offset = 0, $search = '')
    {
        $sql = "SELECT * FROM constancia_leads";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE nombres LIKE ? OR apellidos LIKE ? OR documento_identidad LIKE ?";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }

        $sql .= " ORDER BY fecha_registro DESC LIMIT $limit OFFSET $offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll($search = '')
    {
        $sql = "SELECT COUNT(*) FROM constancia_leads";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE nombres LIKE ? OR apellidos LIKE ? OR documento_identidad LIKE ?";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function create($data)
    {
        $sql = "INSERT INTO constancia_leads (nombres, apellidos, documento_identidad, email, celular) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            $data['nombres'],
            $data['apellidos'],
            $data['documento_identidad'],
            $data['email'] ?? null,
            $data['celular'] ?? null
        ]);
        
        return $result ? $this->pdo->lastInsertId() : false;
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM constancia_leads WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByDocument($documento)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM constancia_leads WHERE documento_identidad = ?");
        $stmt->execute([$documento]);
        return $stmt->fetch();
    }
}

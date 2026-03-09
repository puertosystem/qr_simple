<?php

require_once __DIR__ . '/../config/database.php';

class ConstanciaLead
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getAll($limit = 10, $offset = 0, $search = '', $eventoId = null)
    {
        // Join with constancias and eventos to get event details
        $sql = "SELECT l.*, 
                c.id as constancia_id,
                c.num_descargas,
                c.fecha_generacion,
                e.nombre as evento_nombre
                FROM constancia_leads l
                LEFT JOIN constancias c ON l.id = c.lead_id
                LEFT JOIN constancia_eventos e ON c.evento_id = e.id";
        
        $whereClauses = [];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(l.nombres LIKE ? OR l.apellidos LIKE ? OR l.documento_identidad LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($eventoId)) {
            $whereClauses[] = "c.evento_id = ?";
            $params[] = $eventoId;
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $sql .= " ORDER BY l.fecha_registro DESC LIMIT $limit OFFSET $offset";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en consulta de leads con join: " . $e->getMessage());
            return [];
        }
    }

    public function countAll($search = '', $eventoId = null)
    {
        $sql = "SELECT COUNT(*) FROM constancia_leads l
                LEFT JOIN constancias c ON l.id = c.lead_id";
        
        $whereClauses = [];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(l.nombres LIKE ? OR l.apellidos LIKE ? OR l.documento_identidad LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($eventoId)) {
            $whereClauses[] = "c.evento_id = ?";
            $params[] = $eventoId;
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
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
}

<?php

require_once __DIR__ . '/../config/database.php';

class ConstanciaEvento
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getAll()
    {
        $stmt = $this->pdo->query("SELECT * FROM constancia_eventos ORDER BY fecha_creacion DESC");
        return $stmt->fetchAll();
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM constancia_eventos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getLastActive()
    {
        $stmt = $this->pdo->query("SELECT * FROM constancia_eventos WHERE activo = 1 ORDER BY fecha_creacion DESC LIMIT 1");
        return $stmt->fetch();
    }

    public function create($data)
    {
        $sql = "INSERT INTO constancia_eventos (nombre, fecha_inicio, fecha_fin, fondo_constancia, activo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nombre'],
            $data['fecha_inicio'],
            $data['fecha_fin'] ?? null,
            $data['fondo_constancia'] ?? null,
            $data['activo'] ?? 1
        ]);
    }

    public function update($id, $data)
    {
        $sql = "UPDATE constancia_eventos SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, fondo_constancia = ?, activo = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nombre'],
            $data['fecha_inicio'],
            $data['fecha_fin'] ?? null,
            $data['fondo_constancia'],
            $data['activo'],
            $id
        ]);
    }

    public function toggleStatus($id)
    {
        $event = $this->findById($id);
        if (!$event) return false;

        $newStatus = $event['activo'] ? 0 : 1;
        $stmt = $this->pdo->prepare("UPDATE constancia_eventos SET activo = ? WHERE id = ?");
        return $stmt->execute([$newStatus, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM constancia_eventos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getConstancia($leadId, $eventoId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM constancias WHERE lead_id = ? AND evento_id = ?");
        $stmt->execute([$leadId, $eventoId]);
        return $stmt->fetch();
    }

    public function createConstancia($data)
    {
        $sql = "INSERT INTO constancias (lead_id, evento_id, codigo_verificacion, qr_codigo, fecha_generacion, ip_generacion, user_agent) VALUES (?, ?, ?, ?, NOW(), ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['lead_id'],
            $data['evento_id'],
            $data['codigo_verificacion'],
            $data['qr_codigo'],
            $data['ip_generacion'] ?? null,
            $data['user_agent'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }

    public function generateUniqueCode()
    {
        $prefix = 'CONST-';
        do {
            $code = $prefix . strtoupper(bin2hex(random_bytes(6)));
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM constancias WHERE codigo_verificacion = ?");
            $stmt->execute([$code]);
            $exists = $stmt->fetchColumn() > 0;
        } while ($exists);
        return $code;
    }
}

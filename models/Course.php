<?php

class Course
{
    public static function create(PDO $pdo, array $data): int
    {
        // Map to 'cursos' table with mixed legacy/new columns
        $sql = 'INSERT INTO cursos (
                event_type_id,
                event_modality_id,
                nombre,
                description,
                fecha_inicio,
                fecha_fin,
                horas_academicas,
                creditos_academicos,
                event_code,
                max_capacity,
                imagen_fondo,
                status,
                created_at,
                updated_at
            ) VALUES (
                :event_type_id,
                :event_modality_id,
                :name,
                :description,
                :start_date,
                :end_date,
                :total_hours,
                :credits,
                :event_code,
                :max_capacity,
                :certificate_background_filename,
                :status,
                NOW(),
                NOW()
            )';

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':event_type_id' => $data['event_type_id'],
            ':event_modality_id' => $data['event_modality_id'],
            ':name' => $data['name'],
            ':description' => $data['description'] ?: null,
            ':start_date' => $data['start_date'] ?: null,
            ':end_date' => $data['end_date'] ?: null,
            ':total_hours' => $data['total_hours'] !== '' ? (string)$data['total_hours'] : null, // Store as string for legacy text column
            ':credits' => $data['credits'] !== '' ? (string)$data['credits'] : null, // Store as string
            ':event_code' => $data['internal_code'],
            ':max_capacity' => $data['max_capacity'] !== '' ? (int) $data['max_capacity'] : null,
            ':certificate_background_filename' => $data['certificate_background_filename'] ?: null,
            ':status' => 'active',
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, array $data): void
    {
        $sql = "UPDATE cursos SET 
                event_type_id = ?, 
                event_modality_id = ?, 
                nombre = ?, 
                fecha_inicio = ?, 
                fecha_fin = ?, 
                horas_academicas = ?, 
                creditos_academicos = ?, 
                event_code = ?, 
                max_capacity = ?, 
                description = ?,
                updated_at = NOW()";
        
        $params = [
            $data['event_type_id'],
            $data['event_modality_id'],
            $data['name'],
            !empty($data['start_date']) ? $data['start_date'] : null,
            !empty($data['end_date']) ? $data['end_date'] : null,
            ($data['total_hours'] !== '' && $data['total_hours'] !== null) ? (string)$data['total_hours'] : null,
            ($data['credits'] !== '' && $data['credits'] !== null) ? (string)$data['credits'] : null,
            $data['internal_code'] ?? null,
            ($data['max_capacity'] !== '' && $data['max_capacity'] !== null) ? (int)$data['max_capacity'] : null,
            !empty($data['description']) ? $data['description'] : null
        ];

        if (array_key_exists('certificate_background_filename', $data)) {
            $sql .= ", imagen_fondo = ?";
            $params[] = $data['certificate_background_filename'];
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    public static function createWithDetails(PDO $pdo, array $data, array $auspiceIds, string $typeCode, string $modalityCode): array
    {
        try {
            $pdo->beginTransaction();

            $eventTypeId = self::ensureEventType($pdo, $typeCode);
            $eventModalityId = self::ensureEventModality($pdo, $modalityCode);

            $data['event_type_id'] = $eventTypeId;
            $data['event_modality_id'] = $eventModalityId;

            if (empty($data['internal_code'])) {
                $data['internal_code'] = self::generateInternalCode($pdo, $typeCode);
            }

            $eventId = self::create($pdo, $data);
            self::saveAuspices($pdo, $eventId, $auspiceIds);

            $pdo->commit();

            return ['id' => $eventId, 'code' => $data['internal_code']];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function updateWithDetails(PDO $pdo, int $id, array $data, array $auspiceIds, string $typeCode, string $modalityCode): void
    {
        try {
            $pdo->beginTransaction();

            $eventTypeId = self::ensureEventType($pdo, $typeCode);
            $eventModalityId = self::ensureEventModality($pdo, $modalityCode);

            $data['event_type_id'] = $eventTypeId;
            $data['event_modality_id'] = $eventModalityId;

            self::update($pdo, $id, $data);

            self::deleteAuspices($pdo, $id);
            self::saveAuspices($pdo, $id, $auspiceIds);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function paginate(PDO $pdo, int $page, int $limit, string $search): array
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = '';

        if ($search !== '') {
            $where = 'WHERE (e.nombre LIKE :search_name OR e.event_code LIKE :search_code OR et.name LIKE :search_type)';
            $params[':search_name'] = "%$search%";
            $params[':search_code'] = "%$search%";
            $params[':search_type'] = "%$search%";
        }

        // Count total
        $countSql = "SELECT COUNT(*) 
                     FROM cursos e
                     LEFT JOIN event_types et ON e.event_type_id = et.id
                     $where";
        $stmt = $pdo->prepare($countSql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();
        $totalPages = ($limit > 0) ? ceil($total / $limit) : 0;

        // Fetch data
        $sql = "
            SELECT 
                e.id,
                e.event_code,
                e.nombre as name,
                e.fecha_inicio as start_date,
                e.status,
                e.imagen_fondo as certificate_background_filename,
                et.name as type_name,
                em.name as modality_name,
                (SELECT COUNT(*) FROM curso_estudiantes WHERE curso_id = e.id) as total_participants,
                (SELECT COUNT(*) FROM curso_estudiantes WHERE curso_id = e.id AND status = 'active') as active_participants,
                (SELECT COUNT(*) FROM curso_estudiantes WHERE curso_id = e.id AND status = 'completed') as completed_participants,
                (SELECT COUNT(*) FROM certificados WHERE curso_id = e.id) as with_qr,
                (SELECT COUNT(*) FROM curso_estudiantes ce WHERE ce.curso_id = e.id) - (SELECT COUNT(*) FROM certificados WHERE curso_id = e.id) as without_qr
            FROM cursos e
            LEFT JOIN event_types et ON e.event_type_id = et.id
            LEFT JOIN event_modalities em ON e.event_modality_id = em.id
            $where
            ORDER BY e.created_at DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
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

    public static function getAvailableForParticipant(PDO $pdo, int $participantId, string $search): array
    {
        $params = [':participant_id' => $participantId];
        $where = "WHERE e.id NOT IN (SELECT curso_id FROM curso_estudiantes WHERE usuario_id = :participant_id)";

        if ($search !== '') {
            $where .= " AND (e.nombre LIKE :search_name OR e.event_code LIKE :search_code)";
            $params[':search_name'] = "%$search%";
            $params[':search_code'] = "%$search%";
        }

        $sql = "SELECT e.id, e.nombre as name, e.event_code, et.name as type_name 
                FROM cursos e 
                LEFT JOIN event_types et ON e.event_type_id = et.id
                $where 
                ORDER BY e.created_at DESC 
                LIMIT 20";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function count(PDO $pdo, string $search): int
    {
        $search = "%$search%";
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cursos WHERE nombre LIKE ? OR event_code LIKE ?");
        $stmt->execute([$search, $search]);
        return (int)$stmt->fetchColumn();
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("
            SELECT e.*, 
                   e.nombre as name,
                   e.fecha_inicio as start_date,
                   e.fecha_fin as end_date,
                   e.horas_academicas as total_hours,
                   e.creditos_academicos as credits,
                   e.imagen_fondo as certificate_background_filename,
                   et.code as event_type_code, 
                   em.code as event_modality_code
            FROM cursos e
            LEFT JOIN event_types et ON e.event_type_id = et.id
            LEFT JOIN event_modalities em ON e.event_modality_id = em.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($course) {
            $stmt = $pdo->prepare("SELECT auspice_id FROM curso_auspicios WHERE curso_id = ?");
            $stmt->execute([$id]);
            $course['auspice_ids'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        return $course ?: null;
    }

    public static function ensureEventType(PDO $pdo, string $code): int
    {
        $stmt = $pdo->prepare("SELECT id FROM event_types WHERE code = ?");
        $stmt->execute([$code]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int)$id;
        }

        $names = [
            'course' => 'Curso',
            'diploma' => 'Diplomado',
            'congress' => 'Congreso',
            'seminar' => 'Seminario'
        ];
        $name = $names[$code] ?? ucfirst($code);

        $stmt = $pdo->prepare("INSERT INTO event_types (code, name, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$code, $name]);
        
        return (int)$pdo->lastInsertId();
    }

    public static function ensureEventModality(PDO $pdo, string $code): int
    {
        $stmt = $pdo->prepare("SELECT id FROM event_modalities WHERE code = ?");
        $stmt->execute([$code]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int)$id;
        }

        $names = [
            'virtual' => 'Virtual',
            'presencial' => 'Presencial',
            'semipresencial' => 'Semipresencial'
        ];
        $name = $names[$code] ?? ucfirst($code);

        $stmt = $pdo->prepare("INSERT INTO event_modalities (code, name, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$code, $name]);

        return (int)$pdo->lastInsertId();
    }

    public static function generateInternalCode(PDO $pdo, string $typeCode): string
    {
        $prefix = strtoupper(substr($typeCode, 0, 3));
        $year = date('Y');
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cursos WHERE event_code LIKE ?");
        $stmt->execute(["$prefix-$year-%"]);
        $count = $stmt->fetchColumn();
        
        return sprintf("%s-%s-%04d", $prefix, $year, $count + 1);
    }

    public static function getEventTypes(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM event_types ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getEventModalities(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM event_modalities ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAuspices(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM auspices ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function saveAuspices(PDO $pdo, int $eventId, array $auspiceIds): void
    {
        if (empty($auspiceIds)) return;
        
        $stmt = $pdo->prepare("INSERT INTO curso_auspicios (curso_id, auspice_id) VALUES (?, ?)");
        foreach ($auspiceIds as $auspiceId) {
            $stmt->execute([$eventId, $auspiceId]);
        }
    }

    public static function deleteAuspices(PDO $pdo, int $eventId): void
    {
        $stmt = $pdo->prepare("DELETE FROM curso_auspicios WHERE curso_id = ?");
        $stmt->execute([$eventId]);
    }
}

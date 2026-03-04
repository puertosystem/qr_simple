<?php

class Auspice
{
    public static function getAll(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT * FROM auspices ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(PDO $pdo, array $data): int
    {
        $sql = 'INSERT INTO auspices (code, name, logo_url, website_url, active) VALUES (:code, :name, :logo_url, :website_url, :active)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':code' => $data['code'] ?? '',
            ':name' => $data['name'],
            ':logo_url' => $data['logo_url'] ?? '',
            ':website_url' => $data['website_url'] ?? '',
            ':active' => $data['active'] ?? 1
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, array $data): void
    {
        $sql = 'UPDATE auspices SET code = :code, name = :name, logo_url = :logo_url, website_url = :website_url, active = :active WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':code' => $data['code'] ?? '',
            ':name' => $data['name'],
            ':logo_url' => $data['logo_url'] ?? '',
            ':website_url' => $data['website_url'] ?? '',
            ':active' => $data['active'] ?? 1
        ]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('DELETE FROM auspices WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM auspices WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}

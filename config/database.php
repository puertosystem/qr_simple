<?php

class Database
{
    public static function getConnection(): PDO
    {
        $host = getenv('QR_DB_HOST') ?: 'localhost';
        $dbName = getenv('QR_DB_NAME') ?: 'aulavirtual'; // bd: aulavirtual
        $user = getenv('QR_DB_USER') ?: 'root'; // user: root
        $password = getenv('QR_DB_PASSWORD') ?: '1234'; // pass: 1234

        $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, $user, $password, $options);
            return $pdo;
        } catch (PDOException $e) {
            error_log('Error de conexión a la base de datos QR: ' . $e->getMessage());
            throw $e;
        }
    }
}


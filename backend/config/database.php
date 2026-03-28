<?php
/**
 * Configuration de la connexion à la base de données
 */

require_once __DIR__ . '/../.env.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            // Détection robuste du driver
            $dbHost = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
            $driver = getenv('DB_DRIVER') ?: (defined('DB_DRIVER') ? DB_DRIVER : '');

            if (empty($driver)) {
                $driver = (strpos($dbHost, 'supabase') !== false || strpos($dbHost, 'pooler') !== false) ? 'pgsql' : 'mysql';
            }

            $dbPort = getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : ($driver === 'pgsql' ? '6543' : '3306'));
            $dbName = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : '');
            $dbUser = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : '');
            $dbPass = getenv('DB_PASSWORD') ?: (defined('DB_PASSWORD') ? DB_PASSWORD : '');
            
            // AUTO-FIX: Si host est Supabase Pooler et user est 'postgres', on ajoute le tenant id
            if ($driver === 'pgsql' && strpos($dbHost, 'pooler.supabase.com') !== false && $dbUser === 'postgres') {
                $dbUser = 'postgres.jxlhjeqyrtrnhziuizlw';
            }

            if ($driver === 'pgsql') {
                // SSL est obligatoire pour Supabase
                $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName};sslmode=require";
            } else {
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            }
            
            $this->connection = new PDO(
                $dsn,
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('Erreur de connexion à la base de données: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}
?>

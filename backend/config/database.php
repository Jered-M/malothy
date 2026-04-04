<?php
/**
 * Configuration de la connexion a la base de donnees
 */

require_once __DIR__ . '/../.env.php';

class Database {
    private static $instance = null;
    private $connection;

    private function parseDatabaseUrl($databaseUrl) {
        $parts = parse_url($databaseUrl);
        if ($parts === false || empty($parts['scheme'])) {
            return [];
        }

        parse_str($parts['query'] ?? '', $query);

        return [
            'driver' => $parts['scheme'],
            'host' => $parts['host'] ?? null,
            'port' => isset($parts['port']) ? (string) $parts['port'] : null,
            'name' => isset($parts['path']) ? ltrim($parts['path'], '/') : null,
            'user' => isset($parts['user']) ? rawurldecode($parts['user']) : null,
            'password' => isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
            'sslmode' => $query['sslmode'] ?? null,
        ];
    }

    private function __construct() {
        try {
            $databaseUrl = getenv('DATABASE_URL') ?: (defined('DATABASE_URL') ? DATABASE_URL : '');

            $dbHost = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
            $driver = getenv('DB_DRIVER') ?: (defined('DB_DRIVER') ? DB_DRIVER : '');
            $dbPort = getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : ($driver === 'pgsql' ? '6543' : '3306'));
            $dbName = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : '');
            $dbUser = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : '');
            $dbPass = getenv('DB_PASSWORD') ?: (defined('DB_PASSWORD') ? DB_PASSWORD : '');
            $sslMode = null;

            if (!empty($databaseUrl)) {
                $parsedUrl = $this->parseDatabaseUrl($databaseUrl);
                $driver = $parsedUrl['driver'] ?: $driver;
                $dbHost = $parsedUrl['host'] ?: $dbHost;
                $dbPort = $parsedUrl['port'] ?: $dbPort;
                $dbName = $parsedUrl['name'] ?: $dbName;
                $dbUser = $parsedUrl['user'] ?: $dbUser;
                $dbPass = $parsedUrl['password'] ?: $dbPass;
                $sslMode = $parsedUrl['sslmode'] ?? null;
            }

            if (empty($driver)) {
                $driver = (strpos($dbHost, 'supabase') !== false || strpos($dbHost, 'pooler') !== false) ? 'pgsql' : 'mysql';
            }

            if ($driver === 'pgsql' && strpos($dbHost, 'pooler.supabase.com') !== false && $dbUser === 'postgres') {
                $dbUser = 'postgres.jxlhjeqyrtrnhziuizlw';
            }

            if ($driver === 'pgsql' && !in_array('pgsql', PDO::getAvailableDrivers(), true)) {
                throw new Exception(
                    "Le driver PDO PostgreSQL (pdo_pgsql) n'est pas active. " .
                    "Activez 'extension=pgsql' et 'extension=pdo_pgsql' dans votre php.ini."
                );
            }

            if ($driver === 'mysql' && !in_array('mysql', PDO::getAvailableDrivers(), true)) {
                throw new Exception(
                    "Le driver PDO MySQL (pdo_mysql) n'est pas active dans votre installation PHP."
                );
            }

            if ($driver === 'pgsql') {
                $effectiveSslMode = $sslMode ?: 'require';
                $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName};sslmode={$effectiveSslMode}";
            } else {
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            }

            $emulatePrepares = $driver === 'pgsql';

            $this->connection = new PDO(
                $dsn,
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => $emulatePrepares,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('Erreur de connexion a la base de donnees: ' . $e->getMessage());
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

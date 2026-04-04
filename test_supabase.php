<?php
define('PROJECT_ROOT', __DIR__);
require_once __DIR__ . '/backend/config/api-config.php';

echo "=== TEST CONNEXION SUPABASE ===\n";
echo "Driver demandé: " . (defined('DB_DRIVER') ? DB_DRIVER : 'NON DÉFINI') . "\n";
echo "Extensions PDO: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
echo "Host: " . DB_HOST . "\n";
echo "User: " . DB_USER . "\n";
echo "DB: " . DB_NAME . "\n";
echo "Port: " . DB_PORT . "\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    if ($pdo) {
        echo "\nSUCCESS: Connecté à Supabase !\n";
        
        // Test simple
        $stmt = $pdo->query("SELECT current_database(), current_user, version()");
        $res = $stmt->fetch();
        print_r($res);
        
        // Check tables
        echo "\nTables disponibles :\n";
        $stmt = $pdo->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
        while ($row = $stmt->fetch()) {
            echo "- " . $row['tablename'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
}

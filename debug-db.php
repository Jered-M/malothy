<?php
/**
 * Debug Database Test
 */

header('Content-Type: application/json; charset=utf-8');

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
}

try {
    // Test 1: Load .env
    echo json_encode(['test' => '1. Loading .env'], JSON_PRETTY_PRINT);
    require_once PROJECT_ROOT . '/backend/.env.php';
    
    echo "\n";
    echo json_encode(['test' => '2. Constants loaded', 'DB_HOST' => DB_HOST, 'DB_NAME' => DB_NAME], JSON_PRETTY_PRINT);
    
    // Test 2: Load Database class
    echo "\n";
    require_once PROJECT_ROOT . '/backend/config/database.php';
    echo json_encode(['test' => '3. Database class loaded'], JSON_PRETTY_PRINT);
    
    // Test 3: Connect to database
    echo "\n";
    $db = Database::getInstance();
    echo json_encode(['test' => '4. Database connected'], JSON_PRETTY_PRINT);
    
    // Test 4: Query users table
    echo "\n";
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['test' => '5. Query executed', 'user_count' => $result['count']], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], JSON_PRETTY_PRINT);
}

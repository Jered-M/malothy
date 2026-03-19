<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG API TEST ===\n\n";

// Test 1: Check if constants are set
echo "1. PROJECT_ROOT: ";
echo defined('PROJECT_ROOT') ? "UNDEFINED\n" : constant('PROJECT_ROOT') . "\n";

// Define if not already
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
    echo "   (Set to: " . PROJECT_ROOT . ")\n";
}

// Test 2: Check if config exists
echo "\n2. Config file: " . PROJECT_ROOT . "/backend/config/api-config.php\n";
if (!file_exists(PROJECT_ROOT . '/backend/config/api-config.php')) {
    echo "   ERROR: File not found!\n";
} else {
    echo "   OK\n";
}

// Test 3: Check if User model exists
echo "\n3. User model: " . PROJECT_ROOT . "/backend/models/User.php\n";
if (!file_exists(PROJECT_ROOT . '/backend/models/User.php')) {
    echo "   ERROR: File not found!\n";
} else {
    echo "   OK\n";
}

// Test 4: Load config and test
echo "\n4. Loading config...\n";
try {
    require_once PROJECT_ROOT . '/backend/config/api-config.php';
    echo "   OK\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 5: Check database connection
echo "\n5. Testing database connection...\n";
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    if ($pdo) {
        echo "   OK - PDO connected\n";
        
        // Test query
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   Users in DB: " . $result['total'] . "\n";
    } else {
        echo "   ERROR: PDO not connected\n";
    }
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 6: Check User model
echo "\n6. Testing User model...\n";
try {
    require_once PROJECT_ROOT . '/backend/models/User.php';
    $userModel = new User();
    echo "   OK - User class loaded\n";
    
    // Test authentication
    $user = $userModel->authenticate('admin@maloty.com', 'admin123');
    if ($user) {
        echo "   OK - User authenticated\n";
        echo "   User ID: " . $user['id'] . "\n";
        echo "   User Email: " . $user['email'] . "\n";
    } else {
        echo "   ERROR: User not found or password incorrect\n";
    }
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG TEST ===\n";

<?php
/**
 * Test AuthController directly
 */

header('Content-Type: application/json; charset=utf-8');

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
}

try {
    // Load config
    require_once PROJECT_ROOT . '/backend/config/api-config.php';
    
    // Load User model
    require_once PROJECT_ROOT . '/backend/models/User.php';
    
    // Load AuthController
    require_once PROJECT_ROOT . '/backend/api/controllers/AuthController.php';
    
    // Set up request as if it's a POST /api/auth/login
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [];
    
    // Simulate JSON input
    $json_input = json_encode([
        'email' => 'admin@maloty.com',
        'password' => 'admin123'
    ]);
    
    // Mock the php://input stream
    $test_stream = tmpfile();
    fwrite($test_stream, $json_input);
    rewind($test_stream);
    
    // Create controller
    $controller = new AuthController();
    
    // Call login method
    $controller->login();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ], JSON_PRETTY_PRINT);
}

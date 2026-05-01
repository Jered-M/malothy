<?php
/**
 * Test script for member dashboard API
 */

define('PROJECT_ROOT', __DIR__);
require_once __DIR__ . '/backend/config/api-config.php';
require_once __DIR__ . '/backend/api/controllers/DashboardController.php';

// Simuler une session
session_start();
$_SESSION['user_id'] = 13; // JERED MINONO
$_SESSION['user_email'] = 'minonojered7@gmail.com';
$_SESSION['user_role'] = 'membre';

try {
    $controller = new DashboardController();
    ob_start();
    $controller->member();
    $output = ob_get_clean();
    echo "Output: " . $output . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

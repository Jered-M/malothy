<?php
header('Content-Type: application/json');

// Test si l'API backend répond
define('PROJECT_ROOT', __DIR__);
$apiPath = __DIR__ . '/backend/api/index.php';

if (!file_exists($apiPath)) {
    echo json_encode(['error' => 'API file not found: ' . $apiPath]);
    exit;
}

// Essayer de charger l'API
try {
    require $apiPath;
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

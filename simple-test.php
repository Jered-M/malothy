<?php
/**
 * Test endpoint simple
 */

header('Content-Type: application/json; charset=utf-8');

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
}

echo json_encode([
    'status' => 'ok',
    'project_root' => PROJECT_ROOT,
    'env_file_exists' => file_exists(PROJECT_ROOT . '/.env'),
    'backend_exists' => is_dir(PROJECT_ROOT . '/backend'),
    'api_exists' => is_dir(PROJECT_ROOT . '/backend/api'),
    'env_php_exists' => file_exists(PROJECT_ROOT . '/backend/.env.php')
], JSON_PRETTY_PRINT);

<?php
/**
 * API Backend Router - RESTful
 */

// Global Error Handlers (Improved with logging)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    $logMsg = date('[Y-m-d H:i:s]') . " Error [$errno]: $errstr in $errfile on line $errline \n";
    @file_put_contents(PROJECT_ROOT . '/backend/error_debug.log', $logMsg, FILE_APPEND);
    
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $errstr, 'file' => basename($errfile), 'line' => $errline]);
    exit;
});

set_exception_handler(function($exception) {
    $logMsg = date('[Y-m-d H:i:s]') . " Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . " \n";
    @file_put_contents(PROJECT_ROOT . '/backend/error_debug.log', $logMsg, FILE_APPEND);
    
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $exception->getMessage()]);
    exit;
});

// Project root
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(dirname(dirname(__FILE__))));
}

// Load config (headers are in there)
require_once PROJECT_ROOT . '/backend/config/api-config.php';

// Parse URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = preg_replace('#^/api/?#', '', $path);
$path = trim($path, '/');

if (empty($path)) {
    json_error('Aucune ressource spécifiée', 400);
}

$segments = explode('/', $path);
$resourceName = ucfirst(strtolower($segments[0]));
$controllerFile = PROJECT_ROOT . "/backend/api/controllers/{$resourceName}Controller.php";

if (!file_exists($controllerFile)) {
    json_error("Ressource '{$resourceName}' non trouvée", 404);
}

require_once $controllerFile;
$controllerClass = $resourceName . 'Controller';
$controller = new $controllerClass();

// Determine Action based on HTTP Method and path segments
$httpMethod = $_SERVER['REQUEST_METHOD'];
$id = isset($segments[1]) && is_numeric($segments[1]) ? $segments[1] : null;
$action = null;

if ($id) {
    $action = $segments[2] ?? null;
} else {
    $action = $segments[1] ?? null;
}

$methodToCall = '';
$params = [];

if ($id && $action) {
    // Operations with ID and sub-action (e.g., /members/1/photo)
    $action = str_replace('-', '_', $action);
    switch ($httpMethod) {
        case 'GET':    $methodToCall = 'get_' . $action; break;
        case 'POST':   $methodToCall = 'upload_' . $action; break;
        case 'PUT':    $methodToCall = 'update_' . $action; break;
        case 'DELETE': $methodToCall = 'delete_' . $action; break;
    }
    $params = [$id];
} elseif ($id) {
    // Operations on a specific ID without sub-action
    switch ($httpMethod) {
        case 'GET':    $methodToCall = 'show'; break;
        case 'PUT':    $methodToCall = 'update'; break;
        case 'DELETE': $methodToCall = 'delete'; break;
    }
    $params = [$id];
} else {
    // Operations on a collection or custom action
    if ($action) {
        $methodToCall = str_replace('-', '_', $action);
    } else {
        switch ($httpMethod) {
            case 'GET':  $methodToCall = 'index'; break;
            case 'POST': $methodToCall = 'create'; break;
        }
    }
}

if (!$methodToCall || !method_exists($controller, $methodToCall)) {
    json_error("Action '{$methodToCall}' non disponible pour '{$resourceName}'", 405);
}

// Call the method with params
call_user_func_array([$controller, $methodToCall], $params);
exit;

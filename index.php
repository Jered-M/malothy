<?php
/**
 * Router principal MALOTY
 * Dirige les requêtes vers API ou Frontend
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');
$frontendRoot = __DIR__ . DIRECTORY_SEPARATOR . 'frontend';
$spaRoutes = [
    'dashboard',
    'members',
    'members-form',
    'finance',
    'tithes',
    'tithe-form',
    'offerings',
    'offering-form',
    'expenses',
    'expense-form',
    'reports',
    'settings',
    'audit-logs',
    'contribute',
    'home',
];

// Requête API
if (strpos($path, 'api/') === 0 || $path === 'api') {
    define('PROJECT_ROOT', __DIR__);
    require __DIR__ . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'index.php';
    exit;
}

if ($path === 'login') {
    header('Content-Type: text/html; charset=utf-8');
    require $frontendRoot . DIRECTORY_SEPARATOR . 'login.php';
    exit;
}

// Résoudre le chemin du fichier demandé
$frontendPath = __DIR__ . DIRECTORY_SEPARATOR . $path;

// Si le fichier n'existe pas à la racine, tenter dans le dossier frontend
if (!file_exists($frontendPath) || is_dir($frontendPath)) {
    $frontendPath = $frontendRoot . DIRECTORY_SEPARATOR . $path;
}

// Si c'est un fichier PHP dans le dossier frontend, l'exécuter
// On évite d'exécuter les fichiers PHP à la racine ici pour éviter la récursion (comme login.php)
if (!empty($path) && file_exists($frontendPath) && strpos(realpath($frontendPath), realpath(__DIR__ . '/frontend')) === 0) {
    if (pathinfo($frontendPath, PATHINFO_EXTENSION) === 'php') {
        header('Content-Type: text/html; charset=utf-8');
        require $frontendPath;
        exit;
    }
    // Pour les autres fichiers statiques (css, js, images), laisser le serveur les servir
    return false;
}

// Fallback : Pour toutes les autres routes (spa) ou la racine, servir index.html
if ($path === '' || in_array($path, $spaRoutes, true)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($frontendRoot . DIRECTORY_SEPARATOR . 'index.html');
    exit;
}

return false;
?>

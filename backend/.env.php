<?php
/**
 * Chargement des variables d'environnement
 */
ob_start(); // Commencer la mise en buffer pour éviter les problèmes de headers

// Charger depuis .env à la racine
if (file_exists(dirname(__DIR__) . '/.env')) {
    $envFile = dirname(__DIR__) . '/.env';
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Ignorer les commentaires
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

// Valeurs par défaut
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');
if (!defined('DB_NAME')) define('DB_NAME', 'eglise_m');
if (!defined('DB_PORT')) define('DB_PORT', 3306);
if (!defined('APP_NAME')) define('APP_NAME', 'MALOTY - Gestion d\'Église');
if (!defined('APP_URL')) define('APP_URL', 'http://localhost');
if (!defined('APP_DEBUG')) define('APP_DEBUG', true);
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 3600);

// Constantes de l'application - NE PAS REDÉFINIR si déjà défini
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}
if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', PROJECT_ROOT . '/uploads/');
}
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', '/uploads/');
}
?>

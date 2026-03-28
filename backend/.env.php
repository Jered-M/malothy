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

// Fonction utilitaire pour définir une constante depuis l'environnement ou une valeur par défaut
function defineFromEnv($key, $default) {
    if (!defined($key)) {
        // Tentative de récupération depuis plusieurs sources (PHP sur Render/Docker)
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        
        // Si non trouvé dans l'env, utiliser le défaut
        if ($val === false || $val === null || $val === '') {
            $val = $default;
        }
        
        define($key, $val);
    }
}

// Valeurs par défaut avec priorité sur getenv()
$envHost = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$defaultDriver = (strpos($envHost, 'supabase') !== false || strpos($envHost, 'pooler') !== false) ? 'pgsql' : 'mysql';

defineFromEnv('DB_DRIVER', $defaultDriver);
defineFromEnv('DB_HOST', 'localhost');
defineFromEnv('DB_USER', 'root');
defineFromEnv('DB_PASSWORD', '');

// Nom de base intelligent
$defaultDbName = (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') ? 'postgres' : 'eglise_m';
defineFromEnv('DB_NAME', $defaultDbName);

// Port intelligent
$defaultPort = (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') ? 6543 : 3306;
defineFromEnv('DB_PORT', $defaultPort);

defineFromEnv('APP_NAME', 'MALOTY - Gestion d\'Église');
defineFromEnv('APP_URL', 'http://localhost');
defineFromEnv('APP_DEBUG', true);
defineFromEnv('SESSION_TIMEOUT', 3600);

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
